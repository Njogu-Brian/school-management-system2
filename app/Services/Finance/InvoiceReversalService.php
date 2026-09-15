<?php

namespace App\Services\Finance;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\FeeClearanceRecomputeService;
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
     * Allocations on this invoice are removed. The payments themselves stay
     * on the student as credit (they are not reversed).
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

        return DB::transaction(function () use ($invoice, $reason, $userId) {
            $itemIds = InvoiceItem::withTrashed()
                ->where('invoice_id', $invoice->id)
                ->pluck('id');

            $allocations = $itemIds->isEmpty()
                ? collect()
                : PaymentAllocation::query()
                    ->whereIn('invoice_item_id', $itemIds)
                    ->with('payment')
                    ->get();

            $affectedPayments = $allocations
                ->map(fn (PaymentAllocation $a) => $a->payment)
                ->filter(fn ($payment) => $payment instanceof Payment)
                ->unique(fn (Payment $payment) => $payment->id)
                ->values();

            $directPayments = Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('reversed', false)
                ->get();

            $affectedPayments = $affectedPayments
                ->concat($directPayments)
                ->unique(fn (Payment $payment) => $payment->id)
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

            $this->logInvoiceReversal($invoice->fresh(), $oldValues, $affectedPayments);

            $unallocatedCount = $affectedPayments->count();
            $message = 'Invoice reversed.';
            if ($unallocatedCount > 0) {
                $message .= " {$unallocatedCount} payment(s) unallocated from this invoice and kept as credit.";
            }

            return [
                'payments_reversed' => 0,
                'payments_unallocated' => $unallocatedCount,
                'message' => $message,
            ];
        });
    }

    private function logInvoiceReversal(Invoice $invoice, array $oldValues, Collection $paymentsUnallocated): void
    {
        try {
            AuditLog::log('invoice_reversed', $invoice, $oldValues, [
                'status' => 'reversed',
                'reversed_by' => $invoice->reversed_by,
                'reversed_at' => optional($invoice->reversed_at)->toDateTimeString(),
                'reversal_reason' => $invoice->reversal_reason,
                'payments_unallocated' => $paymentsUnallocated->pluck('id')->all(),
            ], ['financial', 'invoice', 'reversal']);
        } catch (\Throwable $e) {
            Log::warning('Failed to log invoice reversal audit', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
