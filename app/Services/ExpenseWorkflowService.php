<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseApproval;
use App\Models\ExpensePayment;
use App\Models\PaymentVoucher;
use App\Models\User;
use App\Services\Finance\JournalPostingService;
use Illuminate\Support\Facades\DB;

class ExpenseWorkflowService
{
    public function __construct(
        protected JournalPostingService $journalPosting,
    ) {}

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

        if (! in_array($decision, ['approved', 'rejected'], true)) {
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
        if (! in_array($voucher->status, ['approved', 'draft'], true)) {
            throw new \InvalidArgumentException('Voucher is not payable in current status.');
        }

        return DB::transaction(function () use ($voucher, $user, $attributes) {
            $creditAccount = $this->resolvePaymentAccount($attributes);

            $payment = ExpensePayment::create([
                'voucher_id' => $voucher->id,
                'reference_no' => $attributes['reference_no'] ?? null,
                'account_source' => $attributes['account_source'] ?? null,
                'bank_account_id' => $attributes['bank_account_id'] ?? null,
                'account_id' => $creditAccount->id,
                'amount' => $attributes['amount'] ?? $voucher->amount,
                'paid_at' => $attributes['paid_at'] ?? now(),
                'recorded_by' => $user->id,
            ]);

            $voucher->status = 'paid';
            $voucher->payment_date = $payment->paid_at;
            $voucher->save();

            $expense = $voucher->expense()->with(['lines.category.account'])->firstOrFail();
            $expense->status = Expense::STATUS_PAID;
            $expense->save();

            $journalEntry = $this->postExpensePaymentJournal($expense, $voucher, $payment, $creditAccount, $user);
            $voucher->journal_entry_id = $journalEntry->id;
            $voucher->save();

            return $payment;
        });
    }

    protected function postExpensePaymentJournal(
        Expense $expense,
        PaymentVoucher $voucher,
        ExpensePayment $payment,
        $creditAccount,
        User $user,
    ) {
        $lines = [];
        $defaultExpenseAccount = $this->journalPosting->systemAccount('5999');

        foreach ($expense->lines as $line) {
            $amount = (float) $line->line_total;
            if ($amount <= 0) {
                continue;
            }

            $expenseAccount = $line->category?->account ?? $defaultExpenseAccount;
            $lines[] = [
                'account_id' => $expenseAccount->id,
                'debit' => $amount,
                'description' => $line->description,
            ];
        }

        if ($lines === []) {
            $lines[] = [
                'account_id' => $defaultExpenseAccount->id,
                'debit' => (float) $payment->amount,
                'description' => $expense->notes ?? 'Expense payment',
            ];
        }

        $lines[] = [
            'account_id' => $creditAccount->id,
            'credit' => (float) $payment->amount,
            'description' => 'Payment for ' . $expense->expense_no,
        ];

        return $this->journalPosting->post(
            $lines,
            'Payment voucher ' . $voucher->voucher_no . ' — ' . $expense->expense_no,
            $payment->paid_at ?? now(),
            'expense_payment',
            $payment->id,
            $user,
        );
    }

    protected function resolvePaymentAccount(array $attributes)
    {
        if (! empty($attributes['account_id'])) {
            return \App\Models\Account::findOrFail($attributes['account_id']);
        }

        if (! empty($attributes['bank_account_id'])) {
            $bank = \App\Models\BankAccount::with('account')->find($attributes['bank_account_id']);
            if ($bank?->account) {
                return $bank->account;
            }

            return $this->journalPosting->systemAccount('1011');
        }

        $source = strtolower((string) ($attributes['account_source'] ?? ''));

        if (str_contains($source, 'petty') || str_contains($source, 'cash')) {
            return $this->journalPosting->systemAccount('1000');
        }

        return $this->journalPosting->systemAccount('1010');
    }
}
