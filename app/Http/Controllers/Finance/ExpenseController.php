<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Http\Requests\Finance\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vendor;
use App\Services\ExpenseWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Expense::query()->with(['vendor', 'requester'])->latest('expense_date');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->vendor_id);
        }

        if ($request->boolean('no_vendor')) {
            $query->whereNull('vendor_id');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('expense_no', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', "%{$search}%"));
            });
        }

        $expenses = $query->paginate(20)->withQueryString();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $categoryGroups = $this->categoryGroups();

        return view('finance.expenses.index', compact('expenses', 'vendors', 'categoryGroups'));
    }

    /**
     * Update the vendor (and optionally re-categorise) of a single expense.
     */
    public function quickUpdate(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('manageVendor', $expense);

        $data = $request->validate([
            'vendor_name' => 'nullable|string|max:255',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
        ]);

        $result = $this->applyQuickEdit($expense, $data['vendor_name'] ?? null, $data['expense_category_id'] ?? null);

        $message = $result['changes'] ? 'Expense ' . $expense->expense_no . ' updated.' : 'No changes applied.';
        if ($result['category_blocked']) {
            $message .= ' Category was not changed because the expense is already approved/posted.';
        }

        return back()->with('success', $message);
    }

    /**
     * Apply a vendor (and optional category) to many expenses at once.
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expense_ids' => 'required|array|min:1',
            'expense_ids.*' => 'integer',
            'vendor_name' => 'nullable|string|max:255',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
        ]);

        if (trim((string) ($data['vendor_name'] ?? '')) === '' && empty($data['expense_category_id'])) {
            return back()->withErrors(['vendor_name' => 'Enter a vendor name and/or pick a category to apply.']);
        }

        $expenses = Expense::whereIn('id', $data['expense_ids'])->get();
        $updated = 0;
        $categoryBlocked = 0;

        foreach ($expenses as $expense) {
            if (! $request->user()->can('manageVendor', $expense)) {
                continue;
            }
            $result = $this->applyQuickEdit($expense, $data['vendor_name'] ?? null, $data['expense_category_id'] ?? null);
            if ($result['changes']) {
                $updated++;
            }
            if ($result['category_blocked']) {
                $categoryBlocked++;
            }
        }

        $message = "Updated {$updated} expense(s).";
        if ($categoryBlocked > 0) {
            $message .= " {$categoryBlocked} kept their category (already approved/posted).";
        }

        return back()->with('success', $message);
    }

    /**
     * Set the vendor and (when allowed) the category on an expense.
     *
     * @return array{changes: array<int, string>, category_blocked: bool}
     */
    private function applyQuickEdit(Expense $expense, ?string $vendorName, ?int $categoryId): array
    {
        $changes = [];
        $categoryBlocked = false;

        $vendorName = trim((string) $vendorName);
        if ($vendorName !== '') {
            $vendor = Vendor::firstOrCreateByName($vendorName);
            if ($vendor && $expense->vendor_id !== $vendor->id) {
                $expense->vendor_id = $vendor->id;
                $expense->save();
                $changes[] = 'vendor';
            }
        }

        if ($categoryId) {
            if (in_array($expense->status, [Expense::STATUS_APPROVED, Expense::STATUS_PAID], true)) {
                $categoryBlocked = true;
            } else {
                $chargeId = ExpenseCategory::where('code', 'TXN_COST')->value('id');
                $lines = $expense->lines();
                if ($chargeId) {
                    $lines->where('category_id', '!=', $chargeId);
                }
                $lines->update(['category_id' => $categoryId]);
                $changes[] = 'category';
            }
        }

        return ['changes' => $changes, 'category_blocked' => $categoryBlocked];
    }

    /**
     * Build the grouped (parent => children) category list used by the typeahead pickers.
     *
     * @return array<string, array<int, ExpenseCategory>>
     */
    private function categoryGroups(): array
    {
        $active = ExpenseCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $byId = $active->keyBy('id');
        $groups = [];
        foreach ($active as $category) {
            if ($category->is_header) {
                continue;
            }
            $parent = $category->parent_id ? $byId->get($category->parent_id) : null;
            $groups[$parent?->name ?? 'General'][] = $category;
        }

        return $groups;
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);
        $categories = ExpenseCategory::where('is_active', true)->where('is_header', false)->orderBy('name')->get();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        return view('finance.expenses.create', compact('categories', 'vendors'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);
        $data = $request->validated();

        $expense = DB::transaction(function () use ($request, $data) {
            $expense = Expense::create([
                'source_type' => $data['source_type'],
                'vendor_id' => $data['vendor_id'] ?? null,
                'requested_by' => $request->user()->id,
                'expense_date' => $data['expense_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency' => strtoupper($data['currency']),
                'status' => Expense::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['lines'] as $line) {
                $expense->lines()->create([
                    'category_id' => $line['category_id'],
                    'department' => $line['department'] ?? null,
                    'cost_center' => $line['cost_center'] ?? null,
                    'description' => $line['description'],
                    'qty' => $line['qty'],
                    'unit_cost' => $line['unit_cost'],
                    'tax_rate' => $line['tax_rate'] ?? 0,
                ]);
            }

            $expense->recalculateTotals();
            $expense->save();

            return $expense;
        });

        return redirect()->route('finance.expenses.show', $expense)->with('success', 'Expense draft created.');
    }

    public function show(Expense $expense): View
    {
        $this->authorize('view', $expense);
        $expense->load(['vendor', 'requester', 'approver', 'lines.category', 'approvals.approver', 'vouchers']);

        return view('finance.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);
        $expense->load('lines');
        $categories = ExpenseCategory::where('is_active', true)->where('is_header', false)->orderBy('name')->get();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        return view('finance.expenses.edit', compact('expense', 'categories', 'vendors'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);
        $data = $request->validated();

        DB::transaction(function () use ($expense, $data) {
            $expense->update([
                'source_type' => $data['source_type'],
                'vendor_id' => $data['vendor_id'] ?? null,
                'expense_date' => $data['expense_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency' => strtoupper($data['currency']),
                'notes' => $data['notes'] ?? null,
            ]);

            $expense->lines()->delete();
            foreach ($data['lines'] as $line) {
                $expense->lines()->create([
                    'category_id' => $line['category_id'],
                    'department' => $line['department'] ?? null,
                    'cost_center' => $line['cost_center'] ?? null,
                    'description' => $line['description'],
                    'qty' => $line['qty'],
                    'unit_cost' => $line['unit_cost'],
                    'tax_rate' => $line['tax_rate'] ?? 0,
                ]);
            }

            $expense->recalculateTotals();
            $expense->save();
        });

        return redirect()->route('finance.expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function submit(Expense $expense, ExpenseWorkflowService $workflowService): RedirectResponse
    {
        $this->authorize('submit', $expense);
        $workflowService->submit($expense);

        return redirect()->route('finance.expenses.show', $expense)->with('success', 'Expense submitted for approval.');
    }
}
