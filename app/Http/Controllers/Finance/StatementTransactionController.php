<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\ExpenseStatementImport;
use App\Models\ExpenseStatementLine;
use App\Models\Vendor;
use App\Services\Finance\ExpenseStatementImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

/**
 * Combined, cross-statement review of every parsed transaction. Recipients that
 * appear in multiple statements are merged into one group so a payee can be
 * classified once instead of statement-by-statement. Per-statement views remain
 * available under "Uploaded Statements".
 */
class StatementTransactionController extends Controller
{
    public function __construct(
        protected ExpenseStatementImportService $importService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ExpenseStatementImport::class);

        $filter = $request->string('filter')->toString() ?: null;
        $search = trim($request->string('search')->toString());

        $perPage = 20;
        $page = Paginator::resolveCurrentPage();
        $allGroups = $this->importService->groupedLinesAcrossImports($filter, $search ?: null);

        $groups = new LengthAwarePaginator(
            $allGroups->forPage($page, $perPage)->values(),
            $allGroups->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $activeCategories = ExpenseCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categoriesById = $activeCategories->keyBy('id');
        $categoryGroups = [];
        foreach ($activeCategories as $category) {
            if ($category->is_header) {
                continue;
            }
            $parent = $category->parent_id ? $categoriesById->get($category->parent_id) : null;
            $groupName = $parent?->name ?? 'General';
            $categoryGroups[$groupName][] = $category;
        }

        $stats = $this->importService->combinedStats();
        $pendingExpenseCreation = $this->importService->combinedExpenseCreationCount();
        $vendorNames = Vendor::where('is_active', true)->orderBy('name')->pluck('name');

        return view('finance.statement-transactions.index', compact(
            'groups',
            'categoryGroups',
            'filter',
            'search',
            'stats',
            'pendingExpenseCreation',
            'vendorNames',
        ));
    }

    public function updateGroup(Request $request): RedirectResponse
    {
        $this->authorize('create', ExpenseStatementImport::class);

        $validated = $request->validate([
            'group_key' => 'required|string|max:64',
            'review_status' => 'required|in:confirmed_expense,personal,ignored,pending',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'expense_description' => 'nullable|string|max:1000',
            'vendor_name' => 'nullable|string|max:255',
            'remember_choice' => 'nullable|boolean',
        ]);

        if ($validated['review_status'] === ExpenseStatementLine::REVIEW_CONFIRMED && empty($validated['expense_category_id'])) {
            return back()->withErrors(['expense_category_id' => 'Select a category when marking as business expense.']);
        }

        try {
            $reversed = $this->importService->applyGroupReviewGlobal(
                $validated['group_key'],
                $validated['review_status'],
                $validated['expense_category_id'] ?? null,
                $validated['expense_description'] ?? null,
                (bool) ($validated['remember_choice'] ?? false),
                (int) $request->user()->id,
                $validated['vendor_name'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['review_status' => $e->getMessage()]);
        }

        $message = $reversed
            ? 'Group edited — its previous expenses were reversed and the transactions moved back to pending. Re-categorise and Submit again.'
            : 'Transaction group updated across all statements.';

        return back()->with('success', $message);
    }

    public function bulkUpdateGroups(Request $request): RedirectResponse
    {
        $this->authorize('create', ExpenseStatementImport::class);

        $validated = $request->validate([
            'group_keys' => 'required|array|min:1',
            'group_keys.*' => 'string|max:64',
            'review_status' => 'required|in:confirmed_expense,personal,ignored,pending',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'expense_description' => 'nullable|string|max:1000',
            'remember_choice' => 'nullable|boolean',
        ]);

        if ($validated['review_status'] === ExpenseStatementLine::REVIEW_CONFIRMED && empty($validated['expense_category_id'])) {
            return back()->withErrors(['expense_category_id' => 'Select a category when marking as business expense.']);
        }

        $applied = 0;
        $blocked = [];

        foreach (array_unique($validated['group_keys']) as $groupKey) {
            try {
                $this->importService->applyGroupReviewGlobal(
                    $groupKey,
                    $validated['review_status'],
                    $validated['expense_category_id'] ?? null,
                    $validated['expense_description'] ?? null,
                    (bool) ($validated['remember_choice'] ?? false),
                    (int) $request->user()->id,
                );
                $applied++;
            } catch (\RuntimeException $e) {
                $blocked[] = $e->getMessage();
            }
        }

        if ($blocked !== []) {
            return back()
                ->with('success', "Updated {$applied} group(s).")
                ->withErrors(['bulk' => 'Some groups were skipped: ' . implode(' ', array_unique($blocked))]);
        }

        return back()->with('success', "Updated {$applied} group(s) across all statements.");
    }

    public function updateLine(Request $request): RedirectResponse
    {
        $this->authorize('create', ExpenseStatementImport::class);

        $validated = $request->validate([
            'line_id' => 'required|integer',
            'review_status' => 'required|in:confirmed_expense,personal,ignored,pending',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'expense_description' => 'nullable|string|max:1000',
            'vendor_name' => 'nullable|string|max:255',
        ]);

        if ($validated['review_status'] === ExpenseStatementLine::REVIEW_CONFIRMED && empty($validated['expense_category_id'])) {
            return back()->withErrors(['expense_category_id' => 'Select a category when marking as business expense.']);
        }

        if (! ExpenseStatementLine::whereKey($validated['line_id'])->exists()) {
            return back()->withErrors(['line_id' => 'Transaction not found.']);
        }

        try {
            $this->importService->applyLineReviewGlobal(
                (int) $validated['line_id'],
                $validated['review_status'],
                $validated['expense_category_id'] ?? null,
                $validated['expense_description'] ?? null,
                (int) $request->user()->id,
                $validated['vendor_name'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['line_id' => $e->getMessage()]);
        }

        return back()->with('success', 'Transaction updated.');
    }

    public function submitExpenses(Request $request): RedirectResponse
    {
        $this->authorize('create', ExpenseStatementImport::class);

        $created = $this->importService->createExpensesForAllImports((int) $request->user()->id);

        if ($created === 0) {
            return back()->withErrors([
                'submit' => 'No confirmed business transactions ready to submit. Mark transactions as business expenses with a category first.',
            ]);
        }

        return back()->with(
            'success',
            sprintf('Created %d expense(s) for approval. Approve them in Expenses or in each statement view.', $created)
        );
    }
}
