<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DiagnoseSwimmingExcludedPayments extends Command
{
    protected $signature = 'finance:diagnose-swimming-excluded
                            {--student= : Admission number (e.g. RKS761) to check}
                            {--fix : Unmark source transaction from swimming so payment counts in statement}';

    protected $description = 'Find fee payments excluded from statement/comparison because source transaction is marked swimming. Use --fix to unmark.';

    public function handle(): int
    {
        $studentAdm = $this->option('student');
        $fix = $this->option('fix');

        $swimmingPaymentIds = $this->getSwimmingPaymentIds();
        if (empty($swimmingPaymentIds)) {
            $this->info('No swimming-linked payments found. Nothing to diagnose.');
            return 0;
        }

        // Payments that are in swimming list but have allocations to FEE invoice items (not swimming_attendance)
        $query = Payment::whereIn('id', $swimmingPaymentIds)
            ->where('reversed', false)
            ->whereHas('allocations', function ($q) {
                $q->whereHas('invoiceItem', function ($q2) {
                    $q2->where(function ($q3) {
                        $q3->whereNull('source')->orWhere('source', '!=', 'swimming_attendance');
                    });
                });
            });

        if ($studentAdm) {
            $query->whereHas('student', fn ($q) => $q->where('admission_number', $studentAdm));
        }

        $payments = $query->with(['student', 'allocations.invoiceItem.votehead'])->get();

        if ($payments->isEmpty()) {
            $this->info('No fee payments found that are incorrectly excluded (swimming-marked but allocated to fees).');
            return 0;
        }

        $this->warn('Found ' . $payments->count() . ' payment(s) allocated to FEES but excluded from statement (source marked swimming):');
        $this->line('');

        foreach ($payments as $payment) {
            $student = $payment->student;
            $studentLabel = $student ? "{$student->full_name} ({$student->admission_number})" : 'Unknown';
            $allocTotal = $payment->allocations->sum('amount');
            $this->line("  Payment #{$payment->id} | {$payment->receipt_number} | Ksh " . number_format($payment->amount, 2) . " | {$studentLabel}");
            $this->line("    Transaction: {$payment->transaction_code} | Allocated to fees: Ksh " . number_format($allocTotal, 2));

            // Find source
            $bankTxn = BankStatementTransaction::where('payment_id', $payment->id)->first();
            $c2bTxn = MpesaC2BTransaction::where('payment_id', $payment->id)->first();
            if ($bankTxn) {
                $this->line("    Source: Bank statement transaction #{$bankTxn->id} (is_swimming_transaction=true)");
                if ($fix) {
                    $bankTxn->update(['is_swimming_transaction' => false]);
                    $this->info("    [Fixed] Unmarked bank transaction #{$bankTxn->id} from swimming.");
                }
            } elseif ($c2bTxn) {
                $this->line("    Source: M-Pesa C2B transaction #{$c2bTxn->id} (is_swimming_transaction=true)");
                if ($fix) {
                    $c2bTxn->update(['is_swimming_transaction' => false]);
                    $this->info("    [Fixed] Unmarked C2B transaction #{$c2bTxn->id} from swimming.");
                }
            } else {
                $this->line("    Source: Unknown (no bank/c2b link with payment_id)");
            }
            $this->line('');
        }

        if ($fix && $payments->isNotEmpty()) {
            $this->info('Done. These payments will now count in Student Statement and Fees Comparison.');
        } else {
            $this->line('Run with --fix to unmark the source transactions from swimming.');
            if ($studentAdm) {
                $this->line('Run without --student= to check all students.');
            }
        }

        return 0;
    }

    private function getSwimmingPaymentIds(): array
    {
        $ids = collect();
        if (Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
            $ids = $ids->merge(
                BankStatementTransaction::where('is_swimming_transaction', true)
                    ->whereNotNull('payment_id')
                    ->pluck('payment_id')
            );
        }
        if (Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction')) {
            $ids = $ids->merge(
                MpesaC2BTransaction::where('is_swimming_transaction', true)
                    ->whereNotNull('payment_id')
                    ->pluck('payment_id')
            );
        }
        return $ids->unique()->filter()->values()->toArray();
    }
}
