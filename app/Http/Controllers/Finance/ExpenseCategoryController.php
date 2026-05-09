<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ExpenseCategory::with('parent')->orderBy('name')->paginate(25);
        $parents = ExpenseCategory::orderBy('name')->get();

        return view('finance.expense_categories.index', compact('categories', 'parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:expense_categories,code',
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        ExpenseCategory::create($validated);

        return redirect()->route('finance.expense-categories.index')->with('success', 'Expense category created.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:expense_categories,code,' . $expenseCategory->id,
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $expenseCategory->update($validated);

        return redirect()->route('finance.expense-categories.index')->with('success', 'Expense category updated.');
    }
}
