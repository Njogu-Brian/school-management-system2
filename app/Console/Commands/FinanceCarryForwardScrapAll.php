<?php

namespace App\Console\Commands;

use App\Models\{Invoice, InvoiceItem, Payment, PaymentAllocation, Student, Votehead};
use App\Services\{InvoiceService, OldestInvoiceFirstAllocator};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One-shot cleanup after scrapping the intra-year "Balance from prior term(s)" mechanism.
 *
 * Removes across ALL years/terms (scope can still be narrowed via --year / --student):
 *   - Synthetic `prior_term_carryforward` InvoiceItems (PRIOR_TERM_ARREARS votehead)
 *   - Their payment allocations
 *   - The internal "Internal transfer" / `term_balance_transfer` Payments
 *     (TERM-CF-{year}-T{term}-S{studentId}) — allocations deleted and payment marked reversed
 *
 * Then recalculates affected invoices and reallocates real payments oldest-invoice-first
 * so each invoice's paid_amount/balance matches the real transaction history.
 */
class FinanceCarryForwardScrapAll extends Command
{
    protected $signature = 'finance:cf:scrap-all
                            {--year= : Optional: restrict to a single academic year}
                            {--student= : Optional: admission number or student ID}
                            {--dry-run : Report intended changes without mutating data}
                            {--no-reallocate : Skip the oldest-invoice-first reallocation step}
                            {--output-dir= : Output directory under storage/app (optional)}';

    protected $description = 'Scrap all "Balance from prior term(s)" carry-forward artifacts (items + internal transfers) and rebalance invoices.';

    public function handle(OldestInvoiceFirstAllocator $allocator): int
    {
        $year = $this->option('year') ? (int) $this->option('year') : null;
        $studentOpt = $this->option('student');
        $dryRun = (bool) $this->option('dry-run');
        $reallocate = !$this->option('no-reallocate');

        $outputDir = $this->option('output-dir')
            ?: ('finance-migrations/cf_scrap_all_' . now()->format('Ymd_His'));
        $logPath = rtrim($outputDir, '/') . '/scrap_log.json';

        $student = null;
        if ($studentOpt) {
            $student = is_numeric($studentOpt)
                ? Student::find((int) $studentOpt)
                : Student::where('admission_number', (string) $studentOpt)->first();
            if (!$student) {
                $this->error("Student not found: {$studentOpt}");
                return self::FAILURE;
            }
        }

        $priorVoteheadId = Votehead::where('code', 'PRIOR_TERM_ARREARS')->value('id');

        // Collect student IDs with carry-forward artifacts in scope
        $carryItemStudents = InvoiceItem::query()
            ->where(function ($q) use ($priorVoteheadId) {
                $q->where('source', 'prior_term_carryforward');
                if ($priorVoteheadId) {
                    $q->orWhere('votehead_id', $priorVoteheadId);
                }
            })
            ->whereHas('invoice', function ($q) use ($year, $student) {
                $q->where('status', '!=', 'reversed');
                if ($year !== null) {
                    $q->where('year', $year);
                }
                if ($student) {
                    $q->where('student_id', $student->id);
                }
            })
            ->with('invoice:id,student_id,year,term')
            ->get()
            ->pluck('invoice.student_id');

        $transferPaymentStudents = Payment::query()
            ->where('reversed', false)
            ->where(function ($q) {
                $q->where('payment_channel', 'term_balance_transfer')
                    ->orWhereRaw('LOWER(payment_method) = ?', ['internal transfer'])
                    ->orWhere('transaction_code', 'like', 'TERM-CF-%');
            })
            ->when($student, fn ($q) => $q->where('student_id', $student->id))
            ->when($year, fn ($q) => $q->where('transaction_code', 'like', "TERM-CF-{$year}-%"))
            ->pluck('student_id');

        $studentIds = $carryItemStudents->merge($transferPaymentStudents)
            ->filter()
            ->unique()
            ->values();

        $summary = [
            'scope' => [
                'year' => $year,
                'student' => $student ? ['id' => $student->id, 'admission_number' => $student->admission_number] : null,
            ],
            'dry_run' => $dryRun,
            'reallocate' => $reallocate,
            'students_count' => $studentIds->count(),
            'carry_items_deleted' => 0,
            'transfer_allocations_deleted' => 0,
            'transfer_payments_reversed' => 0,
            'invoices_recalced' => 0,
            'real_payments_reallocated' => 0,
            'students' => [],
            'generated_at' => now()->toIso8601String(),
        ];

        $this->info("Scrapping prior-term carry-forward for {$studentIds->count()} student(s)" . ($dryRun ? ' (dry-run)' : '') . '...');

        $bar = $this->output->createProgressBar($studentIds->count());
        $bar->start();

        foreach ($studentIds as $sid) {
            $stu = Student::find($sid);
            if (!$stu) {
                $bar->advance();
                continue;
            }

            $studentLog = [
                'student_id' => (int) $sid,
                'admission_number' => $stu->admission_number,
                'carry_items_deleted' => 0,
                'transfer_allocations_deleted' => 0,
                'transfer_payments_reversed' => 0,
                'invoices_recalced' => [],
                'real_payments_reallocated' => 0,
            ];

            DB::transaction(function () use ($stu, $year, $priorVoteheadId, $dryRun, $reallocate, $allocator, &$studentLog, &$summary) {
                $studentId = (int) $stu->id;

                // 1) Find carry-forward items for this student (optionally scoped to year)
                $carryItems = InvoiceItem::query()
                    ->where(function ($q) use ($priorVoteheadId) {
                        $q->where('source', 'prior_term_carryforward');
                        if ($priorVoteheadId) {
                            $q->orWhere('votehead_id', $priorVoteheadId);
                        }
                    })
                    ->whereHas('invoice', function ($q) use ($studentId, $year) {
                        $q->where('student_id', $studentId)
                          ->where('status', '!=', 'reversed');
                        if ($year !== null) {
                            $q->where('year', $year);
                        }
                    })
                    ->get(['id', 'invoice_id']);

                $affectedInvoiceIds = $carryItems->pluck('invoice_id')->unique()->all();

                if ($carryItems->isNotEmpty() && !$dryRun) {
                    $carryIds = $carryItems->pluck('id')->all();

                    PaymentAllocation::whereIn('invoice_item_id', $carryIds)->delete();

                    $deleted = InvoiceItem::whereIn('id', $carryIds)->delete();
                    $studentLog['carry_items_deleted'] = (int) $deleted;
                    $summary['carry_items_deleted'] += (int) $deleted;
                } elseif ($carryItems->isNotEmpty()) {
                    $studentLog['carry_items_deleted'] = $carryItems->count();
                }

                // 2) Find internal transfer payments for this student (optionally scoped to year)
                $transferPayments = Payment::query()
                    ->where('student_id', $studentId)
                    ->where('reversed', false)
                    ->where(function ($q) {
                        $q->where('payment_channel', 'term_balance_transfer')
                            ->orWhereRaw('LOWER(payment_method) = ?', ['internal transfer'])
                            ->orWhere('transaction_code', 'like', 'TERM-CF-%');
                    })
                    ->when($year, fn ($q) => $q->where('transaction_code', 'like', "TERM-CF-{$year}-%"))
                    ->get();

                $transferIds = $transferPayments->pluck('id')->all();

                // Track invoice IDs we'll also need to recalc (ones that received transfer allocations)
                if (!empty($transferIds)) {
                    $moreInvoices = PaymentAllocation::query()
                        ->whereIn('payment_id', $transferIds)
                        ->with('invoiceItem:id,invoice_id')
                        ->get()
                        ->map(fn ($a) => optional($a->invoiceItem)->invoice_id)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                    $affectedInvoiceIds = array_values(array_unique(array_merge($affectedInvoiceIds, $moreInvoices)));
                }

                if ($transferPayments->isNotEmpty() && !$dryRun) {
                    $allocDeleted = PaymentAllocation::whereIn('payment_id', $transferIds)->delete();
                    $studentLog['transfer_allocations_deleted'] = (int) $allocDeleted;
                    $summary['transfer_allocations_deleted'] += (int) $allocDeleted;

                    foreach ($transferPayments as $p) {
                        $p->update([
                            'reversed' => true,
                            'reversed_at' => now(),
                            'reversal_reason' => 'Intra-year prior-term carry-forward scrapped',
                            'reversed_by' => auth()->id(),
                        ]);
                        $studentLog['transfer_payments_reversed']++;
                        $summary['transfer_payments_reversed']++;
                    }
                } elseif ($transferPayments->isNotEmpty()) {
                    $studentLog['transfer_payments_reversed'] = $transferPayments->count();
                    $studentLog['transfer_allocations_deleted'] = (int) PaymentAllocation::whereIn('payment_id', $transferIds)->count();
                }

                // 3) Recalc affected invoices so balance/paid_amount reflects removed artifacts
                if (!$dryRun && !empty($affectedInvoiceIds)) {
                    $invoices = Invoice::whereIn('id', $affectedInvoiceIds)->get();
                    foreach ($invoices as $inv) {
                        InvoiceService::recalc($inv);
                        $studentLog['invoices_recalced'][] = (int) $inv->id;
                    }
                    $summary['invoices_recalced'] += count($invoices);
                }

                // 4) Reallocate real payments oldest-invoice-first across ALL years of this student
                if ($reallocate && !$dryRun) {
                    $yearsToProcess = Invoice::where('student_id', $studentId)
                        ->where('status', '!=', 'reversed')
                        ->when($year, fn ($q) => $q->where('year', $year))
                        ->distinct()
                        ->pluck('year')
                        ->filter()
                        ->sort()
                        ->values();

                    foreach ($yearsToProcess as $yr) {
                        $invoices = $allocator->collectInvoicesOldestFirst($studentId, (int) $yr, 3);
                        if ($invoices->isEmpty()) {
                            continue;
                        }

                        $realPayments = Payment::query()
                            ->where('student_id', $studentId)
                            ->where('reversed', false)
                            ->whereRaw("COALESCE(receipt_number, '') NOT LIKE 'SWIM-%'")
                            ->where(function ($q) {
                                $q->whereNull('payment_channel')
                                    ->orWhereNotIn('payment_channel', ['term_balance_transfer', 'balance_brought_forward']);
                            })
                            ->where(function ($q) {
                                $q->whereNull('payment_method')
                                    ->orWhereRaw('LOWER(payment_method) != ?', ['internal transfer']);
                            })
                            ->where(function ($q) {
                                $q->whereNull('transaction_code')
                                    ->orWhereRaw("transaction_code NOT LIKE 'TERM-CF-%'");
                            })
                            ->orderBy('payment_date')
                            ->orderBy('id')
                            ->get();

                        foreach ($realPayments as $payment) {
                            if ($payment->allocations()->count() === 0) {
                                // Let the allocator assign from scratch
                                $allocator->allocatePaymentAcrossInvoices($payment, $invoices);
                                $studentLog['real_payments_reallocated']++;
                                $summary['real_payments_reallocated']++;
                                continue;
                            }

                            $payment->allocations()->delete();
                            $payment->updateAllocationTotals();
                            $allocator->allocatePaymentAcrossInvoices($payment, $invoices);
                            $studentLog['real_payments_reallocated']++;
                            $summary['real_payments_reallocated']++;
                        }

                        foreach ($invoices as $inv) {
                            InvoiceService::recalc($inv);
                        }
                    }
                }
            });

            $summary['students'][] = $studentLog;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        Storage::disk('local')->put($logPath, json_encode($summary, JSON_PRETTY_PRINT));

        $this->info("Students processed:           {$summary['students_count']}");
        $this->info("Carry-forward items deleted:  {$summary['carry_items_deleted']}");
        $this->info("Transfer payments reversed:   {$summary['transfer_payments_reversed']}");
        $this->info("Transfer allocations deleted: {$summary['transfer_allocations_deleted']}");
        $this->info("Invoices recalculated:        {$summary['invoices_recalced']}");
        $this->info("Real payments reallocated:    {$summary['real_payments_reallocated']}");
        $this->info("Log: storage/app/{$logPath}");

        return self::SUCCESS;
    }
}
