<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $bankAccounts = BankAccount::orderBy('name')->get();
        return view('finance.bank_accounts.index', compact('bankAccounts'));
    }

    public function create()
    {
        return view('finance.bank_accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255|unique:bank_accounts,account_number',
            'bank_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'account_type' => 'required|in:current,savings,deposit,other',
            'is_active' => 'boolean',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string',
        ]);

        BankAccount::create($validated);

        return redirect()
            ->route('finance.bank-accounts.index')
            ->with('success', 'Bank account created successfully.');
    }

    public function show(BankAccount $bankAccount)
    {
        $bankAccount->load('payments');
        return view('finance.bank_accounts.show', compact('bankAccount'));
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('finance.bank_accounts.edit', compact('bankAccount'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255|unique:bank_accounts,account_number,' . $bankAccount->id,
            'bank_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'account_type' => 'required|in:current,savings,deposit,other',
            'is_active' => 'boolean',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string',
        ]);

        $bankAccount->update($validated);

        return redirect()
            ->route('finance.bank-accounts.index')
            ->with('success', 'Bank account updated successfully.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        // Check if bank account is in use
        if ($bankAccount->payments()->count() > 0) {
            return redirect()
                ->route('finance.bank-accounts.index')
                ->with('error', 'Cannot delete bank account that has been used in payments.');
        }

        // Check if any payment methods are linked to this account
        if (\App\Models\PaymentMethod::where('bank_account_id', $bankAccount->id)->count() > 0) {
            return redirect()
                ->route('finance.bank-accounts.index')
                ->with('error', 'Cannot delete bank account that is linked to payment methods.');
        }

        $bankAccount->delete();

        return redirect()
            ->route('finance.bank-accounts.index')
            ->with('success', 'Bank account deleted successfully.');
    }
}
