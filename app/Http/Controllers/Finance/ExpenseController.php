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

        $expenses = $query->paginate(20)->withQueryString();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        return view('finance.expenses.index', compact('expenses', 'vendors'));
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
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
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
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
