<?php

namespace App\Services\Finance;

use App\Models\ExpenseCategory;
use App\Models\PettyCashFund;
use App\Models\PettyCashVoucher;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PettyCashService
{
    public function __construct(
        protected JournalPostingService $journalPosting,
    ) {}

    public function approve(PettyCashVoucher $voucher, User $approver): PettyCashVoucher
    {
        if ($voucher->status !== PettyCashVoucher::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Only draft petty cash vouchers can be approved.');
        }

        $voucher->status = PettyCashVoucher::STATUS_APPROVED;
        $voucher->approved_by = $approver->id;
        $voucher->save();

        return $voucher->refresh();
    }

    public function post(PettyCashVoucher $voucher, User $user): PettyCashVoucher
    {
        if (! in_array($voucher->status, [PettyCashVoucher::STATUS_DRAFT, PettyCashVoucher::STATUS_APPROVED], true)) {
            throw new \InvalidArgumentException('Voucher cannot be posted in its current status.');
        }

        return DB::transaction(function () use ($voucher, $user) {
            $fund = $voucher->fund()->with('account')->firstOrFail();
            $amount = (float) $voucher->amount;

            if ($voucher->voucher_type === PettyCashVoucher::TYPE_DISBURSEMENT) {
                $expenseAccount = $this->resolveExpenseAccount($voucher);
                $lines = [
                    ['account_id' => $expenseAccount->id, 'debit' => $amount, 'description' => $voucher->description],
                    ['account_id' => $fund->account_id, 'credit' => $amount, 'description' => $voucher->description],
                ];
            } else {
                $bankAccount = $this->journalPosting->systemAccount('1010');
                $lines = [
                    ['account_id' => $fund->account_id, 'debit' => $amount, 'description' => $voucher->description],
                    ['account_id' => $bankAccount->id, 'credit' => $amount, 'description' => $voucher->description],
                ];
            }

            $entry = $this->journalPosting->post(
                $lines,
                'Petty cash voucher ' . $voucher->voucher_no,
                $voucher->voucher_date,
                'petty_cash_voucher',
                $voucher->id,
                $user,
            );

            $voucher->status = PettyCashVoucher::STATUS_POSTED;
            $voucher->approved_by = $voucher->approved_by ?? $user->id;
            $voucher->journal_entry_id = $entry->id;
            $voucher->save();

            return $voucher->refresh()->load(['journalEntry.lines.account', 'fund']);
        });
    }

    protected function resolveExpenseAccount(PettyCashVoucher $voucher)
    {
        if ($voucher->account_id) {
            return $voucher->account;
        }

        if ($voucher->expense_category_id) {
            $category = ExpenseCategory::with('account')->find($voucher->expense_category_id);
            if ($category?->account) {
                return $category->account;
            }
        }

        return $this->journalPosting->systemAccount('5999');
    }
}
