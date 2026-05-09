<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseApproval;
use App\Models\ExpensePayment;
use App\Models\LedgerPosting;
use App\Models\PaymentVoucher;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseWorkflowService
{
    public function submit(Expense $expense): Expense
    {
        if ($expense->status !== Expense::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Only draft expenses can be submitted.');
        }

        if ($expense->lines()->count() === 0) {
            throw new \InvalidArgumentException('Expense must contain at least one line before submission.');
        }

        $expense->recalculateTotals();
        $expense->status = Expense::STATUS_SUBMITTED;
        $expense->submitted_at = now();
        $expense->save();

        return $expense->refresh();
    }

    public function decide(Expense $expense, User $approver, string $decision, ?string $remarks = null): Expense
    {
        if ($expense->status !== Expense::STATUS_SUBMITTED) {
            throw new \InvalidArgumentException('Only submitted expenses can be approved or rejected.');
        }

        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new \InvalidArgumentException('Decision must be approved or rejected.');
        }

        return DB::transaction(function () use ($expense, $approver, $decision, $remarks) {
            ExpenseApproval::create([
                'expense_id' => $expense->id,
                'approved_by' => $approver->id,
                'decision' => $decision,
                'decided_at' => now(),
                'remarks' => $remarks,
            ]);

            $expense->status = $decision === 'approved'
                ? Expense::STATUS_APPROVED
                : Expense::STATUS_REJECTED;
            $expense->approved_by = $approver->id;
            $expense->approved_at = now();
            $expense->save();

            return $expense->refresh();
        });
    }

    public function createVoucher(Expense $expense, User $preparedBy, array $attributes = []): PaymentVoucher
    {
        if ($expense->status !== Expense::STATUS_APPROVED) {
            throw new \InvalidArgumentException('Only approved expenses can be turned into vouchers.');
        }

        return PaymentVoucher::create([
            'expense_id' => $expense->id,
            'payee' => $attributes['payee'] ?? optional($expense->vendor)->name ?? 'Direct Expense',
            'payment_method' => $attributes['payment_method'] ?? null,
            'payment_date' => $attributes['payment_date'] ?? null,
            'amount' => $attributes['amount'] ?? $expense->total,
            'status' => 'approved',
            'prepared_by' => $preparedBy->id,
            'approved_by' => $preparedBy->id,
        ]);
    }

    public function payVoucher(PaymentVoucher $voucher, User $user, array $attributes): ExpensePayment
    {
        if (!in_array($voucher->status, ['approved', 'draft'], true)) {
            throw new \InvalidArgumentException('Voucher is not payable in current status.');
        }

        return DB::transaction(function () use ($voucher, $user, $attributes) {
            $payment = ExpensePayment::create([
                'voucher_id' => $voucher->id,
                'reference_no' => $attributes['reference_no'] ?? null,
                'account_source' => $attributes['account_source'] ?? null,
                'amount' => $attributes['amount'] ?? $voucher->amount,
                'paid_at' => $attributes['paid_at'] ?? now(),
                'recorded_by' => $user->id,
            ]);

            $voucher->status = 'paid';
            $voucher->payment_date = $payment->paid_at;
            $voucher->save();

            $expense = $voucher->expense;
            $expense->status = Expense::STATUS_PAID;
            $expense->save();

            $this->postBasicLedgerEntries($expense, $voucher, $payment);

            return $payment;
        });
    }

    protected function postBasicLedgerEntries(Expense $expense, PaymentVoucher $voucher, ExpensePayment $payment): void
    {
        $postingDate = $payment->paid_at?->toDateString() ?? now()->toDateString();

        LedgerPosting::create([
            'source_type' => 'expense_payment',
            'source_id' => $payment->id,
            'account_code' => 'EXPENSE',
            'dr_cr' => 'dr',
            'amount' => $payment->amount,
            'posting_date' => $postingDate,
        ]);

        LedgerPosting::create([
            'source_type' => 'expense_payment',
            'source_id' => $payment->id,
            'account_code' => 'CASH_BANK',
            'dr_cr' => 'cr',
            'amount' => $payment->amount,
            'posting_date' => $postingDate,
        ]);
    }
}
