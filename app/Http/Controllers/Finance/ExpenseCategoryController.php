<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Services\Finance\ExpenseCategoryCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(): View
    {
        $tree = ExpenseCategory::query()
            ->with(['children.children.account', 'account', 'parent'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $headerParents = ExpenseCategory::query()
            ->where('is_active', true)
            ->where('is_header', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $accounts = Account::query()
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $typeLabels = Account::types();
        $accountGroups = $accounts
            ->groupBy('account_type')
            ->mapWithKeys(fn ($group, $type) => [($typeLabels[$type] ?? ucfirst($type)) => $group]);

        return view('finance.expense_categories.index', compact('tree', 'headerParents', 'accounts', 'accountGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:expense_categories,code',
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'account_id' => 'nullable|exists:accounts,id',
            'is_header' => 'nullable|boolean',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $parent = isset($validated['parent_id']) ? ExpenseCategory::find($validated['parent_id']) : null;

        ExpenseCategory::create([
            'code' => $validated['code'] ?: ExpenseCategoryCodeService::suggest($parent, $validated['name']),
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'account_id' => $validated['account_id'] ?? null,
            'is_header' => (bool) ($validated['is_header'] ?? false),
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('finance.expense-categories.index')->with('success', 'Category created.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'account_id' => 'nullable|exists:accounts,id',
            'is_header' => 'nullable|boolean',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if (! empty($validated['parent_id'])) {
            $newParentId = (int) $validated['parent_id'];

            if ($newParentId === $expenseCategory->id) {
                return back()->with('error', 'A category cannot be its own parent.');
            }

            if (in_array($newParentId, $this->descendantIds($expenseCategory), true)) {
                return back()->with('error', 'A category cannot be moved under one of its own sub-categories.');
            }
        }

        $expenseCategory->update([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'account_id' => $validated['account_id'] ?? null,
            'is_header' => (bool) ($validated['is_header'] ?? false),
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('finance.expense-categories.index')->with('success', 'Category updated.');
    }

    /**
     * Collect all descendant category IDs so we can block cyclic re-parenting.
     *
     * @return array<int, int>
     */
    protected function descendantIds(ExpenseCategory $category): array
    {
        $ids = [];
        $stack = ExpenseCategory::where('parent_id', $category->id)->pluck('id')->all();

        while ($stack) {
            $current = array_pop($stack);
            $ids[] = $current;
            foreach (ExpenseCategory::where('parent_id', $current)->pluck('id')->all() as $childId) {
                $stack[] = $childId;
            }
        }

        return $ids;
    }
}
