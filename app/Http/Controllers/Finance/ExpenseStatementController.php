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

        // Live confirmed-business totals per import (the stored column can lag behind
        // auto-categorisation), keyed by import id for the listing.
        $confirmedTotals = ExpenseStatementLine::query()
            ->whereIn('import_id', $imports->pluck('id'))
            ->where('direction', 'out')
            ->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED)
            ->selectRaw('import_id, SUM(withdrawn_amount) as total')
            ->groupBy('import_id')
            ->pluck('total', 'import_id');

        return view('finance.expense-statements.index', compact('imports', 'confirmedTotals'));
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

        if (is_string($password) && $password !== '') {
            $import->pdf_password = $password;
            $summary = $import->summary ?? [];
            $summary['pdf_encrypted'] = true;
            $import->summary = $summary;
            $import->save();
        }

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

        $perPage = (int) $request->integer('per_page', 20);
        if (! in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }
        $groups = $this->importService->paginateGroupedLines(
            $expenseStatement,
            $filter,
            $search ?: null,
            $perPage,
            max(1, (int) $request->integer('page', 1)),
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

        // Compute confirmed/pending totals live from the current line state so the
        // summary cards always tally with auto-categorised transactions (the stored
        // confirmed_expense_total column is only refreshed on manual review/import).
        $stats = [
            'outgoing_total' => $expenseStatement->outgoing_total,
            'confirmed_total' => $expenseStatement->lines()
                ->where('direction', 'out')
                ->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED)
                ->sum('withdrawn_amount'),
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
            'perPage',
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

        if ($reversed) {
            $message = $validated['review_status'] === ExpenseStatementLine::REVIEW_CONFIRMED
                ? 'Group updated — the previous expense(s) were removed (any ledger postings reversed) and replaced with your new vendor/category/description. Submit the confirmed transactions to recreate the expense(s).'
                : 'Group updated — the previous expense(s) were removed (any ledger postings reversed).';
        } else {
            $message = 'Transaction group updated.';
        }

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
            'vendor_name' => 'nullable|string|max:255',
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
                    $validated['vendor_name'] ?? null,
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

    public function document(ExpenseStatementImport $expenseStatement): View
    {
        $this->authorize('view', $expenseStatement);

        $fileExists = $this->statementFileExists($expenseStatement);
        if ($fileExists) {
            $this->rememberPdfProtection($expenseStatement);
        }

        return view('finance.expense-statements.document', [
            'import' => $expenseStatement->fresh(),
            'fileExists' => $fileExists,
            'encrypted' => (bool) (($expenseStatement->fresh()->summary ?? [])['pdf_encrypted'] ?? false),
        ]);
    }

    public function saveSecurityCode(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

        $validated = $request->validate([
            'pdf_password' => 'required|string|max:64',
        ]);

        if (! $this->statementFileExists($expenseStatement)) {
            return back()->withErrors(['pdf_password' => 'The original statement file is not stored, so the code cannot be checked.']);
        }

        $password = $validated['pdf_password'];
        $info = $this->withLocalStatementFile($expenseStatement, function (?string $path) use ($password) {
            return $path ? $this->parser->inspect($path, $password) : ['success' => false, 'error' => 'file_not_found'];
        });

        $passwordOk = ($info['success'] ?? false) && ($info['password_ok'] ?? false);
        if (! $passwordOk) {
            return back()->withErrors([
                'pdf_password' => $info['message'] ?? 'That security code does not open this statement.',
            ]);
        }

        $expenseStatement->pdf_password = $password;
        if (! empty($info['verification_code'])) {
            $expenseStatement->verification_code = $info['verification_code'];
        }
        $summary = $expenseStatement->summary ?? [];
        $summary['pdf_encrypted'] = (bool) ($info['encrypted'] ?? true);
        $expenseStatement->summary = $summary;
        $expenseStatement->save();

        return back()->with('success', 'Security code saved. It is shown below before the statement opens.');
    }

    public function attachDocument(Request $request, ExpenseStatementImport $expenseStatement): RedirectResponse
    {
        $this->authorize('update', $expenseStatement);

        $validated = $request->validate([
            'statement_file' => 'required|file|mimes:pdf|max:20480',
            'pdf_password' => 'nullable|string|max:64',
        ]);

        $file = $request->file('statement_file');
        $password = $validated['pdf_password'] ?? null;
        $diskName = config('filesystems.private_disk', 'private');
        $driver = config("filesystems.disks.{$diskName}.driver", 'local');
        [$storedPath, $absolutePath] = $this->importService->storeUploadedFile($file);

        try {
            $info = $this->parser->inspect($absolutePath, is_string($password) && $password !== '' ? $password : null);
        } finally {
            if ($driver !== 'local' && is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        $encrypted = (bool) ($info['encrypted'] ?? false);
        $passwordOk = ($info['success'] ?? false) && ($info['password_ok'] ?? false);

        if ($encrypted && ! $passwordOk) {
            $this->importService->deleteStoredFile($storedPath);

            return back()->withErrors([
                'pdf_password' => 'This PDF is locked. Enter the security code that opens it, then upload again.',
                'password_required' => true,
            ])->withInput();
        }

        if (! ($info['success'] ?? false)) {
            $this->importService->deleteStoredFile($storedPath);

            return back()->withErrors([
                'statement_file' => $info['message'] ?? 'Could not read that PDF.',
            ]);
        }

        if ($expenseStatement->file_path && $this->statementFileExists($expenseStatement)) {
            storage_private()->delete($expenseStatement->file_path);
        }

        $summary = $expenseStatement->summary ?? [];
        $summary['pdf_encrypted'] = $encrypted;
        $expenseStatement->file_path = $storedPath;
        $expenseStatement->original_filename = $file->getClientOriginalName();
        $expenseStatement->summary = $summary;
        $expenseStatement->pdf_password = ($encrypted && is_string($password) && $password !== '') ? $password : null;
        if (! empty($info['verification_code'])) {
            $expenseStatement->verification_code = $info['verification_code'];
        }
        $expenseStatement->save();

        return back()->with('success', 'Statement PDF saved. You can view and download it below.');
    }

    public function viewDocument(ExpenseStatementImport $expenseStatement)
    {
        $denied = $this->guardStatementDownload($expenseStatement);
        if ($denied) {
            return $denied;
        }

        $name = $this->statementDownloadName($expenseStatement);

        return storage_private()->response($expenseStatement->file_path, $name, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $name . '"',
        ]);
    }

    public function downloadDocument(ExpenseStatementImport $expenseStatement)
    {
        $denied = $this->guardStatementDownload($expenseStatement);
        if ($denied) {
            return $denied;
        }

        return storage_private()->download(
            $expenseStatement->file_path,
            $this->statementDownloadName($expenseStatement),
            ['Content-Type' => 'application/pdf']
        );
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

    protected function statementFileExists(ExpenseStatementImport $import): bool
    {
        return is_string($import->file_path)
            && $import->file_path !== ''
            && storage_private()->exists($import->file_path);
    }

    /**
     * Download the stored PDF once and remember whether it is password protected.
     * Later visits reuse that flag so a large statement is not pulled again just to check.
     */
    protected function rememberPdfProtection(ExpenseStatementImport $import): void
    {
        $summary = $import->summary ?? [];
        if (array_key_exists('pdf_encrypted', $summary)) {
            return;
        }

        $info = $this->withLocalStatementFile($import, function (?string $path) use ($import) {
            return $path ? $this->parser->inspect($path, $import->pdf_password) : null;
        });

        if (! is_array($info)) {
            return;
        }

        $known = ($info['success'] ?? false) || ($info['error'] ?? null) === 'password_required';
        if (! $known || ! array_key_exists('encrypted', $info)) {
            return;
        }

        $summary['pdf_encrypted'] = (bool) $info['encrypted'];
        $import->summary = $summary;
        if (! empty($info['verification_code'])) {
            $import->verification_code = $info['verification_code'];
        }
        $import->save();
    }

    protected function guardStatementDownload(ExpenseStatementImport $expenseStatement): ?RedirectResponse
    {
        $this->authorize('view', $expenseStatement);

        if (! $this->statementFileExists($expenseStatement)) {
            abort(404, 'Statement file not found');
        }

        $this->rememberPdfProtection($expenseStatement);
        $expenseStatement->refresh();

        $encrypted = (bool) (($expenseStatement->summary ?? [])['pdf_encrypted'] ?? false);
        if ($encrypted && trim((string) $expenseStatement->pdf_password) === '') {
            return redirect()
                ->route('finance.expense-statements.document', $expenseStatement)
                ->withErrors(['pdf_password' => 'Enter the security code before opening this statement.']);
        }

        return null;
    }

    protected function statementDownloadName(ExpenseStatementImport $import): string
    {
        $name = trim((string) ($import->original_filename ?: 'statement.pdf'));
        $name = str_replace(['"', '\\', '/', "\r", "\n"], '', $name);
        if (! str_ends_with(strtolower($name), '.pdf')) {
            $name .= '.pdf';
        }

        return $name !== '' ? $name : 'statement.pdf';
    }

    /**
     * @template T
     * @param  callable(?string): T  $callback
     * @return T
     */
    protected function withLocalStatementFile(ExpenseStatementImport $import, callable $callback): mixed
    {
        $diskName = config('filesystems.private_disk', 'private');
        $driver = config("filesystems.disks.{$diskName}.driver", 'local');
        $path = storage_local_path($diskName, (string) $import->file_path);

        try {
            return $callback($path);
        } finally {
            if ($driver !== 'local' && is_file($path)) {
                @unlink($path);
            }
        }
    }
}
