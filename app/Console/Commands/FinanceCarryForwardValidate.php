<?php

namespace App\Console\Commands;

use App\Models\{Invoice, InvoiceItem, Payment, PaymentAllocation, Student, Votehead};
use App\Services\StudentBalanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FinanceCarryForwardValidate extends Command
{
    protected $signature = 'finance:cf:validate
                            {--year=2026 : Academic year (invoice.year)}
                            {--term=2 : Term number (invoice.term)}
                            {--output-dir= : Output directory under storage/app (optional)}';

    protected $description = 'Validate invariants after carry-forward reversal + oldest-first reallocation.';

    public function handle(): int
    {
        $year = (int) $this->option('year');
        $term = (int) $this->option('term');

        $outputDir = $this->option('output-dir') ?: "finance-migrations/cf_{$year}_t{$term}";
        $pathCsv = rtrim($outputDir, '/') . '/validation.csv';

        $priorVoteheadId = Votehead::where('code', 'PRIOR_TERM_ARREARS')->value('id');
        $txPrefix = "TERM-CF-{$year}-T{$term}-S";

        $studentIds = Invoice::query()
            ->where('year', $year)
            ->where('term', '<=', $term)
            ->where('status', '!=', 'reversed')
            ->distinct()
            ->pluck('student_id')
            ->filter()
            ->values();

        $rows = [];
        $rows[] = [
            'student_id',
            'admission_number',
            'total_invoiced_terms_1_to_term',
            'total_paid_real_allocations_terms_1_to_term',
            'final_balance_terms_1_to_term',
            'has_term_carryforward_items_on_term_invoice',
            'has_allocations_on_reversed_transfers',
            'notes',
        ];

        foreach ($studentIds as $sid) {
            $student = Student::find($sid);
            if (!$student) continue;

            $invoices = Invoice::query()
                ->where('student_id', $sid)
                ->where('year', $year)
                ->where('term', '<=', $term)
                ->where('status', '!=', 'reversed')
                ->get();

            foreach ($invoices as $inv) {
                $inv->recalculate();
            }

            $totalInvoiced = (float) $invoices->sum('total');
            $finalBalance = (float) $invoices->sum('balance');

            // Paid = sum of allocations to invoice items on these invoices.
            // We exclude only the Term-CF/internal transfer artifacts; BBF credits may legitimately exist
            // (e.g. prior-year overpayments posted as balance brought forward) and should count here.
            $invoiceIds = $invoices->pluck('id')->all();
            $paidReal = 0.0;
            if (!empty($invoiceIds)) {
                $paidReal = (float) DB::table('payment_allocations')
                    ->join('invoice_items', 'payment_allocations.invoice_item_id', '=', 'invoice_items.id')
                    ->join('payments', 'payment_allocations.payment_id', '=', 'payments.id')
                    ->whereIn('invoice_items.invoice_id', $invoiceIds)
                    ->where('payments.reversed', false)
                    ->where(function ($q) use ($txPrefix) {
                        $q->whereNull('payments.payment_channel')
                            ->orWhereNotIn('payments.payment_channel', ['term_balance_transfer']);
                    })
                    ->where(function ($q) {
                        $q->whereNull('payments.payment_method')
                            ->orWhereRaw('LOWER(payments.payment_method) != ?', ['internal transfer']);
                    })
                    ->where(function ($q) use ($txPrefix) {
                        $q->whereNull('payments.transaction_code')
                            ->orWhere('payments.transaction_code', 'not like', $txPrefix . '%')
                            ->orWhereRaw("payments.transaction_code NOT LIKE 'TERM-CF-%'");
                    })
                    ->sum('payment_allocations.amount');
            }

            // Invariant: no carry-forward items remain on term invoice
            $termInvoice = Invoice::query()
                ->where('student_id', $sid)
                ->where('year', $year)
                ->where('term', $term)
                ->where('status', '!=', 'reversed')
                ->first();

            $hasCarryItems = false;
            if ($termInvoice) {
                $hasCarryItems = InvoiceItem::query()
                    ->where('invoice_id', $termInvoice->id)
                    ->where(function ($q) use ($priorVoteheadId) {
                        $q->where('source', 'prior_term_carryforward');
                        if ($priorVoteheadId) {
                            $q->orWhere('votehead_id', $priorVoteheadId);
                        }
                    })
                    ->exists();
            }

            // Invariant: reversed transfers have no allocations
            $hasAllocOnReversedTransfers = Payment::query()
                ->where('student_id', $sid)
                ->where('reversed', true)
                ->where(function ($q) use ($txPrefix) {
                    $q->where('transaction_code', 'like', $txPrefix . '%')
                        ->orWhere('payment_channel', 'term_balance_transfer')
                        ->orWhereRaw('LOWER(payment_method) = ?', ['internal transfer']);
                })
                ->whereHas('allocations')
                ->exists();

            $expectedBalance = max(0.0, $totalInvoiced - $paidReal);
            $ok = abs($expectedBalance - $finalBalance) <= 0.1;
            $notes = $ok ? '' : "balance_mismatch expected={$expectedBalance} actual={$finalBalance}";

            $rows[] = [
                $sid,
                $student->admission_number,
                number_format($totalInvoiced, 2, '.', ''),
                number_format($paidReal, 2, '.', ''),
                number_format($finalBalance, 2, '.', ''),
                $hasCarryItems ? 'YES' : 'NO',
                $hasAllocOnReversedTransfers ? 'YES' : 'NO',
                $notes,
            ];
        }

        $csv = '';
        foreach ($rows as $r) {
            $csv .= implode(',', array_map(function ($v) {
                $v = (string) $v;
                if (str_contains($v, ',') || str_contains($v, '"') || str_contains($v, "\n")) {
                    $v = '"' . str_replace('"', '""', $v) . '"';
                }
                return $v;
            }, $r)) . "\n";
        }

        Storage::disk('local')->put($pathCsv, $csv);
        $this->info("Wrote: storage/app/{$pathCsv}");
        $this->info("Students checked: " . (count($rows) - 1));

        return 0;
    }
}

