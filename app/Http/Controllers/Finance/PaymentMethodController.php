<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $paymentMethods = PaymentMethod::with('bankAccount')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
        
        $bankAccounts = BankAccount::active()->get();
        
        return view('finance.payment_methods.index', compact('paymentMethods', 'bankAccounts'));
    }

    public function create()
    {
        $bankAccounts = BankAccount::active()->get();
        return view('finance.payment_methods.create', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'requires_reference' => 'boolean',
            'is_online' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
        ]);

        PaymentMethod::create($validated);

        return redirect()
            ->route('finance.payment-methods.index')
            ->with('success', 'Payment method created successfully.');
    }

    public function show(PaymentMethod $paymentMethod)
    {
        $paymentMethod->load('bankAccount', 'payments');
        return view('finance.payment_methods.show', compact('paymentMethod'));
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        $bankAccounts = BankAccount::active()->get();
        return view('finance.payment_methods.edit', compact('paymentMethod', 'bankAccounts'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_methods,code,' . $paymentMethod->id,
            'requires_reference' => 'boolean',
            'is_online' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
        ]);

        $paymentMethod->update($validated);

        return redirect()
            ->route('finance.payment-methods.index')
            ->with('success', 'Payment method updated successfully.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        // Check if payment method is in use
        if ($paymentMethod->payments()->count() > 0) {
            return redirect()
                ->route('finance.payment-methods.index')
                ->with('error', 'Cannot delete payment method that has been used in payments.');
        }

        $paymentMethod->delete();

        return redirect()
            ->route('finance.payment-methods.index')
            ->with('success', 'Payment method deleted successfully.');
    }
}
