<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PettyCashFund;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PettyCashFundController extends Controller
{
    public function index(): View
    {
        $funds = PettyCashFund::with(['account', 'custodian'])->orderBy('name')->get();

        return view('finance.accounting.petty_cash.funds.index', compact('funds'));
    }

    public function create(): View
    {
        $accounts = Account::query()
            ->where('account_type', Account::TYPE_ASSET)
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('finance.accounting.petty_cash.funds.create', compact('accounts', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:petty_cash_funds,code',
            'name' => 'required|string|max:255',
            'account_id' => 'required|exists:accounts,id',
            'custodian_id' => 'nullable|exists:users,id',
            'imprest_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        PettyCashFund::create(array_merge($validated, ['is_active' => true]));

        return redirect()->route('finance.petty-cash-funds.index')->with('success', 'Petty cash fund created.');
    }
}
