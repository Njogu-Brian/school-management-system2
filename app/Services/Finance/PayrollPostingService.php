<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollPostingService
{
    public function __construct(
        protected JournalPostingService $journalPosting,
    ) {}

    public function postAccrual(PayrollPeriod $period, ?User $user = null): void
    {
        if ($period->accrual_journal_entry_id) {
            $this->ensureExpense($period, $user);
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

        $this->ensureExpense($period, $user);
    }

    /**
     * Create (or reuse) an approved Expense for this payroll period.
     * Informational + finance visibility; GL is already posted via accrual journal.
     */
    public function ensureExpense(PayrollPeriod $period, ?User $user = null): ?Expense
    {
        if ($period->expense_id) {
            return Expense::find($period->expense_id);
        }

        $period->loadMissing('records');
        $totalGross = round((float) $period->records->sum('gross_salary'), 2);
        $totalNet = round((float) $period->records->sum('net_salary'), 2);
        $totalDeductions = round((float) $period->records->sum('total_deductions'), 2);

        if ($totalGross <= 0 && $totalNet <= 0) {
            return null;
        }

        $category = ExpenseCategory::where('code', 'SALARIES')->where('is_active', true)->first()
            ?? ExpenseCategory::where('name', 'like', '%Salaries%')->where('is_active', true)->first();

        if (! $category) {
            Log::warning('Payroll expense skipped: SALARIES expense category missing', [
                'payroll_period_id' => $period->id,
            ]);
            return null;
        }

        $requesterId = $user?->id ?? $period->processed_by ?? auth()->id();

        try {
            $expense = DB::transaction(function () use ($period, $category, $requesterId, $totalGross, $totalNet, $totalDeductions) {
                $expense = Expense::create([
                    'source_type' => 'payroll',
                    'vendor_id' => null,
                    'requested_by' => $requesterId,
                    'expense_date' => $period->pay_date ?? $period->end_date ?? now(),
                    'currency' => 'KES',
                    'status' => Expense::STATUS_APPROVED,
                    'notes' => sprintf(
                        'Payroll — %s | Staff: %d | Gross: %s | Deductions: %s | Net: %s',
                        $period->period_name ?? $period->name,
                        $period->records->count(),
                        number_format($totalGross, 2, '.', ''),
                        number_format($totalDeductions, 2, '.', ''),
                        number_format($totalNet, 2, '.', ''),
                    ),
                    'submitted_at' => now(),
                    'approved_at' => now(),
                    'approved_by' => $requesterId,
                ]);

                $expense->lines()->create([
                    'category_id' => $category->id,
                    'department' => 'HR / Payroll',
                    'description' => sprintf(
                        'Salaries & wages — %s (%d staff)',
                        $period->period_name ?? $period->name,
                        $period->records->count(),
                    ),
                    'qty' => 1,
                    'unit_cost' => $totalGross > 0 ? $totalGross : $totalNet,
                    'tax_rate' => 0,
                    'line_total' => $totalGross > 0 ? $totalGross : $totalNet,
                ]);

                $expense->recalculateTotals();
                $expense->save();

                $period->expense_id = $expense->id;
                $period->saveQuietly();

                return $expense;
            });

            return $expense;
        } catch (\Throwable $e) {
            Log::error('Failed to create payroll expense', [
                'payroll_period_id' => $period->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function markPeriodPaid(PayrollPeriod $period, ?User $user = null, ?int $bankAccountId = null): void
    {
        if ($period->payment_journal_entry_id) {
            throw new \InvalidArgumentException('Payroll period has already been marked as paid.');
        }

        if (! $period->accrual_journal_entry_id) {
            $this->postAccrual($period, $user);
        } else {
            $this->ensureExpense($period, $user);
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

            if ($period->expense_id) {
                Expense::where('id', $period->expense_id)
                    ->where('status', '!=', Expense::STATUS_PAID)
                    ->update(['status' => Expense::STATUS_PAID]);
            }
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
