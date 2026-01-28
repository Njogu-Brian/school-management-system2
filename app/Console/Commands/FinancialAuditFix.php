<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\BankStatementTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Term;
use App\Services\PaymentAllocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialAuditFix extends Command
{
    protected $signature = 'finance:audit-fix
                            {--fix-over-allocated : Reduce over-allocations so total ≤ payment amount}
                            {--fix-invalid-allocations : Delete allocations pointing to non-existent invoice items}
                            {--recalculate-invoices : Recalculate all invoices (paid_amount, balance, status)}
                            {--auto-allocate : Auto-allocate unallocated/partially allocated payments}
                            {--prefer-term= : Prefer this term when auto-allocating (e.g. 2026,1 for 2026 Term 1)}
                            {--reallocate-to-term= : Re-allocate payments to this term first (e.g. 2026,1). Use after fees comparison shows 68 diff}
                            {--all : Run all fixes in the correct order}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Apply fixes for issues found by finance:audit (over-allocations, invalid allocations, invoice recalculation, auto-allocation)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $runAll = $this->option('all');

        if ($runAll) {
            $this->info('Running all fixes in order (1 → 2 → 3 → 4).');
            if ($dryRun) {
                $this->warn('DRY RUN: no changes will be made.');
            }
            $this->fixOverAllocated($dryRun);
            $this->fixInvalidAllocations($dryRun);
            $this->recalculateInvoices($dryRun);
            $this->autoAllocate($dryRun);
            return 0;
        }

        $reallocateToTerm = $this->option('reallocate-to-term');
        $any = $this->option('fix-over-allocated')
            || $this->option('fix-invalid-allocations')
            || $this->option('recalculate-invoices')
            || $this->option('auto-allocate')
            || $reallocateToTerm;

        if (!$any) {
            $this->error('Specify at least one fix option, or use --all.');
            $this->line('Options: --fix-over-allocated, --fix-invalid-allocations, --recalculate-invoices, --auto-allocate, --reallocate-to-term=2026,1');
            return 1;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: no changes will be made.');
        }

        if ($this->option('fix-over-allocated')) {
            $this->fixOverAllocated($dryRun);
        }
        if ($this->option('fix-invalid-allocations')) {
            $this->fixInvalidAllocations($dryRun);
        }
        if ($this->option('recalculate-invoices')) {
            $this->recalculateInvoices($dryRun);
        }
        if ($this->option('auto-allocate')) {
            $this->autoAllocate($dryRun);
        }
        if ($reallocateToTerm) {
            $this->reallocateToTerm($reallocateToTerm, $dryRun);
        }

        return 0;
    }

    /**
     * Fix over-allocated payments: reduce largest allocation by excess so total = payment amount.
     */
    protected function fixOverAllocated(bool $dryRun): void
    {
        $this->info('Step 1: Fixing over-allocated payments...');

        $payments = Payment::where('reversed', false)
            ->whereNull('deleted_at')
            ->get();

        $fixed = 0;
        foreach ($payments as $payment) {
            $allocatedTotal = PaymentAllocation::where('payment_id', $payment->id)->sum('amount');
            $paymentAmount = (float) $payment->amount;

            if ($allocatedTotal <= $paymentAmount) {
                continue;
            }

            $excess = $allocatedTotal - $paymentAmount;

            // Get largest allocation
            $largest = PaymentAllocation::where('payment_id', $payment->id)
                ->orderByDesc('amount')
                ->first();

            if (!$largest) {
                continue;
            }

            $newAmount = (float) $largest->amount - $excess;
            if ($newAmount < 0) {
                $newAmount = 0;
            }

            $this->line("  Payment #{$payment->id} ({$payment->receipt_number}): allocated {$allocatedTotal} > {$paymentAmount}. Reducing allocation #{$largest->id} by {$excess}.");

            if (!$dryRun) {
                DB::transaction(function () use ($largest, $newAmount, $payment) {
                    if ($newAmount <= 0) {
                        $largest->delete();
                    } else {
                        $largest->update(['amount' => $newAmount]);
                    }
                    $payment->updateAllocationTotals();
                    $invoiceItem = InvoiceItem::find($largest->invoice_item_id);
                    if ($invoiceItem && $invoiceItem->invoice) {
                        $invoiceItem->invoice->recalculate();
                    }
                });
            }
            $fixed++;
        }

        $this->info("  Done. " . ($dryRun ? "Would fix" : "Fixed") . " {$fixed} over-allocated payment(s).");
        $this->newLine();
    }

    /**
     * Delete allocations that point to non-existent invoice items; update payment totals.
     */
    protected function fixInvalidAllocations(bool $dryRun): void
    {
        $this->info('Step 2: Fixing invalid allocations (orphans)...');

        $allocations = PaymentAllocation::all();
        $deleted = 0;

        foreach ($allocations as $allocation) {
            $item = InvoiceItem::find($allocation->invoice_item_id);
            if ($item) {
                continue;
            }

            $this->line("  Deleting allocation #{$allocation->id} (payment #{$allocation->payment_id}, invalid invoice_item_id #{$allocation->invoice_item_id}).");

            if (!$dryRun) {
                DB::transaction(function () use ($allocation) {
                    $payment = Payment::find($allocation->payment_id);
                    $allocation->delete();
                    if ($payment) {
                        $payment->updateAllocationTotals();
                    }
                });
            }
            $deleted++;
        }

        $this->info("  Done. " . ($dryRun ? "Would delete" : "Deleted") . " {$deleted} invalid allocation(s).");
        $this->newLine();
    }

    /**
     * Recalculate all non-reversed invoices.
     */
    protected function recalculateInvoices(bool $dryRun): void
    {
        $this->info('Step 3: Recalculating all invoices...');

        $query = Invoice::whereNull('deleted_at');
        if (Schema::hasColumn('invoices', 'reversed_at')) {
            $query->whereNull('reversed_at');
        } else {
            // Fallback if column name differs
            $query->whereRaw('1=1');
        }

        $invoices = $query->get();
        $count = $invoices->count();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $done = 0;
        foreach ($invoices as $invoice) {
            if (!$dryRun) {
                try {
                    $invoice->recalculate();
                } catch (\Throwable $e) {
                    $this->newLine();
                    $this->warn("  Invoice #{$invoice->id} failed: " . $e->getMessage());
                }
            }
            $done++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("  Done. " . ($dryRun ? "Would recalculate" : "Recalculated") . " {$count} invoice(s).");
        $this->newLine();
    }

    /**
     * Auto-allocate unallocated or partially allocated payments.
     * If --prefer-term=YEAR,TERM is set, that term's invoice items are allocated first (e.g. 2026,1 for fees comparison).
     */
    protected function autoAllocate(bool $dryRun): void
    {
        $preferTerm = $this->option('prefer-term');
        $preferTermId = $this->parsePreferTerm($preferTerm);

        $this->info('Step 4: Auto-allocating unallocated/partially allocated payments...');
        if ($preferTermId) {
            $this->line("  Preferring term_id {$preferTermId} (--prefer-term={$preferTerm}).");
        }

        $payments = Payment::where('reversed', false)
            ->whereNull('deleted_at')
            ->get()
            ->filter(function (Payment $p) {
                $allocated = (float) ($p->allocated_amount ?? $p->calculateAllocatedAmount());
                $amount = (float) $p->amount;
                return $allocated < $amount && $amount > 0;
            });

        $service = app(PaymentAllocationService::class);
        $allocated = 0;
        $skipped = 0;

        foreach ($payments as $payment) {
            // Skip swimming payments
            if (strpos($payment->receipt_number ?? '', 'SWIM-') === 0) {
                $skipped++;
                continue;
            }

            if (!$payment->student_id) {
                $this->line("  Skip payment #{$payment->id} ({$payment->receipt_number}): no student.");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  Would auto-allocate payment #{$payment->id} ({$payment->receipt_number}).");
                $allocated++;
                continue;
            }

            try {
                $service->autoAllocate($payment, null, $preferTermId);
                $allocated++;
            } catch (\Throwable $e) {
                $this->line("  Failed payment #{$payment->id} ({$payment->receipt_number}): " . $e->getMessage());
            }
        }

        $this->info("  Done. " . ($dryRun ? "Would process" : "Processed") . " {$allocated} payment(s), skipped {$skipped}.");
        $this->newLine();
    }

    /**
     * Parse --prefer-term=YEAR,TERM or --reallocate-to-term=YEAR,TERM into term_id.
     */
    protected function parsePreferTerm(?string $value): ?int
    {
        if (!$value || !preg_match('/^(\d{4})\s*,\s*(\d)$/', trim($value), $m)) {
            return null;
        }
        $year = (int) $m[1];
        $termNumber = (int) $m[2];
        $academicYear = AcademicYear::where('year', $year)->first();
        if (!$academicYear) {
            return null;
        }
        $term = Term::where('academic_year_id', $academicYear->id)
            ->where(function ($q) use ($termNumber) {
                $q->where('name', 'like', "Term {$termNumber}%")
                    ->orWhere('name', 'like', "%Term {$termNumber}%");
            })
            ->first();
        return $term?->id;
    }

    /**
     * Re-allocate payments so they go to the given term first (e.g. 2026 Term 1 for fees comparison).
     * Finds payments that currently have allocations to other terms, removes allocations, then auto-allocates with preferTermId.
     */
    protected function reallocateToTerm(string $yearTerm, bool $dryRun): void
    {
        $termId = $this->parsePreferTerm($yearTerm);
        if (!$termId) {
            $this->error("Could not resolve term for '{$yearTerm}'. Use format 2026,1 for 2026 Term 1.");
            return;
        }

        $this->info("Re-allocating payments to term_id {$termId} ({$yearTerm}) so fees comparison (Term 1) can match...");

        $service = app(PaymentAllocationService::class);

        // Payments that have at least one allocation to an invoice in a different term
        $paymentIds = PaymentAllocation::whereHas('invoiceItem.invoice', function ($q) use ($termId) {
            $q->where('term_id', '!=', $termId)->orWhereNull('term_id');
        })->pluck('payment_id')->unique();

        $payments = Payment::whereIn('id', $paymentIds)
            ->where('reversed', false)
            ->whereNull('deleted_at')
            ->with('student')
            ->get()
            ->filter(function (Payment $p) {
                return strpos($p->receipt_number ?? '', 'SWIM-') !== 0 && $p->student_id;
            });

        $count = 0;
        foreach ($payments as $payment) {
            // Only re-allocate if student has an invoice for the target term (so payment can go there)
            $hasTermInvoice = Invoice::where('student_id', $payment->student_id)
                ->where('term_id', $termId)
                ->whereNull('reversed_at')
                ->whereNull('deleted_at')
                ->whereHas('items', fn ($q) => $q->where('status', 'active'))
                ->exists();

            if (!$hasTermInvoice) {
                continue;
            }

            if ($dryRun) {
                $this->line("  Would re-allocate payment #{$payment->id} ({$payment->receipt_number}) to term {$yearTerm}.");
                $count++;
                continue;
            }

            try {
                DB::transaction(function () use ($payment, $service, $termId) {
                    $affectedInvoiceIds = PaymentAllocation::where('payment_id', $payment->id)
                        ->get()
                        ->map(fn ($a) => InvoiceItem::find($a->invoice_item_id)?->invoice_id)
                        ->filter()
                        ->unique();
                    PaymentAllocation::where('payment_id', $payment->id)->delete();
                    $payment->updateAllocationTotals();
                    foreach ($affectedInvoiceIds as $invId) {
                        $inv = Invoice::find($invId);
                        if ($inv) {
                            $inv->recalculate();
                        }
                    }
                    $service->autoAllocate($payment, null, $termId);
                });
                $count++;
            } catch (\Throwable $e) {
                $this->line("  Failed payment #{$payment->id} ({$payment->receipt_number}): " . $e->getMessage());
            }
        }

        $this->info("  Done. " . ($dryRun ? "Would re-allocate" : "Re-allocated") . " {$count} payment(s) to term {$yearTerm}.");
        $this->newLine();
    }
}
