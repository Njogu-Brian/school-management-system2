<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountingBudget;
use App\Models\AccountingBudgetLine;
use App\Models\FiscalPeriod;
use App\Services\Finance\AccountingReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(): View
    {
        $budgets = AccountingBudget::with('fiscalPeriod')->latest()->paginate(20);
        $periods = FiscalPeriod::orderByDesc('start_date')->get();

        return view('finance.accounting.budgets.index', compact('budgets', 'periods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fiscal_period_id' => 'required|exists:fiscal_periods,id',
            'name' => 'required|string|max:255',
        ]);

        $budget = AccountingBudget::create([
            'fiscal_period_id' => $validated['fiscal_period_id'],
            'name' => $validated['name'],
            'status' => 'active',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('finance.budgets.show', $budget)->with('success', 'Budget created. Add line amounts below.');
    }

    public function show(AccountingBudget $budget, AccountingReportService $reports): View
    {
        $budget->load(['fiscalPeriod', 'lines.account']);
        $expenseAccounts = Account::query()
            ->where('account_type', Account::TYPE_EXPENSE)
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $comparison = $reports->budgetVsActual($budget);

        return view('finance.accounting.budgets.show', compact('budget', 'expenseAccounts', 'comparison'));
    }

    public function storeLine(Request $request, AccountingBudget $budget): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'budget_amount' => 'required|numeric|min:0',
        ]);

        AccountingBudgetLine::updateOrCreate(
            ['budget_id' => $budget->id, 'account_id' => $validated['account_id']],
            ['budget_amount' => $validated['budget_amount']],
        );

        return back()->with('success', 'Budget line saved.');
    }
}
