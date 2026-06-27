<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\ExpenseStatementImport;
use App\Models\ExpenseStatementLine;
use App\Services\Finance\ExpenseStatementImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class ExpenseStatementController extends Controller
{
    public function __construct(
        protected ExpenseStatementImportService $importService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', ExpenseStatementImport::class);

        $imports = ExpenseStatementImport::query()
            ->with('uploader')
            ->latest()
            ->paginate(15);

        return view('finance.expense-statements.index', compact('imports'));
    }

    public function create(): View
    {
        $this->authorize('create', ExpenseStatementImport::class);

        return view('finance.expense-statements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ExpenseStatementImport::class);

        if (! $request->hasFile('statement_file') || ! $request->file('statement_file')->isValid()) {
            $uploadError = $request->file('statement_file')?->getErrorMessage()
                ?? 'No file received. Check that the PDF is under your PHP upload limit (upload_max_filesize / post_max_size).';

            return redirect()
                ->route('finance.expense-statements.create')
                ->withInput()
                ->withErrors(['statement_file' => $uploadError]);
        }

        $validated = $request->validate([
            'statement_file' => 'required|file|mimes:pdf|max:20480',
            'pdf_password' => 'nullable|string|max:64',
        ]);

        @set_time_limit(900);

        $result = $this->importService->importMpesa(
            $request->file('statement_file'),
            (int) $request->user()->id,
            $validated['pdf_password'] ?? null,
        );

        if (! ($result['success'] ?? false)) {
            $error = $result['error'] ?? 'parse_failed';

            if ($error === 'password_required') {
                return redirect()
                    ->route('finance.expense-statements.create')
                    ->withInput()
                    ->withErrors([
                        'pdf_password' => 'This statement is password protected. Enter the PDF password and upload again.',
                        'password_required' => true,
                    ]);
            }

            return redirect()
                ->route('finance.expense-statements.create')
                ->withInput()
                ->withErrors(['statement_file' => $result['message'] ?? 'Failed to parse statement.']);
        }

        return redirect()
            ->route('finance.expense-statements.show', $result['import'])
            ->with('success', 'Statement parsed successfully. Review outgoing transactions below.');
    }

    public function show(Request $request, ExpenseStatementImport $expenseStatement): View
    {
        $this->authorize('view', $expenseStatement);

        $filter = $request->string('filter')->toString() ?: null;

        $perPage = 20;
        $page = Paginator::resolveCurrentPage();
        $allGroups = $this->importService->groupedLines($expenseStatement, $filter);

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

        $stats = [
            'outgoing_total' => $expenseStatement->outgoing_total,
            'confirmed_total' => $expenseStatement->confirmed_expense_total,
            'pending_outgoing' => $expenseStatement->lines()
                ->where('direction', 'out')
                ->where('review_status', ExpenseStatementLine::REVIEW_PENDING)
                ->sum('withdrawn_amount'),
        ];

        $draftStats = $this->importService->confirmedDraftStats($expenseStatement);

        return view('finance.expense-statements.show', compact(
            'expenseStatement',
            'groups',
            'categoryGroups',
            'filter',
            'stats',
            'draftStats',
        ));
    }

    public function updateGroup(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

        $validated = $request->validate([
            'group_key' => 'required|string|max:64',
            'review_status' => 'required|in:confirmed_expense,personal,ignored,pending',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'expense_description' => 'nullable|string|max:1000',
            'remember_choice' => 'nullable|boolean',
        ]);

        if ($validated['review_status'] === ExpenseStatementLine::REVIEW_CONFIRMED && empty($validated['expense_category_id'])) {
            return back()->withErrors(['expense_category_id' => 'Select a category when marking as business expense.']);
        }

        $this->importService->applyGroupReview(
            $expenseStatement,
            $validated['group_key'],
            $validated['review_status'],
            $validated['expense_category_id'] ?? null,
            $validated['expense_description'] ?? null,
            (bool) ($validated['remember_choice'] ?? false),
            (int) $request->user()->id,
        );

        return back()->with('success', 'Transaction group updated.');
    }

    public function updateLine(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

        $validated = $request->validate([
            'line_id' => 'required|integer',
            'review_status' => 'required|in:confirmed_expense,personal,ignored,pending',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'expense_description' => 'nullable|string|max:1000',
        ]);

        if ($validated['review_status'] === ExpenseStatementLine::REVIEW_CONFIRMED && empty($validated['expense_category_id'])) {
            return back()->withErrors(['expense_category_id' => 'Select a category when marking as business expense.']);
        }

        $line = $expenseStatement->lines()->whereKey($validated['line_id'])->first();
        if (! $line) {
            return back()->withErrors(['line_id' => 'Transaction not found in this import.']);
        }

        $this->importService->applyLineReview(
            $expenseStatement,
            (int) $validated['line_id'],
            $validated['review_status'],
            $validated['expense_category_id'] ?? null,
            $validated['expense_description'] ?? null,
            (int) $request->user()->id,
        );

        return back()->with('success', 'Transaction updated.');
    }

    public function generateExpenses(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

        $result = $this->importService->generateExpenseDrafts(
            $expenseStatement,
            (int) $request->user()->id,
        );

        if ($result['created'] === 0) {
            return back()->withErrors([
                'generate' => 'No confirmed business transactions ready to convert. Mark transactions as business expenses with a category first.',
            ]);
        }

        return back()->with(
            'success',
            sprintf(
                'Created %d expense draft(s). %s',
                $result['created'],
                $result['created'] === 1
                    ? 'Open it from Expenses to review and submit.'
                    : 'Open them from the Expenses list to review and submit.'
            )
        );
    }

    public function destroy(ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('delete', $expenseStatement);

        $expenseStatement->delete();

        return redirect()
            ->route('finance.expense-statements.index')
            ->with('success', 'Statement import deleted.');
    }
}
