<?php

namespace App\Services;

use App\Models\ActivityFeeAllocation;
use App\Models\BankStatementTransaction;
use App\Models\ExtraIncomeItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ActivityFeeSplitService
{
    public function __construct(
        protected ExtraIncomeService $extraIncome,
        protected PaymentAllocationService $allocationService,
        protected SwimmingTransactionService $swimmingTransactions,
        protected SwimmingWalletService $swimmingWallets,
    ) {
    }

    public function alreadySplit(object $transaction, bool $isC2B): bool
    {
        if (!Schema::hasTable('activity_fee_allocations')) {
            return false;
        }

        return ActivityFeeAllocation::query()
            ->when(
                $isC2B,
                fn ($q) => $q->where('mpesa_c2b_transaction_id', $transaction->id),
                fn ($q) => $q->where('bank_statement_transaction_id', $transaction->id)
            )
            ->where('status', '!=', ActivityFeeAllocation::STATUS_REVERSED)
            ->exists();
    }

    /**
     * @param  array<int, array{student_id:int, extra_income_item_id:int, amount:float}>  $allocations
     */
    public function apply(object $transaction, bool $isC2B, array $allocations): float
    {
        $swimming = [];
        $charges = [];

        foreach ($allocations as $row) {
            $item = ExtraIncomeItem::with('classroom')->findOrFail($row['extra_income_item_id']);
            if (!$item->is_active) {
                throw new \RuntimeException("{$item->name} is not active.");
            }

            $student = Student::findOrFail($row['student_id']);
            $amount = round((float) $row['amount'], 2);
            if ($amount <= 0) {
                continue;
            }

            if ($item->classroom_id && (int) $student->classroom_id !== (int) $item->classroom_id) {
                $className = $item->classroom?->name ?? 'the scheduled class';
                throw new \RuntimeException("{$student->full_name} is not in {$className}, so this payment cannot go to {$item->name}.");
            }

            $packed = [
                'item' => $item,
                'student' => $student,
                'student_id' => $student->id,
                'amount' => $amount,
            ];

            if ($item->isSwimming()) {
                $swimming[] = $packed;
            } else {
                $charges[] = $packed;
            }
        }

        $total = 0.0;

        if ($swimming !== []) {
            $total += $this->applySwimming($transaction, $isC2B, $swimming);
        }

        foreach ($charges as $row) {
            $total += $this->applyCharge($transaction, $isC2B, $row);
        }

        return round($total, 2);
    }

    /**
     * @param  array<int, array{item: ExtraIncomeItem, student: Student, student_id:int, amount:float}>  $rows
     */
    protected function applySwimming(object $transaction, bool $isC2B, array $rows): float
    {
        $total = 0.0;
        $walletRows = [];

        foreach ($rows as $row) {
            $walletRows[] = [
                'student_id' => $row['student_id'],
                'amount' => $row['amount'],
            ];
            $total += $row['amount'];
        }

        if (!$isC2B) {
            if (!$transaction instanceof BankStatementTransaction) {
                throw new \RuntimeException('Swimming split expects a bank statement transaction.');
            }
            $this->swimmingTransactions->allocateSplitAndProcess($transaction, $walletRows);
        } else {
            $ref = $transaction->trans_id ?? $transaction->id;
            foreach ($rows as $row) {
                $this->swimmingWallets->creditFromBankTransaction(
                    $row['student'],
                    $transaction,
                    $row['amount'],
                    "Swimming split from M-PESA #{$ref} ({$row['item']->name})"
                );
            }
        }

        foreach ($rows as $row) {
            $this->record($transaction, $isC2B, $row, null, null, 'Credited to the swimming wallet.');
        }

        return round($total, 2);
    }

    /**
     * @param  array{item: ExtraIncomeItem, student: Student, student_id:int, amount:float}  $row
     */
    protected function applyCharge(object $transaction, bool $isC2B, array $row): float
    {
        /** @var ExtraIncomeItem $item */
        $item = $row['item'];
        /** @var Student $student */
        $student = $row['student'];
        $amount = $row['amount'];

        if (!$item->votehead_id) {
            $this->extraIncome->ensureVotehead($item);
            $item->refresh();
        }

        $line = $this->extraIncome->ensureStudentCharge($item, $student, $amount);
        $payment = $this->createActivityPayment($transaction, $isC2B, $student, $item, $amount);

        $this->allocationService->allocatePayment($payment, [[
            'invoice_item_id' => $line->id,
            'amount' => $amount,
        ]]);

        $this->record($transaction, $isC2B, $row, $payment->id, $line->id, "Allocated to {$item->name}.");

        return $amount;
    }

    protected function createActivityPayment(
        object $transaction,
        bool $isC2B,
        Student $student,
        ExtraIncomeItem $item,
        float $amount
    ): Payment {
        $ref = $isC2B
            ? (string) ($transaction->trans_id ?? ('C2B' . $transaction->id))
            : (string) ($transaction->reference_number ?? ('BANK' . $transaction->id));

        $transactionCode = $ref . '-ACT-' . $item->id;

        $existing = Payment::query()
            ->where('transaction_code', $transactionCode)
            ->where('student_id', $student->id)
            ->where('reversed', false)
            ->first();

        if ($existing) {
            throw new \RuntimeException("{$student->full_name} already has an activity payment for {$item->name} on this transaction.");
        }

        if ($isC2B) {
            return Payment::create([
                'student_id' => $student->id,
                'family_id' => $student->family_id,
                'amount' => $amount,
                'unallocated_amount' => $amount,
                'allocated_amount' => 0,
                'payment_method' => 'mpesa',
                'payment_date' => $transaction->trans_time,
                'receipt_number' => ReceiptNumberService::generateForPayment(),
                'transaction_code' => $transactionCode,
                'payer_name' => $transaction->full_name ?? $student->full_name,
                'payer_type' => 'parent',
                'narration' => 'Activity fee: ' . $item->name . ' (split from ' . $ref . ')',
            ]);
        }

        $paymentMethodName = ($transaction->bank_type ?? '') === 'mpesa' ? 'MPESA Paybill' : 'Equity Bank Transfer';
        $paymentMethod = PaymentMethod::where('name', $paymentMethodName)->first();

        return Payment::create([
            'student_id' => $student->id,
            'family_id' => $student->family_id,
            'amount' => $amount,
            'unallocated_amount' => $amount,
            'allocated_amount' => 0,
            'payment_method_id' => $paymentMethod?->id,
            'payment_method' => $paymentMethodName,
            'transaction_code' => $transactionCode,
            'receipt_number' => ReceiptNumberService::generateForPayment(),
            'payer_name' => $transaction->payer_name ?? $student->full_name,
            'payer_type' => 'parent',
            'narration' => 'Activity fee: ' . $item->name . ' (split from ' . $ref . ')',
            'payment_date' => $transaction->transaction_date,
        ]);
    }

    /**
     * @param  array{item: ExtraIncomeItem, student: Student, amount:float}  $row
     */
    protected function record(
        object $transaction,
        bool $isC2B,
        array $row,
        ?int $paymentId,
        ?int $invoiceItemId,
        string $notes
    ): void {
        ActivityFeeAllocation::create([
            'extra_income_item_id' => $row['item']->id,
            'student_id' => $row['student']->id,
            'bank_statement_transaction_id' => $isC2B ? null : $transaction->id,
            'mpesa_c2b_transaction_id' => $isC2B ? $transaction->id : null,
            'payment_id' => $paymentId,
            'invoice_item_id' => $invoiceItemId,
            'amount' => $row['amount'],
            'status' => ActivityFeeAllocation::STATUS_ALLOCATED,
            'notes' => $notes,
            'allocated_by' => Auth::id(),
            'allocated_at' => now(),
        ]);
    }
}
