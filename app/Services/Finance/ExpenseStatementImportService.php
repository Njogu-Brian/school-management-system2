<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseStatementImport;
use App\Models\ExpenseStatementLine;
use App\Models\ExpenseStatementRecipientProfile;
use App\Models\JournalEntry;
use App\Models\PaymentVoucher;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ExpenseWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExpenseStatementImportService
{
    protected ?int $chargeCategoryIdCache = null;

    protected bool $chargeCategoryResolved = false;

    public function __construct(
        protected MpesaExpenseStatementParser $parser,
        protected MpesaTransactionClassifier $classifier,
        protected ExpenseWorkflowService $expenseWorkflow,
        protected JournalPostingService $journalPosting,
        protected RecipientMemoryService $recipientMemory,
        protected InvoicePaymentLinker $invoiceLinker,
    ) {}

    /**
     * Id of the "Bank & Transaction Charges" category (code TXN_COST), or null if not seeded.
     */
    protected function chargeCategoryId(): ?int
    {
        if (! $this->chargeCategoryResolved) {
            $this->chargeCategoryIdCache = ExpenseCategory::where('code', 'TXN_COST')->value('id');
            $this->chargeCategoryResolved = true;
        }

        return $this->chargeCategoryIdCache;
    }

    /**
     * @return array{success: bool, import?: ExpenseStatementImport, error?: string, message?: string}
     */
    public function importMpesa(UploadedFile $file, int $userId, ?string $password = null): array
    {
        $storedPath = $file->store('expense-statements', config('filesystems.private_disk', 'private'));
        $absolutePath = storage_local_path(config('filesystems.private_disk', 'private'), $storedPath);

        $parseResult = $this->parser->parse($absolutePath, $password);
        if (! ($parseResult['success'] ?? false)) {
            Storage::disk(config('filesystems.private_disk', 'private'))->delete($storedPath);

            return [
                'success' => false,
                'error' => $parseResult['error'] ?? 'parse_failed',
                'message' => $parseResult['message'] ?? 'Failed to parse statement.',
            ];
        }

        return DB::transaction(function () use ($parseResult, $storedPath, $file, $userId) {
            $metadata = $parseResult['metadata'] ?? [];
            $import = ExpenseStatementImport::create([
                'uploaded_by' => $userId,
                'source' => ExpenseStatementImport::SOURCE_MPESA,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $storedPath,
                'period_start' => $metadata['period_start'] ?? null,
                'period_end' => $metadata['period_end'] ?? null,
                'account_name' => $metadata['customer_name'] ?? null,
                'account_number' => $metadata['mobile_number'] ?? null,
                'status' => ExpenseStatementImport::STATUS_PARSED,
                'summary' => $metadata,
            ]);

            $stats = $this->persistTransactions($import, $parseResult['transactions'] ?? []);
            $duplicates = (int) ($stats['duplicate_count'] ?? 0);
            unset($stats['duplicate_count']);

            // Whole statement already imported — discard the empty shell.
            if (($stats['line_count'] ?? 0) === 0 && $duplicates > 0) {
                Storage::disk(config('filesystems.private_disk', 'private'))->delete($storedPath);
                $import->delete();

                return [
                    'success' => false,
                    'error' => 'duplicate',
                    'message' => "This statement was already imported — all {$duplicates} transaction(s) are duplicates.",
                ];
            }

            $import->update($stats);

            return ['success' => true, 'import' => $import->fresh(['uploader']), 'duplicates' => $duplicates];
        });
    }

    /**
     * Store the uploaded file on the private disk and return [storedPath, absolutePath].
     *
     * @return array{0: string, 1: string}
     */
    public function storeUploadedFile(UploadedFile $file): array
    {
        $storedPath = $file->store('expense-statements', config('filesystems.private_disk', 'private'));
        $absolutePath = storage_local_path(config('filesystems.private_disk', 'private'), $storedPath);

        return [$storedPath, $absolutePath];
    }

    public function deleteStoredFile(string $storedPath): void
    {
        Storage::disk(config('filesystems.private_disk', 'private'))->delete($storedPath);
    }

    /**
     * Create a placeholder import row in the "parsing" state so the UI has an id
     * to poll while the background job extracts the PDF.
     */
    public function createPendingImport(UploadedFile $file, string $storedPath, int $userId): ExpenseStatementImport
    {
        return ExpenseStatementImport::create([
            'uploaded_by' => $userId,
            'source' => ExpenseStatementImport::SOURCE_MPESA,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'status' => ExpenseStatementImport::STATUS_PARSING,
        ]);
    }

    /**
     * Persist transactions collected by the chunked async parser onto an existing
     * (pending) import row, then finalise its metadata, stats and status.
     *
     * @param  array<int, array<string, mixed>>  $transactions
     * @param  array<string, mixed>  $metadata
     * @return array{line_count: int, duplicates: int}
     */
    public function persistParsedTransactions(ExpenseStatementImport $import, array $transactions, array $metadata): array
    {
        return DB::transaction(function () use ($import, $transactions, $metadata) {
            $import->update([
                'period_start' => $metadata['period_start'] ?? null,
                'period_end' => $metadata['period_end'] ?? null,
                'account_name' => $metadata['customer_name'] ?? null,
                'account_number' => $metadata['mobile_number'] ?? null,
                'summary' => $metadata,
            ]);

            $stats = $this->persistTransactions($import, $transactions);
            $duplicates = (int) ($stats['duplicate_count'] ?? 0);
            unset($stats['duplicate_count']);

            $stats['status'] = ExpenseStatementImport::STATUS_PARSED;
            $import->update($stats);

            return ['line_count' => (int) ($stats['line_count'] ?? 0), 'duplicates' => $duplicates];
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array<string, int|float>
     */
    protected function persistTransactions(ExpenseStatementImport $import, array $transactions): array
    {
        $profiles = ExpenseStatementRecipientProfile::query()->get()->keyBy('group_key');
        $lineCount = 0;
        $outgoingCount = 0;
        $outgoingTotal = 0.0;
        $duplicateCount = 0;

        // Prepare valid rows + their reference fingerprints first.
        $prepared = [];
        foreach ($transactions as $txn) {
            $withdrawn = max(0, (float) ($txn['debit'] ?? 0));
            $paidIn = max(0, (float) ($txn['credit'] ?? 0));

            if ($withdrawn <= 0 && $paidIn <= 0) {
                continue;
            }

            $narration = trim((string) ($txn['particulars'] ?? ''));
            if ($narration === '') {
                continue;
            }

            $completedAt = $this->parseCompletedAt($txn);
            $fingerprint = ExpenseStatementLine::fingerprint(
                $txn['transaction_code'] ?? null,
                $completedAt,
                $narration,
            );

            $prepared[] = [
                'txn' => $txn,
                'withdrawn' => $withdrawn,
                'paidIn' => $paidIn,
                'narration' => $narration,
                'completedAt' => $completedAt,
                'fingerprint' => $fingerprint,
            ];
        }

        // Duplicate detection by reference: skip any transaction whose fingerprint
        // already exists in a previously imported statement.
        $existing = [];
        $fingerprints = array_values(array_unique(array_column($prepared, 'fingerprint')));
        foreach (array_chunk($fingerprints, 1000) as $chunk) {
            foreach (ExpenseStatementLine::whereIn('line_fingerprint', $chunk)->pluck('line_fingerprint') as $fp) {
                $existing[$fp] = true;
            }
        }

        $seen = [];

        foreach ($prepared as $row) {
            $fingerprint = $row['fingerprint'];

            if (isset($existing[$fingerprint]) || isset($seen[$fingerprint])) {
                $duplicateCount++;
                continue;
            }
            $seen[$fingerprint] = true;

            $txn = $row['txn'];
            $withdrawn = $row['withdrawn'];
            $paidIn = $row['paidIn'];
            $narration = $row['narration'];
            $completedAt = $row['completedAt'];

            $classification = $this->classifier->classify($narration, $withdrawn, $paidIn);
            $direction = $withdrawn > 0 ? 'out' : 'in';

            if ($direction === 'out') {
                $outgoingCount++;
                $outgoingTotal += $withdrawn;
            }

            $profile = $profiles->get($classification['group_key']);

            $reviewStatus = ExpenseStatementLine::REVIEW_PENDING;
            $categoryId = null;
            $description = null;
            $vendorName = $profile?->default_vendor_name;

            if ($profile) {
                if ($profile->is_business_expense) {
                    $reviewStatus = ExpenseStatementLine::REVIEW_CONFIRMED;
                    $categoryId = $profile->expense_category_id;
                    $description = $profile->default_description;
                } elseif (! $profile->is_business_expense && $profile->default_description === 'personal') {
                    $reviewStatus = ExpenseStatementLine::REVIEW_PERSONAL;
                }
            }

            if ($classification['is_transaction_fee'] && $profile && $profile->is_business_expense) {
                $reviewStatus = ExpenseStatementLine::REVIEW_CONFIRMED;
                $categoryId = $profile->expense_category_id;
            }

            ExpenseStatementLine::create([
                'import_id' => $import->id,
                'receipt_no' => $txn['transaction_code'] ?? null,
                'completed_at' => $completedAt,
                'narration' => $narration,
                'line_fingerprint' => $fingerprint,
                'withdrawn_amount' => $withdrawn,
                'paid_in_amount' => $paidIn,
                'direction' => $direction,
                'transaction_type' => $classification['transaction_type'],
                'is_transaction_fee' => $classification['is_transaction_fee'],
                'recipient_name' => $classification['recipient_name'],
                'vendor_name' => $classification['is_transaction_fee'] ? null : ($vendorName ?: null),
                'recipient_phone' => $classification['recipient_phone'],
                'paybill_number' => $classification['paybill_number'],
                'account_reference' => $classification['account_reference'],
                'merchant_reference' => $classification['merchant_reference'],
                'group_key' => $classification['group_key'],
                'review_status' => $reviewStatus,
                'expense_category_id' => $categoryId,
                'expense_description' => $description,
                'raw_data' => $txn,
            ]);

            $lineCount++;
        }

        $this->linkTransactionFeesToParents($import);

        // Auto-categorise & group new lines from previously classified recipients.
        $this->recipientMemory->applyToPendingLines($import->id);

        // Auto-link Azanet paybill payments to the seeded internet invoices (no duplicate expense).
        $this->invoiceLinker->linkAzanet($import->id);

        return [
            'line_count' => $lineCount,
            'outgoing_count' => $outgoingCount,
            'outgoing_total' => round($outgoingTotal, 2),
            'duplicate_count' => $duplicateCount,
        ];
    }

    protected function linkTransactionFeesToParents(ExpenseStatementImport $import): void
    {
        $lines = $import->lines()->get();
        $primaryByReceipt = $lines
            ->where('is_transaction_fee', false)
            ->where('direction', 'out')
            ->keyBy('receipt_no');

        foreach ($lines->where('is_transaction_fee', true) as $feeLine) {
            $parent = $primaryByReceipt->get($feeLine->receipt_no);
            if (! $parent) {
                continue;
            }

            $categoryId = $parent->expense_category_id;
            if ($parent->review_status === ExpenseStatementLine::REVIEW_CONFIRMED && ($chargeId = $this->chargeCategoryId())) {
                $categoryId = $chargeId;
            }

            $feeLine->update([
                'group_key' => $parent->group_key,
                'recipient_name' => $parent->recipient_name,
                'paybill_number' => $parent->paybill_number,
                'account_reference' => $parent->account_reference,
                'review_status' => $parent->review_status,
                'expense_category_id' => $categoryId,
                'expense_description' => $parent->expense_description,
            ]);
        }
    }

    protected function parseCompletedAt(array $txn): ?Carbon
    {
        $completedAt = $txn['completed_at'] ?? null;
        if (is_string($completedAt) && $completedAt !== '') {
            try {
                return Carbon::parse($completedAt);
            } catch (\Throwable) {
                // fall through
            }
        }

        $date = $txn['tran_date'] ?? null;
        if ($date) {
            return Carbon::parse($date)->startOfDay();
        }

        return null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function groupedLines(ExpenseStatementImport $import, ?string $filter = null, ?string $search = null)
    {
        $query = $import->lines()
            ->where('direction', 'out')
            ->with('category');

        if ($filter === 'pending') {
            $query->where('review_status', ExpenseStatementLine::REVIEW_PENDING);
        } elseif ($filter === 'confirmed' || $filter === 'business') {
            $query->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED);
        } elseif ($filter === 'personal') {
            $query->where('review_status', ExpenseStatementLine::REVIEW_PERSONAL);
        } elseif ($filter === 'uncategorized') {
            // "Uncategorized" = still needs attention: no category and not already
            // marked personal or ignored.
            $query->whereNull('expense_category_id')
                ->whereNotIn('review_status', [
                    ExpenseStatementLine::REVIEW_PERSONAL,
                    ExpenseStatementLine::REVIEW_IGNORED,
                ]);
        } elseif ($filter === 'fees') {
            $query->where('is_transaction_fee', true);
        }

        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('narration', 'like', $like)
                    ->orWhere('recipient_name', 'like', $like)
                    ->orWhere('recipient_phone', 'like', $like)
                    ->orWhere('paybill_number', 'like', $like)
                    ->orWhere('account_reference', 'like', $like)
                    ->orWhere('receipt_no', 'like', $like);
            });
        }

        $lines = $query->orderByDesc('completed_at')->get();

        return $lines->groupBy('group_key')->map(function ($groupLines) {
            $first = $groupLines->first();

            $vendorName = $this->resolveGroupVendorName($groupLines);

            return (object) [
                'group_key' => $first->group_key,
                'vendor_name' => $vendorName,
                'display_name' => $vendorName
                    ?: $first->recipient_name
                    ?: ($first->paybill_number ? 'Paybill ' . $first->paybill_number : null)
                    ?: $first->narration,
                'recipient_name' => $first->recipient_name,
                'transaction_type' => $first->transaction_type,
                'transaction_type_label' => $first->transaction_type_label,
                'recipient_phone' => $first->recipient_phone,
                'paybill_number' => $first->paybill_number,
                'account_reference' => $first->account_reference,
                'transaction_count' => $groupLines->count(),
                'total_amount' => round((float) $groupLines->sum('withdrawn_amount'), 2),
                'fee_amount' => round((float) $groupLines->where('is_transaction_fee', true)->sum('withdrawn_amount'), 2),
                'pending_count' => $groupLines->where('review_status', ExpenseStatementLine::REVIEW_PENDING)->count(),
                'confirmed_count' => $groupLines->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED)->count(),
                'review_status' => $this->resolveGroupReviewStatus($groupLines),
                'expense_category_id' => $this->resolveGroupCategoryId($groupLines),
                'expense_description' => $this->resolveGroupDescription($groupLines),
                'lines' => $groupLines,
            ];
        })->sortByDesc('total_amount')->values();
    }

    protected function resolveGroupReviewStatus($groupLines): string
    {
        $statuses = $groupLines->pluck('review_status')->unique()->values();

        if ($statuses->count() === 1) {
            return (string) $statuses->first();
        }

        if ($groupLines->every(fn ($line) => $line->review_status === ExpenseStatementLine::REVIEW_CONFIRMED)) {
            return ExpenseStatementLine::REVIEW_CONFIRMED;
        }

        if ($groupLines->every(fn ($line) => in_array($line->review_status, [
            ExpenseStatementLine::REVIEW_PERSONAL,
            ExpenseStatementLine::REVIEW_IGNORED,
        ], true))) {
            return ExpenseStatementLine::REVIEW_PERSONAL;
        }

        return 'mixed';
    }

    protected function resolveGroupCategoryId($groupLines): ?int
    {
        $categories = $groupLines->pluck('expense_category_id')->filter()->unique()->values();

        return $categories->count() === 1 ? (int) $categories->first() : null;
    }

    protected function resolveGroupVendorName($groupLines): ?string
    {
        $names = $groupLines->pluck('vendor_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        return $names->count() >= 1 ? (string) $names->first() : null;
    }

    protected function resolveGroupDescription($groupLines): ?string
    {
        $descriptions = $groupLines->pluck('expense_description')->filter()->unique()->values();

        return $descriptions->count() === 1 ? (string) $descriptions->first() : null;
    }

    public function applyGroupReview(
        ExpenseStatementImport $import,
        string $groupKey,
        string $reviewStatus,
        ?int $categoryId,
        ?string $description,
        bool $remember,
        int $userId,
        ?string $vendorName = null,
    ): bool {
        return DB::transaction(function () use ($import, $groupKey, $reviewStatus, $categoryId, $description, $remember, $userId, $vendorName) {
            $lines = $import->lines()->where('group_key', $groupKey)->get();
            if ($lines->isEmpty()) {
                return false;
            }

            $first = $lines->first();

            // Re-editing a group that already produced expenses (and is still a business
            // expense) reverses those expenses and sends the transactions back to pending,
            // so the edited classification is reviewed and submitted afresh.
            $hasExpenses = $lines->contains(fn ($line) => ! is_null($line->expense_id));
            $reversed = $reviewStatus === ExpenseStatementLine::REVIEW_CONFIRMED && $hasExpenses;
            $effectiveStatus = $reversed
                ? ExpenseStatementLine::REVIEW_PENDING
                : $reviewStatus;

            // Leaving a live "business expense" removes any not-yet-approved expenses.
            if ($effectiveStatus !== ExpenseStatementLine::REVIEW_CONFIRMED) {
                $this->detachExpensesForLines($import, $lines);
            }

            foreach ($lines as $line) {
                $this->applyReviewToLine($line, $effectiveStatus, $categoryId, $description, $vendorName);
            }

            if ($remember && $reviewStatus !== ExpenseStatementLine::REVIEW_PENDING) {
                $rememberedVendor = (trim((string) $vendorName) !== '') ? trim((string) $vendorName) : $first->fresh()->vendor_name;

                ExpenseStatementRecipientProfile::updateOrCreate(
                    ['group_key' => $groupKey],
                    [
                        'display_name' => $first->recipient_name
                            ?: ($first->paybill_number ? 'Paybill ' . $first->paybill_number : 'Unknown'),
                        'default_vendor_name' => $rememberedVendor,
                        'transaction_type' => $first->transaction_type,
                        'is_business_expense' => $reviewStatus === ExpenseStatementLine::REVIEW_CONFIRMED,
                        'expense_category_id' => $categoryId,
                        'default_description' => $reviewStatus === ExpenseStatementLine::REVIEW_PERSONAL
                            ? 'personal'
                            : $description,
                        'updated_by' => $userId,
                    ]
                );
            }

            $this->refreshImportConfirmedTotal($import);

            return $reversed;
        });
    }

    public function applyLineReview(
        ExpenseStatementImport $import,
        int $lineId,
        string $reviewStatus,
        ?int $categoryId,
        ?string $description,
        int $userId,
        ?string $vendorName = null,
    ): void {
        DB::transaction(function () use ($import, $lineId, $reviewStatus, $categoryId, $description, $userId, $vendorName) {
            $line = $import->lines()->whereKey($lineId)->firstOrFail();

            $relatedFees = collect();
            if (! $line->is_transaction_fee && $line->receipt_no) {
                $relatedFees = $import->lines()
                    ->where('receipt_no', $line->receipt_no)
                    ->where('is_transaction_fee', true)
                    ->whereKeyNot($line->id)
                    ->get();
            }

            // Leaving "business expense" removes any not-yet-approved expense created from this transaction.
            if ($reviewStatus !== ExpenseStatementLine::REVIEW_CONFIRMED) {
                $this->detachExpensesForLines($import, collect([$line])->merge($relatedFees));
            }

            $this->applyReviewToLine($line, $reviewStatus, $categoryId, $description, $vendorName);

            // The vendor override applies to the primary transaction, not its M-Pesa charge line.
            $relatedFees->each(fn (ExpenseStatementLine $feeLine) => $this->applyReviewToLine(
                $feeLine,
                $reviewStatus,
                $categoryId,
                $description,
            ));

            $this->refreshImportConfirmedTotal($import);
        });
    }

    protected function applyReviewToLine(
        ExpenseStatementLine $line,
        string $reviewStatus,
        ?int $categoryId,
        ?string $description,
        ?string $vendorName = null,
    ): void {
        // Transaction charges are always booked to Bank & Transaction Charges.
        if ($line->is_transaction_fee && $reviewStatus === ExpenseStatementLine::REVIEW_CONFIRMED) {
            if ($chargeId = $this->chargeCategoryId()) {
                $categoryId = $chargeId;
            }
        }

        $attributes = [
            'review_status' => $reviewStatus,
            'expense_category_id' => $reviewStatus === ExpenseStatementLine::REVIEW_CONFIRMED ? $categoryId : null,
            'expense_description' => $description,
        ];

        // Only overwrite the vendor override when a (non-blank) name is supplied,
        // so re-classifying without touching the vendor field keeps any prior override.
        if ($vendorName !== null && trim($vendorName) !== '') {
            $attributes['vendor_name'] = trim($vendorName);
        }

        $line->update($attributes);
    }

    /**
     * Submit: create one SUBMITTED expense per confirmed transaction that does not yet
     * have one (its own date and amount, plus the M-Pesa charge as a separate line).
     * No GL posting happens here — that occurs on approval.
     *
     * @return int Number of expenses created.
     */
    public function createExpensesForImport(ExpenseStatementImport $import, int $userId): int
    {
        $user = User::find($userId);
        if (! $user) {
            return 0;
        }

        return DB::transaction(function () use ($import, $user) {
            $lines = $import->lines()
                ->where('direction', 'out')
                ->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED)
                ->whereNull('expense_id')
                ->orderBy('completed_at')
                ->get();

            if ($lines->isEmpty()) {
                return 0;
            }

            $chargeCategory = ExpenseCategory::where('code', 'TXN_COST')->first();

            $feesByReceipt = $lines->where('is_transaction_fee', true)
                ->filter(fn ($line) => (float) $line->withdrawn_amount > 0)
                ->groupBy('receipt_no');

            $created = 0;

            foreach ($lines->where('is_transaction_fee', false) as $primary) {
                if (! $primary->expense_category_id) {
                    continue;
                }

                $relatedFees = $primary->receipt_no
                    ? ($feesByReceipt->get($primary->receipt_no) ?? collect())
                    : collect();

                $this->createSubmittedExpenseFromTransaction($import, $primary, $relatedFees, $chargeCategory, $user);
                $created++;
            }

            return $created;
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ExpenseStatementLine>  $fees
     */
    protected function createSubmittedExpenseFromTransaction(
        ExpenseStatementImport $import,
        ExpenseStatementLine $primary,
        $fees,
        ?ExpenseCategory $chargeCategory,
        User $user,
    ): void {
        $recipientLabel = $primary->payeeName()
            ?: mb_substr($primary->narration, 0, 80);

        $vendor = Vendor::firstOrCreateByName($primary->payeeName());

        $expense = Expense::create([
            'source_type' => 'mpesa_statement',
            'vendor_id' => $vendor?->id,
            'requested_by' => $user->id,
            'expense_date' => $primary->completed_at?->toDateString() ?? now()->toDateString(),
            'currency' => 'KES',
            'status' => Expense::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'notes' => sprintf(
                'M-Pesa %s — %s%s',
                $primary->receipt_no ?: 'transaction',
                $recipientLabel,
                $primary->completed_at ? ' on ' . $primary->completed_at->format('Y-m-d H:i') : ''
            ),
        ]);

        $expense->lines()->create([
            'category_id' => $primary->expense_category_id,
            'description' => $primary->expense_description ?: $primary->narration,
            'qty' => 1,
            'unit_cost' => $primary->withdrawn_amount,
            'tax_rate' => 0,
        ]);
        $primary->update(['expense_id' => $expense->id]);

        $expense->recalculateTotals();
        $expense->save();

        // Transaction/bank charges are recorded as their OWN "Bank Charges"
        // expense (no vendor) so they are never attributed to the merchant.
        $this->createChargeExpenseFromFees($fees, $chargeCategory, $primary, $user);
    }

    /**
     * Record M-Pesa / bank transaction charges as a standalone "Bank Charges"
     * expense with no vendor, keyed to the originating transaction.
     *
     * @param  \Illuminate\Support\Collection<int, ExpenseStatementLine>  $fees
     */
    protected function createChargeExpenseFromFees(
        $fees,
        ?ExpenseCategory $chargeCategory,
        ExpenseStatementLine $primary,
        User $user,
    ): void {
        $fees = collect($fees)->filter(fn ($fee) => (float) $fee->withdrawn_amount > 0);
        if ($fees->isEmpty() || ! $chargeCategory) {
            return;
        }

        $charge = Expense::create([
            'source_type' => 'mpesa_statement',
            'vendor_id' => null,
            'requested_by' => $user->id,
            'expense_date' => $primary->completed_at?->toDateString() ?? now()->toDateString(),
            'currency' => 'KES',
            'status' => Expense::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'notes' => 'Bank & M-Pesa transaction charges'
                . ($primary->receipt_no ? ' for ' . $primary->receipt_no : ''),
        ]);

        foreach ($fees as $fee) {
            $charge->lines()->create([
                'category_id' => $chargeCategory->id,
                'description' => 'M-Pesa transaction charge' . ($fee->receipt_no ? ' (' . $fee->receipt_no . ')' : ''),
                'qty' => 1,
                'unit_cost' => $fee->withdrawn_amount,
                'tax_rate' => 0,
            ]);
            $fee->update(['expense_id' => $charge->id]);
        }

        $charge->recalculateTotals();
        $charge->save();
    }

    /**
     * Approve statement-sourced expenses (single, by category, or all submitted),
     * marking each Paid and posting it to the general ledger.
     *
     * @return int Number of expenses approved.
     */
    public function approveStatementExpenses(
        ExpenseStatementImport $import,
        int $userId,
        ?int $expenseId = null,
        ?int $categoryId = null,
    ): int {
        $user = User::find($userId);
        if (! $user) {
            return 0;
        }

        $expenseIds = $import->lines()
            ->whereNotNull('expense_id')
            ->pluck('expense_id')
            ->unique()
            ->values();

        if ($expenseIds->isEmpty()) {
            return 0;
        }

        $query = Expense::whereIn('id', $expenseIds)
            ->where('status', Expense::STATUS_SUBMITTED);

        if ($expenseId) {
            $query->whereKey($expenseId);
        } elseif ($categoryId) {
            $query->whereHas('lines', fn ($q) => $q->where('category_id', $categoryId));
        }

        $expenses = $query->with('lines.category.account')->get();

        $creditAccount = Account::where('code', '1002')->first()
            ?? Account::where('code', '1000')->first();

        $approved = 0;
        foreach ($expenses as $expense) {
            $this->approveStatementExpense($expense, $user, $creditAccount);
            $approved++;
        }

        return $approved;
    }

    protected function approveStatementExpense(Expense $expense, User $user, ?Account $creditAccount): void
    {
        $expense->status = Expense::STATUS_APPROVED;
        $expense->approved_by = $user->id;
        $expense->approved_at = now();
        $expense->save();

        $voucher = PaymentVoucher::create([
            'expense_id' => $expense->id,
            'payee' => optional($expense->vendor)->name ?? 'M-Pesa expense',
            'payment_method' => 'M-Pesa',
            'payment_date' => $expense->expense_date,
            'amount' => $expense->total,
            'status' => 'approved',
            'prepared_by' => $user->id,
            'approved_by' => $user->id,
        ]);

        $this->expenseWorkflow->payVoucher($voucher, $user, [
            'amount' => $expense->total,
            'paid_at' => $expense->expense_date,
            'account_id' => $creditAccount?->id,
            'account_source' => 'M-Pesa',
        ]);
    }

    /**
     * Remove not-yet-approved expenses created from the given statement lines, and
     * clear their links. Throws if any linked expense has already been approved/paid.
     *
     * @param  \Illuminate\Support\Collection<int, ExpenseStatementLine>  $lines
     */
    protected function detachExpensesForLines(ExpenseStatementImport $import, $lines): void
    {
        $expenseIds = $lines->pluck('expense_id')->filter()->unique()->values();
        if ($expenseIds->isEmpty()) {
            return;
        }

        $expenses = Expense::whereIn('id', $expenseIds)->get();

        foreach ($expenses as $expense) {
            if (in_array($expense->status, [Expense::STATUS_APPROVED, Expense::STATUS_PAID], true)) {
                throw new \RuntimeException(
                    "Expense {$expense->expense_no} has already been approved and cannot be moved back to pending."
                );
            }
        }

        $import->lines()->whereIn('expense_id', $expenseIds)->update(['expense_id' => null]);

        foreach ($expenses as $expense) {
            $expense->vouchers()->delete();
            $expense->lines()->delete();
            $expense->delete();
        }
    }

    /**
     * Reject a statement-sourced expense: delete it and send its transactions
     * back to "uncategorized" (pending, no category). Only allowed before posting.
     */
    public function rejectStatementExpense(ExpenseStatementImport $import, int $expenseId): void
    {
        DB::transaction(function () use ($import, $expenseId) {
            $expense = Expense::find($expenseId);
            $linkedCount = $import->lines()->where('expense_id', $expenseId)->count();

            if (! $expense || $linkedCount === 0) {
                throw new \RuntimeException('This expense is not linked to the current statement.');
            }

            if (in_array($expense->status, [Expense::STATUS_APPROVED, Expense::STATUS_PAID], true)) {
                throw new \RuntimeException(
                    "Expense {$expense->expense_no} is already approved/posted and cannot be rejected."
                );
            }

            $import->lines()->where('expense_id', $expenseId)->update([
                'expense_id' => null,
                'review_status' => ExpenseStatementLine::REVIEW_PENDING,
                'expense_category_id' => null,
            ]);

            $expense->vouchers()->delete();
            $expense->lines()->delete();
            $expense->delete();

            $this->refreshImportConfirmedTotal($import);
        });
    }

    /**
     * Reverse posted (approved/paid) statement expenses (single, by category, or all).
     * Each posts a contra journal entry, removes the voucher/payment, and sends the
     * transactions back to "uncategorized" (pending, no category) to be re-done.
     *
     * @return int Number of expenses reversed.
     */
    public function reverseStatementExpenses(
        ExpenseStatementImport $import,
        int $userId,
        ?int $expenseId = null,
        ?int $categoryId = null,
    ): int {
        $user = User::find($userId);
        if (! $user) {
            return 0;
        }

        $expenseIds = $import->lines()
            ->whereNotNull('expense_id')
            ->pluck('expense_id')
            ->unique()
            ->values();

        if ($expenseIds->isEmpty()) {
            return 0;
        }

        $query = Expense::whereIn('id', $expenseIds)
            ->whereIn('status', [Expense::STATUS_APPROVED, Expense::STATUS_PAID]);

        if ($expenseId) {
            $query->whereKey($expenseId);
        } elseif ($categoryId) {
            $query->whereHas('lines', fn ($q) => $q->where('category_id', $categoryId));
        }

        $expenses = $query->with('vouchers')->get();

        $reversed = 0;
        foreach ($expenses as $expense) {
            $this->reverseExpensePosting($import, $expense, $user);
            $reversed++;
        }

        return $reversed;
    }

    protected function reverseExpensePosting(ExpenseStatementImport $import, Expense $expense, User $user): void
    {
        DB::transaction(function () use ($import, $expense, $user) {
            foreach ($expense->vouchers as $voucher) {
                if ($voucher->journal_entry_id) {
                    $entry = JournalEntry::with('lines')->find($voucher->journal_entry_id);
                    if ($entry && $entry->status === JournalEntry::STATUS_POSTED) {
                        $this->journalPosting->reverse($entry, $user);
                    }
                }

                $voucher->payments()->delete();
                $voucher->delete();
            }

            $import->lines()->where('expense_id', $expense->id)->update([
                'expense_id' => null,
                'review_status' => ExpenseStatementLine::REVIEW_PENDING,
                'expense_category_id' => null,
            ]);

            $expense->lines()->delete();
            $expense->delete();

            $this->refreshImportConfirmedTotal($import);
        });
    }

    /**
     * Edit a statement-sourced expense in place: vendor and description may change at
     * any status; category may only change before the expense is posted to the ledger.
     */
    public function updateStatementExpense(
        ExpenseStatementImport $import,
        int $expenseId,
        ?string $vendorName,
        ?int $categoryId,
        ?string $description,
    ): void {
        DB::transaction(function () use ($import, $expenseId, $vendorName, $categoryId, $description) {
            $expense = Expense::with('lines')->find($expenseId);
            $statementLines = $import->lines()->where('expense_id', $expenseId);

            if (! $expense || $statementLines->count() === 0) {
                throw new \RuntimeException('This expense is not linked to the current statement.');
            }

            $posted = in_array($expense->status, [Expense::STATUS_APPROVED, Expense::STATUS_PAID], true);
            $chargeId = $this->chargeCategoryId();
            $primaryLines = $expense->lines->filter(fn ($line) => $line->category_id !== $chargeId);

            $vendorName = trim((string) $vendorName);
            if ($vendorName !== '') {
                $vendor = Vendor::firstOrCreateByName($vendorName);
                if ($vendor) {
                    $expense->vendor_id = $vendor->id;
                    $expense->save();
                }
                (clone $statementLines)->where('is_transaction_fee', false)->update(['vendor_name' => $vendorName]);
            }

            if ($description !== null && trim($description) !== '') {
                $description = trim($description);
                foreach ($primaryLines as $line) {
                    $line->description = $description;
                    $line->save();
                }
                (clone $statementLines)->where('is_transaction_fee', false)->update(['expense_description' => $description]);
            }

            if ($categoryId) {
                if ($posted) {
                    throw new \RuntimeException(
                        "Expense {$expense->expense_no} is already approved/posted; its category can't be changed."
                    );
                }
                foreach ($primaryLines as $line) {
                    $line->category_id = $categoryId;
                    $line->save();
                }
                (clone $statementLines)->where('is_transaction_fee', false)->update(['expense_category_id' => $categoryId]);
            }
        });
    }

    /**
     * Expenses created from this statement, grouped by their primary (non-charge) category.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function importExpenseGroups(ExpenseStatementImport $import)
    {
        $expenseIds = $import->lines()
            ->whereNotNull('expense_id')
            ->pluck('expense_id')
            ->unique()
            ->values();

        if ($expenseIds->isEmpty()) {
            return collect();
        }

        $expenses = Expense::whereIn('id', $expenseIds)
            ->with(['lines.category', 'vendor'])
            ->orderBy('expense_date')
            ->get();

        $chargeCategoryId = $this->chargeCategoryId();

        return $expenses->groupBy(function (Expense $expense) use ($chargeCategoryId) {
            $primaryLine = $expense->lines->first(fn ($line) => $line->category_id !== $chargeCategoryId)
                ?? $expense->lines->first();

            return $primaryLine?->category_id ?? 0;
        })->map(function ($groupExpenses, $categoryId) use ($chargeCategoryId) {
            $first = $groupExpenses->first();
            $primaryLine = $first->lines->first(fn ($line) => $line->category_id !== $chargeCategoryId)
                ?? $first->lines->first();

            return (object) [
                'category_id' => $categoryId ?: null,
                'category_name' => optional($primaryLine?->category)->name ?? 'Uncategorized',
                'expenses' => $groupExpenses->sortBy('expense_date')->values(),
                'count' => $groupExpenses->count(),
                'submitted_count' => $groupExpenses->where('status', Expense::STATUS_SUBMITTED)->count(),
                'posted_count' => $groupExpenses->whereIn('status', [Expense::STATUS_APPROVED, Expense::STATUS_PAID])->count(),
                'total' => round((float) $groupExpenses->sum('total'), 2),
            ];
        })->sortByDesc('total')->values();
    }

    public function pendingExpenseCreationCount(ExpenseStatementImport $import): int
    {
        return $import->lines()
            ->where('direction', 'out')
            ->where('is_transaction_fee', false)
            ->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED)
            ->whereNull('expense_id')
            ->whereNotNull('expense_category_id')
            ->count();
    }

    protected function refreshImportConfirmedTotal(ExpenseStatementImport $import): void
    {
        $confirmedTotal = $import->lines()
            ->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED)
            ->sum('withdrawn_amount');

        $import->update(['confirmed_expense_total' => $confirmedTotal]);
    }

    /**
     * @return array{created: int, expense_ids: array<int, int>}
     */
    public function generateExpenseDrafts(ExpenseStatementImport $import, int $userId): array
    {
        return DB::transaction(function () use ($import, $userId) {
            $confirmed = $import->lines()
                ->where('direction', 'out')
                ->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED)
                ->whereNull('expense_id')
                ->whereNotNull('expense_category_id')
                ->orderBy('completed_at')
                ->get();

            if ($confirmed->isEmpty()) {
                return ['created' => 0, 'expense_ids' => []];
            }

            $expenseIds = [];

            $grouped = $confirmed->groupBy(fn (ExpenseStatementLine $line) => $line->group_key . '|' . $line->expense_category_id);

            foreach ($grouped as $lines) {
                /** @var \Illuminate\Support\Collection<int, ExpenseStatementLine> $lines */
                $first = $lines->first();
                $recipientLabel = $first->payeeName()
                    ?: mb_substr($first->narration, 0, 80);
                $vendor = Vendor::firstOrCreateByName($first->payeeName());

                $expense = Expense::create([
                    'source_type' => 'mpesa_statement',
                    'vendor_id' => $vendor?->id,
                    'requested_by' => $userId,
                    'expense_date' => $lines->sortBy('completed_at')->first()?->completed_at?->toDateString()
                        ?? now()->toDateString(),
                    'currency' => 'KES',
                    'status' => Expense::STATUS_DRAFT,
                    'notes' => sprintf(
                        'M-Pesa statement import #%d — %s (%d transaction(s))',
                        $import->id,
                        $recipientLabel,
                        $lines->count()
                    ),
                ]);

                foreach ($lines as $statementLine) {
                    $expense->lines()->create([
                        'category_id' => $statementLine->expense_category_id,
                        'description' => $statementLine->expense_description ?: $statementLine->narration,
                        'qty' => 1,
                        'unit_cost' => $statementLine->withdrawn_amount,
                        'tax_rate' => 0,
                    ]);

                    $statementLine->update(['expense_id' => $expense->id]);
                }

                $expense->recalculateTotals();
                $expense->save();

                $expenseIds[] = $expense->id;
            }

            return ['created' => count($expenseIds), 'expense_ids' => $expenseIds];
        });
    }

    public function confirmedDraftStats(ExpenseStatementImport $import): array
    {
        $confirmed = $import->lines()
            ->where('direction', 'out')
            ->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED);

        return [
            'confirmed_count' => (clone $confirmed)->count(),
            'unconverted_count' => (clone $confirmed)->whereNull('expense_id')->count(),
            'unconverted_total' => (float) (clone $confirmed)->whereNull('expense_id')->sum('withdrawn_amount'),
            'converted_count' => (clone $confirmed)->whereNotNull('expense_id')->count(),
        ];
    }
}
