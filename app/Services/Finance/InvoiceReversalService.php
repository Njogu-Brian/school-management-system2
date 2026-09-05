<?php

namespace App\Services\Finance;

use App\Models\AuditLog;
use App\Models\BankStatementTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\FeeClearanceRecomputeService;
use App\Services\FinancialAuditService;
use App\Services\PaymentPlanSyncService;
use App\Services\StudentFeeLedgerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceReversalService
{
    /**
     * Fully reverse an invoice.
     *
     * Allocations on this invoice are removed. A payment that was only allocated
     * here is reversed. A payment that also covers other invoices is unallocated
     * from this invoice only and stays active.
     *
     * @return array{payments_reversed: int, payments_unallocated: int, message: string}
     */
    public function reverse(Invoice $invoice, string $reason, ?User $user = null): array
    {
        if ($invoice->isReversed()) {
            throw new \RuntimeException('This invoice has already been reversed.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('A reversal reason is required.');
        }

        $userId = $user?->id ?? auth()->id();

        return DB::transaction(function () use ($invoice, $reason, $user, $userId) {
            $invoice->load(['items.allocations.payment']);

            $allocations = PaymentAllocation::query()
                ->whereHas('invoiceItem', fn ($q) => $q->where('invoice_id', $invoice->id))
                ->with('payment')
                ->lockForUpdate()
                ->get();

            $affectedPayments = $allocations
                ->pluck('payment')
                ->filter()
                ->unique('id')
                ->values();

            $directPayments = Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('reversed', false)
                ->lockForUpdate()
                ->get();

            $affectedPayments = $affectedPayments
                ->concat($directPayments)
                ->unique('id')
                ->values();

            $oldValues = [
                'status' => $invoice->status,
                'total' => $invoice->total,
                'paid_amount' => $invoice->paid_amount,
                'balance' => $invoice->balance,
                'payment_ids' => $affectedPayments->pluck('id')->all(),
                'allocation_amounts' => $allocations->map(fn (PaymentAllocation $a) => [
                    'payment_id' => $a->payment_id,
                    'invoice_item_id' => $a->invoice_item_id,
                    'amount' => (float) $a->amount,
                ])->all(),
            ];

            // Reverse the invoice first so allocation deletes cannot re-apply money here.
            $invoice->forceFill([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by' => $userId,
                'reversal_reason' => $reason,
                'paid_amount' => 0,
                'balance' => 0,
            ])->saveQuietly();

            foreach ($allocations as $allocation) {
                $allocation->delete();
            }

            Payment::query()
                ->where('invoice_id', $invoice->id)
                ->update(['invoice_id' => null]);

            $paymentsReversed = collect();
            $paymentsUnallocatedOnly = collect();

            foreach ($affectedPayments as $payment) {
                $payment = $payment->fresh();
                if (! $payment || $payment->reversed) {
                    continue;
                }

                $remainingAllocations = $payment->allocations()->count();
                if ($remainingAllocations === 0) {
                    $this->reversePayment($payment, $reason, $user);
                    $paymentsReversed->push($payment);
                } else {
                    $paymentsUnallocatedOnly->push($payment);
                }
            }

            $studentId = (int) $invoice->student_id;
            if ($studentId > 0) {
                app(StudentFeeLedgerService::class)->syncStudent($studentId);
                try {
                    $clearance = app(FeeClearanceRecomputeService::class);
                    if ($invoice->term_id) {
                        $clearance->recomputeForStudentTerm(
                            Student::find($studentId),
                            Term::find($invoice->term_id)
                        );
                    }
                    $clearance->recomputeAllTermsForStudent($studentId);
                } catch (\Throwable $e) {
                    Log::warning('Fee clearance recompute after invoice reversal failed', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            try {
                app(PaymentPlanSyncService::class)->syncPlansForInvoice($invoice->fresh());
            } catch (\Throwable $e) {
                Log::warning('Payment plan sync after invoice reversal failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->logInvoiceReversal($invoice->fresh(), $oldValues, $paymentsReversed, $paymentsUnallocatedOnly);

            $reversedCount = $paymentsReversed->count();
            $unallocatedCount = $paymentsUnallocatedOnly->count() + $reversedCount;

            $message = 'Invoice reversed.';
            if ($unallocatedCount > 0) {
                $message .= " {$unallocatedCount} payment(s) unallocated from this invoice.";
            }
            if ($reversedCount > 0) {
                $message .= " {$reversedCount} payment(s) reversed because they were only allocated to this invoice.";
            }

            return [
                'payments_reversed' => $reversedCount,
                'payments_unallocated' => $unallocatedCount,
                'message' => $message,
            ];
        });
    }

    private function reversePayment(Payment $payment, string $reason, ?User $user = null): void
    {
        if ($payment->reversed) {
            return;
        }

        $oldValues = [
            'reversed' => false,
            'amount' => $payment->amount,
            'allocated_amount' => $payment->allocated_amount,
        ];

        foreach ($payment->allocations as $allocation) {
            $allocation->delete();
        }

        $payment->update([
            'reversed' => true,
            'reversed_by' => $user?->id ?? auth()->id(),
            'reversed_at' => now(),
            'reversal_reason' => $reason,
            'allocated_amount' => 0,
            'unallocated_amount' => 0,
            'invoice_id' => null,
        ]);
        $payment->increment('version');

        try {
            app(FeePaymentPostingService::class)->reverse($payment->fresh(), $user ?? auth()->user());
        } catch (\Throwable $e) {
            Log::warning('Fee GL reversal failed during invoice reversal', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            FinancialAuditService::logPaymentReversal($payment->fresh(), $oldValues);
        } catch (\Throwable $e) {
            Log::warning('Failed to log payment reversal during invoice reversal', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->unlinkBankStatementTransactions($payment->fresh());
    }

    private function unlinkBankStatementTransactions(Payment $payment): void
    {
        $directLinked = BankStatementTransaction::query()
            ->where('payment_id', $payment->id)
            ->get();

        $referenceLinked = collect();
        if ($payment->transaction_code) {
            $possibleRefs = collect([$payment->transaction_code]);
            if (preg_match('/^(.*)-\d+$/', $payment->transaction_code, $matches)) {
                $possibleRefs->push($matches[1]);
            }
            $referenceLinked = BankStatementTransaction::query()
                ->whereIn('reference_number', $possibleRefs->unique()->values()->all())
                ->get();
        }

        $bankTransactions = $directLinked->merge($referenceLinked)->unique('id');

        foreach ($bankTransactions as $bankTransaction) {
            $transactionReference = $bankTransaction->reference_number ?? $payment->transaction_code;
            $remainingPayments = 0;
            if ($transactionReference) {
                $remainingPayments = Payment::query()
                    ->where('reversed', false)
                    ->where(function ($q) use ($transactionReference) {
                        $q->where('transaction_code', $transactionReference)
                            ->orWhere('transaction_code', 'LIKE', $transactionReference . '-%');
                    })
                    ->count();
            }

            $transactionPaymentReversed = false;
            if ($bankTransaction->payment_id) {
                $linked = Payment::find($bankTransaction->payment_id);
                $transactionPaymentReversed = $linked && $linked->reversed;
            }

            if ($remainingPayments === 0 || $transactionPaymentReversed) {
                $bankTransaction->update([
                    'payment_created' => false,
                    'payment_id' => null,
                ]);
                $bankTransaction->increment('version');
            }
        }
    }

    private function logInvoiceReversal(
        Invoice $invoice,
        array $oldValues,
        Collection $paymentsReversed,
        Collection $paymentsUnallocatedOnly
    ): void {
        try {
            AuditLog::log('invoice_reversed', $invoice, $oldValues, [
                'status' => 'reversed',
                'reversed_by' => $invoice->reversed_by,
                'reversed_at' => optional($invoice->reversed_at)->toDateTimeString(),
                'reversal_reason' => $invoice->reversal_reason,
                'payments_reversed' => $paymentsReversed->pluck('id')->all(),
                'payments_unallocated' => $paymentsUnallocatedOnly->pluck('id')->all(),
            ], ['financial', 'invoice', 'reversal']);
        } catch (\Throwable $e) {
            Log::warning('Failed to log invoice reversal audit', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
