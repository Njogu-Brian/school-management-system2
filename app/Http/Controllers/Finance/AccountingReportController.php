<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\AccountingReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingReportController extends Controller
{
    public function trialBalance(Request $request, AccountingReportService $reports): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to', now()->toDateString());
        $rows = $reports->trialBalance($dateFrom, $dateTo);
        $totalDebit = round($rows->sum('debit'), 2);
        $totalCredit = round($rows->sum('credit'), 2);

        return view('finance.accounting.reports.trial_balance', compact('rows', 'dateFrom', 'dateTo', 'totalDebit', 'totalCredit'));
    }

    public function profitAndLoss(Request $request, AccountingReportService $reports): View
    {
        $dateFrom = $request->input('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $report = $reports->profitAndLoss($dateFrom, $dateTo);

        return view('finance.accounting.reports.profit_and_loss', compact('report', 'dateFrom', 'dateTo'));
    }

    public function balanceSheet(Request $request, AccountingReportService $reports): View
    {
        $asOf = $request->input('as_of', now()->toDateString());
        $report = $reports->balanceSheet($asOf);

        return view('finance.accounting.reports.balance_sheet', compact('report', 'asOf'));
    }
}
