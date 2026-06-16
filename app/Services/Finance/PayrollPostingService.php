<?php

namespace App\Services\Finance;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PayrollPostingService
{
    public function __construct(
        protected JournalPostingService $journalPosting,
    ) {}

    public function postAccrual(PayrollPeriod $period, ?User $user = null): void
    {
        if ($period->accrual_journal_entry_id) {
            return;
        }

        $period->loadMissing('records');

        $totalNet = round((float) $period->records->sum('net_salary'), 2);
        if ($totalNet <= 0) {
            return;
        }

        $expenseAccount = $this->journalPosting->systemAccount('5200');
        $payableAccount = $this->journalPosting->systemAccount('2100');

        $entry = $this->journalPosting->post(
            [
                ['account_id' => $expenseAccount->id, 'debit' => $totalNet, 'description' => 'Payroll accrual ' . $period->name],
                ['account_id' => $payableAccount->id, 'credit' => $totalNet, 'description' => 'Payroll accrual ' . $period->name],
            ],
            'Payroll accrual — ' . $period->name,
            $period->pay_date ?? $period->end_date ?? now(),
            'payroll_accrual',
            $period->id,
            $user ?? auth()->user(),
        );

        $period->accrual_journal_entry_id = $entry->id;
        $period->saveQuietly();
    }

    public function markPeriodPaid(PayrollPeriod $period, ?User $user = null, ?int $bankAccountId = null): void
    {
        if ($period->payment_journal_entry_id) {
            throw new \InvalidArgumentException('Payroll period has already been marked as paid.');
        }

        if (! $period->accrual_journal_entry_id) {
            $this->postAccrual($period, $user);
        }

        $period->refresh()->loadMissing('records');

        $totalNet = round((float) $period->records->sum('net_salary'), 2);
        if ($totalNet <= 0) {
            throw new \InvalidArgumentException('No payroll amount to pay.');
        }

        DB::transaction(function () use ($period, $user, $bankAccountId, $totalNet) {
            $payableAccount = $this->journalPosting->systemAccount('2100');
            $bankAccount = $this->resolveBankAccount($bankAccountId);

            $entry = $this->journalPosting->post(
                [
                    ['account_id' => $payableAccount->id, 'debit' => $totalNet, 'description' => 'Payroll payment ' . $period->name],
                    ['account_id' => $bankAccount->id, 'credit' => $totalNet, 'description' => 'Payroll payment ' . $period->name],
                ],
                'Payroll payment — ' . $period->name,
                $period->pay_date ?? now(),
                'payroll_payment',
                $period->id,
                $user ?? auth()->user(),
            );

            $period->payment_journal_entry_id = $entry->id;
            $period->paid_at = now();
            $period->save();

            PayrollRecord::where('payroll_period_id', $period->id)
                ->where('status', 'approved')
                ->update(['status' => 'paid', 'paid_at' => now()]);
        });
    }

    protected function resolveBankAccount(?int $bankAccountId)
    {
        if ($bankAccountId) {
            $bank = \App\Models\BankAccount::with('account')->find($bankAccountId);
            if ($bank?->account) {
                return $bank->account;
            }
        }

        return $this->journalPosting->systemAccount('1011');
    }
}
