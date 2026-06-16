<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Finance\AccountCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChartOfAccountController extends Controller
{
    public function index(): View
    {
        $roots = Account::query()
            ->with(['children.children'])
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();

        $types = Account::types();

        return view('finance.accounting.chart_of_accounts.index', compact('roots', 'types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:' . implode(',', array_keys(Account::types())),
            'parent_id' => 'nullable|exists:accounts,id',
            'code' => 'nullable|string|max:20|unique:accounts,code',
            'is_postable' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $parent = isset($validated['parent_id']) ? Account::find($validated['parent_id']) : null;
        $accountType = $parent?->account_type ?? $validated['account_type'];

        Account::create([
            'code' => $validated['code'] ?: AccountCodeService::suggest($parent, $accountType),
            'name' => $validated['name'],
            'account_type' => $accountType,
            'parent_id' => $validated['parent_id'] ?? null,
            'normal_balance' => Account::defaultNormalBalance($accountType),
            'is_postable' => (bool) ($validated['is_postable'] ?? true),
            'is_active' => true,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Account created.');
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        if ($account->is_system && $request->boolean('delete')) {
            return back()->with('error', 'System accounts cannot be deleted.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_postable' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $account->update([
            'name' => $validated['name'],
            'is_postable' => (bool) ($validated['is_postable'] ?? $account->is_postable),
            'is_active' => (bool) ($validated['is_active'] ?? $account->is_active),
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Account updated.');
    }
}
