<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\AccountingBudget;
use App\Models\AccountingBudgetLine;
use App\Models\JournalLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountingReportService
{
    public function trialBalance(?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        $query = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.status', 'posted');

        if ($dateFrom) {
            $query->whereDate('journal_entries.entry_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('journal_entries.entry_date', '<=', $dateTo);
        }

        return $query
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.account_type',
                'accounts.normal_balance',
                DB::raw('SUM(journal_lines.debit) as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit'),
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.account_type', 'accounts.normal_balance')
            ->orderBy('accounts.code')
            ->get()
            ->map(function ($row) {
                $debit = round((float) $row->total_debit, 2);
                $credit = round((float) $row->total_credit, 2);
                $net = round($debit - $credit, 2);

                return [
                    'code' => $row->code,
                    'name' => $row->name,
                    'account_type' => $row->account_type,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $net,
                ];
            });
    }

    public function profitAndLoss(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $rows = $this->trialBalance($dateFrom, $dateTo)
            ->filter(fn ($r) => in_array($r['account_type'], [Account::TYPE_REVENUE, Account::TYPE_EXPENSE], true));

        $revenue = $rows->where('account_type', Account::TYPE_REVENUE)->sum(fn ($r) => -$r['balance']);
        $expenses = $rows->where('account_type', Account::TYPE_EXPENSE)->sum(fn ($r) => $r['balance']);

        return [
            'lines' => $rows->values(),
            'total_revenue' => round($revenue, 2),
            'total_expenses' => round($expenses, 2),
            'net_profit' => round($revenue - $expenses, 2),
        ];
    }

    public function balanceSheet(?string $asOf = null): array
    {
        $dateTo = $asOf ?? now()->toDateString();
        $rows = $this->trialBalance(null, $dateTo);

        $section = fn (string $type) => $rows->where('account_type', $type)->values();

        $sum = fn (Collection $items) => round($items->sum('balance'), 2);

        $assets = $section(Account::TYPE_ASSET);
        $liabilities = $section(Account::TYPE_LIABILITY);
        $equity = $section(Account::TYPE_EQUITY);

        $totalAssets = $sum($assets);
        $totalLiabilities = $sum($liabilities);
        $totalEquity = $sum($equity);

        $pnl = $this->profitAndLoss(
            Carbon::parse($dateTo)->startOfYear()->toDateString(),
            $dateTo,
        );
        $currentYearProfit = $pnl['net_profit'];
        $totalEquityWithProfit = round($totalEquity + $currentYearProfit, 2);

        return [
            'as_of' => $dateTo,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'current_year_profit' => $currentYearProfit,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquityWithProfit,
            'balanced' => abs($totalAssets - ($totalLiabilities + $totalEquityWithProfit)) < 0.02,
        ];
    }

    public function budgetVsActual(AccountingBudget $budget, ?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        $budget->loadMissing(['lines.account', 'fiscalPeriod']);
        $dateFrom = $dateFrom ?? $budget->fiscalPeriod->start_date->toDateString();
        $dateTo = $dateTo ?? $budget->fiscalPeriod->end_date->toDateString();

        $actuals = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '>=', $dateFrom)
            ->whereDate('journal_entries.entry_date', '<=', $dateTo)
            ->select('journal_lines.account_id', DB::raw('SUM(journal_lines.debit) - SUM(journal_lines.credit) as net'))
            ->groupBy('journal_lines.account_id')
            ->pluck('net', 'account_id');

        return $budget->lines->map(function (AccountingBudgetLine $line) use ($actuals) {
            $actual = round((float) ($actuals[$line->account_id] ?? 0), 2);
            $budgetAmount = round((float) $line->budget_amount, 2);
            $variance = round($budgetAmount - $actual, 2);

            return [
                'account' => $line->account,
                'budget' => $budgetAmount,
                'actual' => $actual,
                'variance' => $variance,
                'pct_used' => $budgetAmount > 0 ? round(($actual / $budgetAmount) * 100, 1) : null,
            ];
        });
    }
}
