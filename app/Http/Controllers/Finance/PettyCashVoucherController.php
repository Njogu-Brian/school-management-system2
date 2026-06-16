<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\PettyCashFund;
use App\Models\PettyCashVoucher;
use App\Services\Finance\PettyCashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PettyCashVoucherController extends Controller
{
    public function index(): View
    {
        $vouchers = PettyCashVoucher::with(['fund', 'preparer'])
            ->latest('voucher_date')
            ->latest('id')
            ->paginate(25);

        return view('finance.accounting.petty_cash.vouchers.index', compact('vouchers'));
    }

    public function create(): View
    {
        $funds = PettyCashFund::with('account')->where('is_active', true)->orderBy('name')->get();
        $categories = ExpenseCategory::with('parent')
            ->where('is_active', true)
            ->where('is_header', false)
            ->orderBy('name')
            ->get();
        $expenseAccounts = Account::query()
            ->where('account_type', Account::TYPE_EXPENSE)
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('finance.accounting.petty_cash.vouchers.create', compact('funds', 'categories', 'expenseAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'petty_cash_fund_id' => 'required|exists:petty_cash_funds,id',
            'voucher_type' => 'required|in:disbursement,replenishment',
            'voucher_date' => 'required|date',
            'payee' => 'nullable|string|max:255',
            'description' => 'required|string|max:2000',
            'amount' => 'required|numeric|min:0.01',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'account_id' => 'nullable|exists:accounts,id',
        ]);

        $voucher = PettyCashVoucher::create(array_merge($validated, [
            'status' => PettyCashVoucher::STATUS_DRAFT,
            'prepared_by' => $request->user()->id,
        ]));

        return redirect()->route('finance.petty-cash-vouchers.show', $voucher)
            ->with('success', 'Petty cash voucher created.');
    }

    public function show(PettyCashVoucher $pettyCashVoucher): View
    {
        $pettyCashVoucher->load(['fund.account', 'expenseCategory', 'account', 'preparer', 'approver', 'journalEntry.lines.account']);

        return view('finance.accounting.petty_cash.vouchers.show', ['voucher' => $pettyCashVoucher]);
    }

    public function approve(PettyCashVoucher $pettyCashVoucher, PettyCashService $service): RedirectResponse
    {
        $service->approve($pettyCashVoucher, request()->user());

        return back()->with('success', 'Voucher approved.');
    }

    public function post(PettyCashVoucher $pettyCashVoucher, PettyCashService $service): RedirectResponse
    {
        $service->post($pettyCashVoucher, request()->user());

        return back()->with('success', 'Voucher posted to the general ledger.');
    }
}
