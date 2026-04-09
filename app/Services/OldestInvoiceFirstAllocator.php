<?php

namespace App\Services;

use App\Models\{Invoice, InvoiceItem, Payment, Student};
use Illuminate\Support\Collection;

/**
 * Allocate existing payments strictly to oldest invoice first.
 *
 * This is used after reversing internal carry-forward transfers so that:
 * - Term 1 invoice stays visible and is cleared first
 * - Then Term 2 invoice, etc.
 *
 * This allocator never creates new payments.
 */
class OldestInvoiceFirstAllocator
{
    public function __construct(
        private readonly PaymentAllocationService $allocationService,
    ) {}

    /**
     * @param Payment $payment Real (non-internal-transfer) payment
     * @param Collection<int,Invoice> $invoices Oldest-to-newest invoices
     */
    public function allocatePaymentAcrossInvoices(Payment $payment, Collection $invoices): void
    {
        $alreadyAllocated = (float) $payment->allocations()->sum('amount');
        $remaining = (float) $payment->amount - $alreadyAllocated;
        if ($remaining <= 0.0001) {
            return;
        }

        $allocations = [];

        foreach ($invoices as $invoice) {
            if ($remaining <= 0.0001) {
                break;
            }

            $items = InvoiceItem::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', 'active')
                ->with('votehead')
                ->get()
                ->filter(fn (InvoiceItem $i) => $i->getBalance() > 0.0001)
                // within invoice: stable ordering by id (oldest items first)
                ->sortBy('id')
                ->values();

            foreach ($items as $item) {
                if ($remaining <= 0.0001) {
                    break;
                }
                $balance = (float) $item->getBalance();
                if ($balance <= 0.0001) {
                    continue;
                }
                $amt = min($remaining, $balance);
                if ($amt <= 0.0001) {
                    continue;
                }
                $allocations[] = ['invoice_item_id' => $item->id, 'amount' => round($amt, 2)];
                $remaining -= $amt;
            }
        }

        if ($allocations !== []) {
            $this->allocationService->allocatePayment($payment, $allocations);
        } else {
            $payment->updateAllocationTotals();
        }
    }

    /**
     * Collect invoices for a student up to a year/term scope and return oldest-to-newest.
     */
    public function collectInvoicesOldestFirst(int $studentId, int $year, int $upToTerm): Collection
    {
        return Invoice::query()
            ->where('student_id', $studentId)
            ->where('status', '!=', 'reversed')
            ->where('year', $year)
            ->where('term', '<=', $upToTerm)
            ->orderBy('year')
            ->orderBy('term')
            ->orderBy('issued_date')
            ->orderBy('id')
            ->get();
    }
}

