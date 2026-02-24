<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OptionalFee;
use App\Models\Student;
use App\Models\SwimmingLedger;
use App\Models\Votehead;
use App\Services\InvoiceService;
use App\Services\TransportFeeService;
use App\Services\SwimmingWalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconcile optional fees with invoices and (for swimming) swimming wallets.
 * - Billed optional fee but no invoice item → add item to invoice
 * - Invoice item with source=optional but no billed OptionalFee → remove from invoice
 * - Swimming billed optional fee → verify wallet credit exists; with --fix create missing credit
 * Records changes for tracking when --fix is used.
 */
class ReconcileOptionalFees extends Command
{
    protected $signature = 'finance:reconcile-optional-fees
                            {--year= : Academic year (default: current)}
                            {--term= : Term (default: current)}
                            {--student_id= : Limit to one student}
                            {--fix : Apply changes (default: dry-run)}
                            {--csv= : Export report to CSV path}';

    protected $description = 'Reconcile optional fees with invoices and swimming wallets; report or fix mismatches';

    private array $report = [
        'added_to_invoice' => [],
        'removed_from_invoice' => [],
        'swimming_wallet_missing' => [],
        'swimming_wallet_fixed' => [],
        'errors' => [],
    ];

    public function handle(): int
    {
        [$year, $term] = TransportFeeService::resolveYearAndTerm(
            $this->option('year') ? (int) $this->option('year') : null,
            $this->option('term') ? (int) $this->option('term') : null
        );

        $fix = $this->option('fix');
        $studentId = $this->option('student_id') ? (int) $this->option('student_id') : null;

        $this->info("Reconciling optional fees for year={$year}, term={$term}" . ($fix ? ' (FIX mode)' : ' (dry-run)'));
        $this->newLine();

        $optionalFees = OptionalFee::where('year', $year)
            ->where('term', $term)
            ->where('status', 'billed')
            ->when($studentId, fn ($q) => $q->where('student_id', $studentId))
            ->whereHas('student', fn ($q) => $q->where('archive', 0)->where('is_alumni', false))
            ->with(['student:id,first_name,last_name,admission_number', 'votehead:id,name,code'])
            ->get();

        $invoices = Invoice::where('year', $year)
            ->where('term', $term)
            ->when($studentId, fn ($q) => $q->where('student_id', $studentId))
            ->with('items')
            ->get()
            ->keyBy('student_id');

        // 1) Billed optional fee but missing on invoice → add
        foreach ($optionalFees as $opt) {
            // Consider item "on invoice" if any line exists for this votehead (any source), to avoid duplicates
            $invoice = $invoices->get($opt->student_id);
            $item = $invoice
                ? $invoice->items->firstWhere(fn ($i) => (int) $i->votehead_id === (int) $opt->votehead_id)
                : null;

            if (!$item) {
                $this->report['added_to_invoice'][] = [
                    'student_id' => $opt->student_id,
                    'student_name' => $this->studentName($opt->student),
                    'admission' => $opt->student->admission_number ?? '',
                    'votehead' => $opt->votehead ? $opt->votehead->name : '?',
                    'optional_fee_id' => $opt->id,
                    'amount' => (float) $opt->amount,
                ];

                if ($fix) {
                    $this->addOptionalFeeToInvoice($opt, $year, $term);
                }
            }

            // Swimming: check wallet credit exists
            if ($opt->votehead_id && $this->isSwimmingVotehead($opt->votehead_id)) {
                $hasCredit = SwimmingLedger::where('student_id', $opt->student_id)
                    ->where('source', SwimmingLedger::SOURCE_OPTIONAL_FEE)
                    ->where('source_id', $opt->id)
                    ->where('type', SwimmingLedger::TYPE_CREDIT)
                    ->whereNull('swimming_attendance_id')
                    ->exists();

                if (!$hasCredit && (float) $opt->amount > 0) {
                    $this->report['swimming_wallet_missing'][] = [
                        'student_id' => $opt->student_id,
                        'student_name' => $this->studentName($opt->student),
                        'admission' => $opt->student->admission_number ?? '',
                        'votehead' => $opt->votehead ? $opt->votehead->name : '?',
                        'optional_fee_id' => $opt->id,
                        'amount' => (float) $opt->amount,
                    ];
                }
            }
        }

        // 2) Invoice items to remove: (a) source=optional with no billed OptionalFee, or
        //    (b) any item whose votehead has an OptionalFee for this student/year/term with status != 'billed' (exempt), or
        //    (c) item votehead is an "optional" votehead for this year/term but student has no OptionalFee record (exempt = record was deleted in UI)
        $optionalFeeStatusByKey = OptionalFee::where('year', $year)
            ->where('term', $term)
            ->when($studentId, fn ($q) => $q->where('student_id', $studentId))
            ->get()
            ->keyBy(fn ($o) => "{$o->student_id}_{$o->votehead_id}");

        $optionalVoteheadIds = OptionalFee::where('year', $year)
            ->where('term', $term)
            ->pluck('votehead_id')
            ->unique()
            ->merge(
                Votehead::where('is_mandatory', false)->pluck('id')
            )
            ->unique()
            ->flip();

        foreach ($invoices as $invoice) {
            $studentIdForInvoice = $invoice->student_id;
            foreach ($invoice->items->filter(fn ($i) => $i->status === 'active') as $item) {
                $source = $item->source ?? '';
                if (in_array($source, ['transport', 'balance_brought_forward'], true)) {
                    continue;
                }
                $votehead = Votehead::find($item->votehead_id);
                $code = $votehead ? strtoupper(trim($votehead->code ?? '')) : '';
                if ($code === 'BAL_BF' || $code === 'TRANSPORT') {
                    continue;
                }

                $key = "{$studentIdForInvoice}_{$item->votehead_id}";
                $optFee = $optionalFeeStatusByKey->get($key);
                $shouldRemove = false;
                if ($optFee !== null) {
                    $shouldRemove = $optFee->status !== 'billed';
                } else {
                    if ($source === 'optional') {
                        $shouldRemove = true;
                    } elseif ($optionalVoteheadIds->has($item->votehead_id)) {
                        $shouldRemove = true;
                    }
                }
                if (!$shouldRemove) {
                    continue;
                }

                $student = $invoice->student ?? Student::find($invoice->student_id);

                $this->report['removed_from_invoice'][] = [
                    'student_id' => $invoice->student_id,
                    'student_name' => $this->studentName($student),
                    'admission' => $student ? $student->admission_number : '',
                    'votehead' => $votehead ? $votehead->name : '?',
                    'invoice_item_id' => $item->id,
                    'amount' => (float) $item->amount,
                ];

                if ($fix) {
                    $this->removeOptionalFeeFromInvoice($item, $invoice);
                }
            }
        }

        // 3) With --fix: create missing swimming wallet credits for billed optional fees
        if ($fix && count($this->report['swimming_wallet_missing']) > 0) {
            $walletService = app(SwimmingWalletService::class);
            $stillMissing = [];
            foreach ($this->report['swimming_wallet_missing'] as $r) {
                if ($this->creditSwimmingWalletForOptionalFee($walletService, $r)) {
                    $this->report['swimming_wallet_fixed'][] = $r;
                } else {
                    $stillMissing[] = $r;
                }
            }
            $this->report['swimming_wallet_missing'] = $stillMissing;
        }

        $this->outputReport($fix);
        $this->maybeExportCsv();

        if ($fix && (count($this->report['added_to_invoice']) + count($this->report['removed_from_invoice']) + count($this->report['swimming_wallet_fixed']) > 0)) {
            Log::info('Optional fee reconciliation completed', [
                'year' => $year,
                'term' => $term,
                'added_count' => count($this->report['added_to_invoice']),
                'removed_count' => count($this->report['removed_from_invoice']),
                'swimming_credited_count' => count($this->report['swimming_wallet_fixed']),
                'swimming_missing_count' => count($this->report['swimming_wallet_missing']),
                'added' => $this->report['added_to_invoice'],
                'removed' => $this->report['removed_from_invoice'],
                'swimming_fixed' => $this->report['swimming_wallet_fixed'],
            ]);
        }

        return count($this->report['errors']) > 0 ? 1 : 0;
    }

    private function studentName($student): string
    {
        if (!$student) {
            return '—';
        }
        return trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) ?: $student->admission_number ?? '—';
    }

    private function isSwimmingVotehead(?int $voteheadId): bool
    {
        if (!$voteheadId) {
            return false;
        }
        $v = Votehead::find($voteheadId);
        if (!$v) {
            return false;
        }
        $is = stripos($v->name ?? '', 'swimming') !== false || stripos($v->code ?? '', 'SWIM') !== false;
        return $is && !$v->is_mandatory;
    }

    private function addOptionalFeeToInvoice(OptionalFee $opt, int $year, int $term): void
    {
        try {
            DB::transaction(function () use ($opt, $year, $term) {
                $invoice = InvoiceService::ensure($opt->student_id, $year, $term);
                $amount = (float) $opt->amount;

                if ($amount <= 0) {
                    $amount = $this->resolveOptionalFeeAmountFromStructure($opt);
                }
                if ($amount <= 0) {
                    $this->report['errors'][] = "Optional fee #{$opt->id} has zero amount; skipped adding to invoice.";
                    return;
                }

                $existing = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('votehead_id', $opt->votehead_id)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'amount' => $amount,
                        'original_amount' => $amount,
                        'status' => 'active',
                        'source' => 'optional',
                        'posted_at' => now(),
                    ]);
                    $item = $existing;
                } else {
                    $item = InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'votehead_id' => $opt->votehead_id,
                        'amount' => $amount,
                        'discount_amount' => 0,
                        'original_amount' => $amount,
                        'status' => 'active',
                        'source' => 'optional',
                        'posted_at' => now(),
                    ]);
                }

                InvoiceService::recalc($invoice);

                Log::info('ReconcileOptionalFees: added optional fee to invoice', [
                    'optional_fee_id' => $opt->id,
                    'student_id' => $opt->student_id,
                    'invoice_item_id' => $item->id,
                ]);
            });
        } catch (\Throwable $e) {
            $this->report['errors'][] = "Add failed for optional_fee_id={$opt->id}: " . $e->getMessage();
            Log::error('ReconcileOptionalFees add failed', ['optional_fee_id' => $opt->id, 'error' => $e->getMessage()]);
        }
    }

    private function resolveOptionalFeeAmountFromStructure(OptionalFee $opt): float
    {
        $student = $opt->student ?? Student::find($opt->student_id);
        if (!$student) {
            return 0;
        }
        $year = $opt->year;
        $term = $opt->term;

        $q = \App\Models\FeeStructure::with('charges')
            ->where('classroom_id', $student->classroom_id)
            ->where('is_active', true);

        $ay = AcademicYear::where('year', $year)->first();
        if ($ay) {
            $q->where(function ($q) use ($ay, $year) {
                $q->where('academic_year_id', $ay->id)->orWhere('year', $year);
            });
        } else {
            $q->where('year', $year);
        }

        if ($student->category_id === null) {
            $q->whereNull('student_category_id');
        } else {
            $q->where('student_category_id', $student->category_id);
        }
        if ($student->stream_id) {
            $q->where(function ($q) use ($student) {
                $q->where('stream_id', $student->stream_id)->orWhereNull('stream_id');
            });
        } else {
            $q->whereNull('stream_id');
        }

        $structure = $q->first();
        if (!$structure) {
            return 0;
        }

        $charge = $structure->charges()
            ->where('votehead_id', $opt->votehead_id)
            ->where('term', $term)
            ->first();

        return $charge ? (float) $charge->amount : 0;
    }

    private function removeOptionalFeeFromInvoice(InvoiceItem $item, Invoice $invoice): void
    {
        try {
            DB::transaction(function () use ($item, $invoice) {
                $paymentIds = $item->allocations()->pluck('payment_id')->unique()->filter();
                $item->allocations()->delete();
                $item->delete();

                InvoiceService::recalc($invoice);
                InvoiceService::allocateUnallocatedPaymentsForStudent($invoice->student_id);

                foreach ($paymentIds as $pid) {
                    $p = \App\Models\Payment::find($pid);
                    if ($p) {
                        $p->updateAllocationTotals();
                    }
                }

                Log::info('ReconcileOptionalFees: removed optional fee from invoice', [
                    'invoice_item_id' => $item->id,
                    'student_id' => $invoice->student_id,
                ]);
            });
        } catch (\Throwable $e) {
            $this->report['errors'][] = "Remove failed for invoice_item_id={$item->id}: " . $e->getMessage();
            Log::error('ReconcileOptionalFees remove failed', ['invoice_item_id' => $item->id, 'error' => $e->getMessage()]);
        }
    }

    private function creditSwimmingWalletForOptionalFee(SwimmingWalletService $walletService, array $r): bool
    {
        $optionalFee = OptionalFee::with('student')->find($r['optional_fee_id']);
        $student = $optionalFee ? $optionalFee->student : Student::find($r['student_id']);
        if (!$optionalFee || !$student) {
            $this->report['errors'][] = "Swimming credit failed: optional_fee_id={$r['optional_fee_id']} not found.";
            return false;
        }
        try {
            $walletService->creditFromOptionalFee(
                $student,
                $optionalFee,
                (float) $r['amount'],
                "Swimming termly fee for Term {$optionalFee->term} (reconcile)"
            );
            return true;
        } catch (\Throwable $e) {
            $this->report['errors'][] = "Swimming credit failed for {$r['admission']}: " . $e->getMessage();
            Log::error('ReconcileOptionalFees swimming credit failed', [
                'optional_fee_id' => $r['optional_fee_id'],
                'student_id' => $r['student_id'],
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function outputReport(bool $fix): void
    {
        $added = $this->report['added_to_invoice'];
        $removed = $this->report['removed_from_invoice'];
        $swimmingFixed = $this->report['swimming_wallet_fixed'];
        $swimmingMissing = $this->report['swimming_wallet_missing'];
        $errors = $this->report['errors'];

        if ($added) {
            $this->table(
                ['Student', 'Admission', 'Votehead', 'Amount', $fix ? 'Action' : 'Note'],
                array_map(fn ($r) => [
                    $r['student_name'],
                    $r['admission'],
                    $r['votehead'],
                    number_format($r['amount'], 2),
                    $fix ? 'Added' : 'Would add to invoice',
                ], $added)
            );
        }

        if ($removed) {
            $this->table(
                ['Student', 'Admission', 'Votehead', 'Amount', $fix ? 'Action' : 'Would remove'],
                array_map(fn ($r) => [
                    $r['student_name'],
                    $r['admission'],
                    $r['votehead'],
                    number_format($r['amount'], 2),
                    $fix ? 'Removed' : 'Would remove from invoice',
                ], $removed)
            );
        }

        if ($swimmingFixed) {
            $this->info('Swimming wallet credited:');
            $this->table(
                ['Student', 'Admission', 'Votehead', 'Amount', 'Action'],
                array_map(fn ($r) => [
                    $r['student_name'],
                    $r['admission'],
                    $r['votehead'],
                    number_format($r['amount'], 2),
                    'Credited',
                ], $swimmingFixed)
            );
        }

        if ($swimmingMissing) {
            $this->warn('Swimming wallet credit missing (run with --fix to create credit):');
            $this->table(
                ['Student', 'Admission', 'Votehead', 'Amount'],
                array_map(fn ($r) => [$r['student_name'], $r['admission'], $r['votehead'], number_format($r['amount'], 2)], $swimmingMissing)
            );
        }

        foreach ($errors as $err) {
            $this->error($err);
        }

        $this->newLine();
        $this->info('Summary: ' . count($added) . ' added to invoice, ' . count($removed) . ' removed from invoice, ' . count($swimmingFixed) . ' swimming wallet credited, ' . count($swimmingMissing) . ' swimming wallet missing.');
        if (!$fix && (count($added) + count($removed) + count($swimmingMissing) > 0)) {
            $this->comment('Run with --fix to apply changes.');
        }
    }

    private function maybeExportCsv(): void
    {
        $path = $this->option('csv');
        if (!$path) {
            return;
        }

        $rows = [];
        foreach ($this->report['added_to_invoice'] as $r) {
            $rows[] = ['action' => 'added', 'student' => $r['student_name'], 'admission' => $r['admission'], 'votehead' => $r['votehead'], 'amount' => $r['amount']];
        }
        foreach ($this->report['removed_from_invoice'] as $r) {
            $rows[] = ['action' => 'removed', 'student' => $r['student_name'], 'admission' => $r['admission'], 'votehead' => $r['votehead'], 'amount' => $r['amount']];
        }
        foreach ($this->report['swimming_wallet_missing'] as $r) {
            $rows[] = ['action' => 'swimming_missing', 'student' => $r['student_name'], 'admission' => $r['admission'], 'votehead' => $r['votehead'], 'amount' => $r['amount']];
        }
        foreach ($this->report['swimming_wallet_fixed'] as $r) {
            $rows[] = ['action' => 'swimming_credited', 'student' => $r['student_name'], 'admission' => $r['admission'], 'votehead' => $r['votehead'], 'amount' => $r['amount']];
        }

        $fp = fopen($path, 'w');
        if (!$fp) {
            $this->error("Could not open CSV: {$path}");
            return;
        }
        fputcsv($fp, ['action', 'student', 'admission', 'votehead', 'amount']);
        foreach ($rows as $r) {
            fputcsv($fp, $r);
        }
        fclose($fp);
        $this->info("Report exported to {$path}");
    }
}
