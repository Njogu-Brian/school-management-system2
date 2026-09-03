<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;

/**
 * Student fee ledger: charges on one side, payments on the other.
 *
 * Invoice line items describe what was billed. Payments are not glued to
 * those lines. Balance is always charged minus paid (oldest invoice first).
 *
 * A credit note or extra charge moves the balance automatically because it
 * changes the charge side. A new payment moves it because it changes the
 * payment side. No leftover "attachment" can cling to a votehead.
 */
class StudentFeeLedgerService
{
    /** @var array<int, true> */
    private static array $syncing = [];

    /** @var array<string, array> */
    private array $snapshotCache = [];

    public static function isSyncing(int $studentId): bool
    {
        return isset(self::$syncing[$studentId]);
    }

    public static function isFeePayment(Payment $payment): bool
    {
        if ($payment->reversed) {
            return false;
        }
        if ($payment->deleted_at) {
            return false;
        }
        $receipt = (string) ($payment->receipt_number ?? '');
        if ($receipt !== '' && str_starts_with($receipt, 'SWIM-')) {
            return false;
        }

        return true;
    }

    /**
     * Persist invoice paid/balance/status and payment allocated/unallocated
     * from charges vs payments.
     */
    public function syncStudent(int $studentId): array
    {
        if ($studentId <= 0) {
            return $this->emptySnapshot();
        }
        if (self::isSyncing($studentId)) {
            return $this->snapshotCache[(string) $studentId] ?? $this->emptySnapshot();
        }

        self::$syncing[$studentId] = true;
        try {
            $snapshot = $this->compute($studentId, false);
            $this->persist($snapshot);
            app(StudentFeeStatementService::class)->persistPaymentSnapshots($studentId);
            $this->forgetCache($studentId);

            return $snapshot;
        } finally {
            unset(self::$syncing[$studentId]);
        }
    }

    /**
     * @return array{
     *   charged: float,
     *   paid: float,
     *   balance: float,
     *   credit: float,
     *   invoices: array<int, array{total: float, paid_amount: float, balance: float, status: string, due: bool}>
     * }
     */
    public function snapshot(int $studentId, bool $dueOnly = false): array
    {
        $key = $studentId . ':' . ($dueOnly ? '1' : '0');
        if (isset($this->snapshotCache[$key])) {
            return $this->snapshotCache[$key];
        }

        $snapshot = $this->compute($studentId, $dueOnly);
        $this->snapshotCache[$key] = $snapshot;

        return $snapshot;
    }

    public function forgetCache(?int $studentId = null): void
    {
        if ($studentId === null) {
            $this->snapshotCache = [];
            return;
        }
        unset($this->snapshotCache[(string) $studentId], $this->snapshotCache[$studentId . ':0'], $this->snapshotCache[$studentId . ':1']);
    }

    private function emptySnapshot(): array
    {
        return [
            'charged' => 0.0,
            'paid' => 0.0,
            'balance' => 0.0,
            'credit' => 0.0,
            'invoices' => [],
            'payments' => [],
        ];
    }

    private function compute(int $studentId, bool $dueOnly): array
    {
        $invoices = Invoice::query()
            ->where('student_id', $studentId)
            ->where('status', '!=', 'reversed')
            ->with(['items' => function ($q) {
                $q->where('status', 'active');
            }, 'term'])
            ->orderBy('issued_date')
            ->orderBy('id')
            ->get();

        $payments = Payment::query()
            ->where('student_id', $studentId)
            ->where('reversed', false)
            ->where(function ($q) {
                $q->whereNull('receipt_number')
                    ->orWhere('receipt_number', 'not like', 'SWIM-%');
            })
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (Payment $p) => self::isFeePayment($p))
            ->values();

        $invoiceState = [];
        $charged = 0.0;
        foreach ($invoices as $invoice) {
            $total = $this->invoiceChargeTotal($invoice);
            $due = $this->invoiceIsDue($invoice);
            $invoiceState[(int) $invoice->id] = [
                'model' => $invoice,
                'total' => $total,
                'remaining' => $total,
                'due' => $due,
            ];
            if (! $dueOnly || $due) {
                $charged += $total;
            }
        }

        $paid = 0.0;
        $paymentState = [];
        foreach ($payments as $payment) {
            $left = round((float) $payment->amount, 2);
            $applied = 0.0;
            foreach ($invoiceState as $id => $state) {
                if ($left <= 0.009) {
                    break;
                }
                if ($state['remaining'] <= 0.009) {
                    continue;
                }
                $take = round(min($left, $state['remaining']), 2);
                $invoiceState[$id]['remaining'] = round($state['remaining'] - $take, 2);
                $left = round($left - $take, 2);
                $applied = round($applied + $take, 2);
            }
            $paid = round($paid + (float) $payment->amount, 2);
            $paymentState[(int) $payment->id] = [
                'model' => $payment,
                'allocated_amount' => $applied,
                'unallocated_amount' => max(0.0, round((float) $payment->amount - $applied, 2)),
            ];
        }

        $invoicesOut = [];
        $dueOutstanding = 0.0;
        foreach ($invoiceState as $id => $state) {
            $total = $state['total'];
            $remaining = max(0.0, $state['remaining']);
            $paidAmount = max(0.0, round($total - $remaining, 2));
            if ($paidAmount > $total) {
                $paidAmount = $total;
                $remaining = 0.0;
            }
            $status = $remaining <= 0.009 ? 'paid' : ($paidAmount > 0.009 ? 'partial' : 'unpaid');
            $invoicesOut[$id] = [
                'total' => $total,
                'paid_amount' => $paidAmount,
                'balance' => $remaining,
                'status' => $status,
                'due' => $state['due'],
            ];
            if ($state['due']) {
                $dueOutstanding = round($dueOutstanding + $remaining, 2);
            }
        }

        $allBalance = round($this->sumInvoiceTotals($invoicesOut) - $paid, 2);
        $credit = max(0.0, round($paid - $this->sumInvoiceTotals($invoicesOut), 2));

        return [
            'charged' => round($dueOnly ? $charged : $this->sumInvoiceTotals($invoicesOut), 2),
            'paid' => $paid,
            'balance' => $dueOnly ? $dueOutstanding : $allBalance,
            'credit' => $credit,
            'invoices' => $invoicesOut,
            'payments' => $paymentState,
        ];
    }

    private function persist(array $snapshot): void
    {
        foreach ($snapshot['invoices'] as $invoiceId => $row) {
            Invoice::where('id', $invoiceId)->update([
                'total' => $row['total'],
                'paid_amount' => $row['paid_amount'],
                'balance' => $row['balance'],
                'status' => $row['status'],
                'updated_at' => now(),
            ]);
        }

        foreach ($snapshot['payments'] as $paymentId => $row) {
            Payment::where('id', $paymentId)->update([
                'allocated_amount' => $row['allocated_amount'],
                'unallocated_amount' => $row['unallocated_amount'],
                'updated_at' => now(),
            ]);
        }
    }

    public function invoiceChargeTotal(Invoice $invoice): float
    {
        $items = $invoice->relationLoaded('items')
            ? $invoice->items->filter(fn ($item) => ($item->status ?? 'active') === 'active' && empty($item->deleted_at))
            : $invoice->items()->where('status', 'active')->get();

        if ($items->isNotEmpty()) {
            $net = (float) $items->sum(function ($item) {
                return (float) $item->amount - (float) ($item->discount_amount ?? 0);
            });

            return max(0.0, round($net - (float) ($invoice->discount_amount ?? 0), 2));
        }

        return max(0.0, round((float) ($invoice->total ?? 0), 2));
    }

    public function invoiceIsDue(Invoice $invoice): bool
    {
        $today = now()->toDateString();
        if ($invoice->due_date) {
            return $invoice->due_date->toDateString() <= $today;
        }

        $term = $invoice->relationLoaded('term') ? $invoice->getRelation('term') : null;
        if (! is_object($term)) {
            $term = $invoice->term()->first();
        }
        if (! $term) {
            return true;
        }
        if (empty($term->opening_date)) {
            return true;
        }

        return $term->opening_date <= $today;
    }

    private function sumInvoiceTotals(array $invoicesOut): float
    {
        $sum = 0.0;
        foreach ($invoicesOut as $row) {
            $sum += $row['total'];
        }

        return round($sum, 2);
    }
}
