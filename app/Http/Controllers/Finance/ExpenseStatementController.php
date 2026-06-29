<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Jobs\ParseExpenseStatementJob;
use App\Models\ExpenseCategory;
use App\Models\ExpenseStatementImport;
use App\Models\ExpenseStatementLine;
use App\Models\Vendor;
use App\Services\Finance\ExpenseStatementImportService;
use App\Services\Finance\MpesaExpenseStatementParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ExpenseStatementController extends Controller
{
    public function __construct(
        protected ExpenseStatementImportService $importService,
        protected MpesaExpenseStatementParser $parser,
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

        $file = $request->file('statement_file');
        $password = $validated['pdf_password'] ?? null;

        [$storedPath, $absolutePath] = $this->importService->storeUploadedFile($file);

        // Fast, low-memory precheck (page count + password + format) so the
        // password prompt stays synchronous. The heavy page extraction then runs
        // in a background job, a few pages at a time, instead of blocking the
        // web request (which previously exhausted memory and took the site down).
        $info = $this->parser->countPages($absolutePath, $password);

        if (! ($info['success'] ?? false)) {
            $this->importService->deleteStoredFile($storedPath);
            $error = $info['error'] ?? 'parse_failed';

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
                ->withErrors(['statement_file' => $info['message'] ?? 'Failed to read statement.']);
        }

        if (! ($info['is_mpesa'] ?? false)) {
            $this->importService->deleteStoredFile($storedPath);

            return redirect()
                ->route('finance.expense-statements.create')
                ->withInput()
                ->withErrors(['statement_file' => 'Could not detect an M-Pesa detailed statement in this PDF.']);
        }

        $import = $this->importService->createPendingImport($file, $storedPath, (int) $request->user()->id);

        ParseExpenseStatementJob::dispatch($import->id, $password, (int) ($info['page_count'] ?? 1));

        return redirect()
            ->route('finance.expense-statements.show', $import)
            ->with('info', 'Your statement was uploaded and is being processed in the background. This page updates automatically.');
    }

    public function parseProgress(ExpenseStatementImport $expenseStatement): JsonResponse
    {
        $this->authorize('view', $expenseStatement);

        $progress = ParseExpenseStatementJob::getProgress($expenseStatement->id);
        $status = $progress['status'] ?? null;

        // Fall back to the persisted import status if the cache has expired.
        if ($expenseStatement->status === ExpenseStatementImport::STATUS_PARSED && $status !== 'completed') {
            $progress = [
                'status' => 'completed',
                'percent' => 100,
                'message' => 'Completed.',
                'redirect_url' => route('finance.expense-statements.show', $expenseStatement->id),
            ];
        } elseif ($expenseStatement->status === ExpenseStatementImport::STATUS_FAILED && $status !== 'failed') {
            $progress = [
                'status' => 'failed',
                'percent' => 100,
                'message' => $expenseStatement->parse_error ?: 'Parsing failed.',
            ];
        }

        return response()->json($progress);
    }

    public function show(Request $request, ExpenseStatementImport $expenseStatement): View
    {
        $this->authorize('view', $expenseStatement);

        // While the background parser runs (or if it failed), show the progress
        // screen instead of the (empty) review table.
        if (in_array($expenseStatement->status, [
            ExpenseStatementImport::STATUS_PARSING,
            ExpenseStatementImport::STATUS_FAILED,
        ], true)) {
            return view('finance.expense-statements.processing', [
                'import' => $expenseStatement,
                'progress' => ParseExpenseStatementJob::getProgress($expenseStatement->id),
            ]);
        }

        $filter = $request->string('filter')->toString() ?: null;
        $search = trim($request->string('search')->toString());

        $perPage = 20;
        $page = Paginator::resolveCurrentPage();
        $allGroups = $this->importService->groupedLines($expenseStatement, $filter, $search ?: null);

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
        $expenseGroups = $this->importService->importExpenseGroups($expenseStatement);
        $pendingExpenseCreation = $this->importService->pendingExpenseCreationCount($expenseStatement);
        $vendorNames = Vendor::where('is_active', true)->orderBy('name')->pluck('name');

        return view('finance.expense-statements.show', compact(
            'expenseStatement',
            'groups',
            'categoryGroups',
            'filter',
            'search',
            'stats',
            'draftStats',
            'expenseGroups',
            'pendingExpenseCreation',
            'vendorNames',
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
            'vendor_name' => 'nullable|string|max:255',
            'remember_choice' => 'nullable|boolean',
        ]);

        if ($validated['review_status'] === ExpenseStatementLine::REVIEW_CONFIRMED && empty($validated['expense_category_id'])) {
            return back()->withErrors(['expense_category_id' => 'Select a category when marking as business expense.']);
        }

        try {
            $reversed = $this->importService->applyGroupReview(
                $expenseStatement,
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
            : 'Transaction group updated.';

        return back()->with('success', $message);
    }

    public function bulkUpdateGroups(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

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
                $this->importService->applyGroupReview(
                    $expenseStatement,
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

        return back()->with('success', "Updated {$applied} group(s).");
    }

    public function updateLine(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

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

        $line = $expenseStatement->lines()->whereKey($validated['line_id'])->first();
        if (! $line) {
            return back()->withErrors(['line_id' => 'Transaction not found in this import.']);
        }

        try {
            $this->importService->applyLineReview(
                $expenseStatement,
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

    public function submitExpenses(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

        $created = $this->importService->createExpensesForImport(
            $expenseStatement,
            (int) $request->user()->id,
        );

        if ($created === 0) {
            return back()->withErrors([
                'submit' => 'No confirmed business transactions ready to submit. Mark transactions as business expenses with a category first.',
            ]);
        }

        return back()->with(
            'success',
            sprintf('Created %d expense(s) for approval. Approve them below or by category.', $created)
        );
    }

    public function approveExpenses(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

        $validated = $request->validate([
            'expense_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
        ]);

        $approved = $this->importService->approveStatementExpenses(
            $expenseStatement,
            (int) $request->user()->id,
            $validated['expense_id'] ?? null,
            $validated['category_id'] ?? null,
        );

        if ($approved === 0) {
            return back()->withErrors(['approve' => 'No submitted expenses matched for approval.']);
        }

        return back()->with('success', sprintf('Approved and posted %d expense(s) to the ledger.', $approved));
    }

    public function rejectExpense(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

        $validated = $request->validate(['expense_id' => 'required|integer']);

        try {
            $this->importService->rejectStatementExpense($expenseStatement, (int) $validated['expense_id']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['reject' => $e->getMessage()]);
        }

        return back()->with('success', 'Expense rejected — its transactions are back in Uncategorized.');
    }

    public function reverseExpense(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

        $validated = $request->validate([
            'expense_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
        ]);

        try {
            $reversed = $this->importService->reverseStatementExpenses(
                $expenseStatement,
                (int) $request->user()->id,
                $validated['expense_id'] ?? null,
                $validated['category_id'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['reverse' => $e->getMessage()]);
        }

        if ($reversed === 0) {
            return back()->withErrors(['reverse' => 'No posted expenses matched for reversal.']);
        }

        return back()->with('success', sprintf(
            'Reversed %d posted expense(s) — contra journal entries posted and the transactions returned to Uncategorized.',
            $reversed
        ));
    }

    public function editExpense(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

        $validated = $request->validate([
            'expense_id' => 'required|integer',
            'vendor_name' => 'nullable|string|max:255',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'expense_description' => 'nullable|string|max:1000',
        ]);

        try {
            $this->importService->updateStatementExpense(
                $expenseStatement,
                (int) $validated['expense_id'],
                $validated['vendor_name'] ?? null,
                $validated['expense_category_id'] ?? null,
                $validated['expense_description'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['edit' => $e->getMessage()]);
        }

        return back()->with('success', 'Expense updated.');
    }

    public function destroy(ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('delete', $expenseStatement);

        $lockedCount = $expenseStatement->lines()
            ->where(function ($q) {
                $q->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED)
                    ->orWhereNotNull('expense_id');
            })
            ->count();

        if ($lockedCount > 0) {
            return back()->withErrors([
                'delete' => "This statement has {$lockedCount} confirmed/recorded transaction(s). Move them back to pending (which removes their expenses) before deleting the statement.",
            ]);
        }

        if ($expenseStatement->file_path) {
            Storage::disk(config('filesystems.private_disk', 'private'))->delete($expenseStatement->file_path);
        }

        $expenseStatement->delete();

        return redirect()
            ->route('finance.expense-statements.index')
            ->with('success', 'Statement and all its transactions were deleted.');
    }
}
