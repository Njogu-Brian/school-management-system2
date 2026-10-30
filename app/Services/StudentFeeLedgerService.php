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

    /**
     * How this payment was applied onto non-reversed invoices (oldest first).
     *
     * @return array<int, array{invoice_id: int, amount: float}>
     */
    public function applicationsForPayment(Payment $payment): array
    {
        if ((int) $payment->student_id <= 0 || ! self::isFeePayment($payment)) {
            return [];
        }

        $snapshot = $this->snapshot((int) $payment->student_id, false);

        return $snapshot['applications'][(int) $payment->id] ?? [];
    }

    /**
     * Running fee balance before/after each fee payment (ledger model).
     *
     * Payments are applied FIFO to all invoices. Receipt "fee balance" after each
     * payment is due/overdue outstanding only as of that payment date — upcoming
     * (not-yet-due) invoices never count as fee balance.
     *
     * @return array<int, array{balance_before: float, balance_after: float}>
     */
    public function runningBalancesForStudent(int $studentId): array
    {
        if ($studentId <= 0) {
            return [];
        }

        $invoices = Invoice::query()
            ->where('student_id', $studentId)
            ->notReversed()
            ->with(['items' => function ($q) {
                $q->where('status', 'active');
            }, 'term'])
            ->orderBy('issued_date')
            ->orderBy('id')
            ->get();

        $remaining = [];
        foreach ($invoices as $invoice) {
            $remaining[(int) $invoice->id] = $this->invoiceChargeTotal($invoice);
        }

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

        $out = [];
        foreach ($payments as $payment) {
            $asOf = $payment->payment_date
                ? $payment->payment_date->toDateString()
                : now()->toDateString();

            $before = $this->sumDueRemaining($invoices, $remaining, $asOf);

            $left = round((float) $payment->amount, 2);
            foreach ($invoices as $invoice) {
                if ($left <= 0.009) {
                    break;
                }
                $id = (int) $invoice->id;
                if (($remaining[$id] ?? 0) <= 0.009) {
                    continue;
                }
                $take = round(min($left, $remaining[$id]), 2);
                $remaining[$id] = round($remaining[$id] - $take, 2);
                $left = round($left - $take, 2);
            }

            $after = $this->sumDueRemaining($invoices, $remaining, $asOf);
            $out[(int) $payment->id] = [
                'balance_before' => $before,
                'balance_after' => $after,
            ];
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Invoice>  $invoices
     * @param  array<int, float>  $remaining
     */
    private function sumDueRemaining($invoices, array $remaining, string $asOfDate): float
    {
        $sum = 0.0;
        foreach ($invoices as $invoice) {
            if (! $this->invoiceIsDue($invoice, $asOfDate)) {
                continue;
            }
            $sum = round($sum + max(0.0, (float) ($remaining[(int) $invoice->id] ?? 0)), 2);
        }

        return $sum;
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
            'applications' => [],
        ];
    }

    private function compute(int $studentId, bool $dueOnly): array
    {
        $invoices = Invoice::query()
            ->where('student_id', $studentId)
            ->notReversed()
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
        $applications = [];
        foreach ($payments as $payment) {
            $left = round((float) $payment->amount, 2);
            $applied = 0.0;
            $paymentId = (int) $payment->id;
            $applications[$paymentId] = [];
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
                if ($take > 0.009) {
                    $applications[$paymentId][] = [
                        'invoice_id' => (int) $id,
                        'amount' => $take,
                    ];
                }
            }
            $paid = round($paid + (float) $payment->amount, 2);
            $paymentState[$paymentId] = [
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
            'applications' => $applications,
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

    /**
     * @param  \DateTimeInterface|string|null  $asOf  Defaults to today
     */
    public function invoiceIsDue(Invoice $invoice, $asOf = null): bool
    {
        $asOfDate = $asOf instanceof \DateTimeInterface
            ? $asOf->format('Y-m-d')
            : (is_string($asOf) && $asOf !== '' ? substr($asOf, 0, 10) : now()->toDateString());

        if ($invoice->due_date) {
            return $invoice->due_date->toDateString() <= $asOfDate;
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

        $opening = $term->opening_date instanceof \DateTimeInterface
            ? $term->opening_date->format('Y-m-d')
            : (string) $term->opening_date;

        return $opening <= $asOfDate;
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
