<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseStatementImport;
use App\Models\ExpenseStatementLine;
use App\Models\ExpenseStatementRecipientProfile;
use App\Models\PaymentVoucher;
use App\Models\User;
use App\Services\ExpenseWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExpenseStatementImportService
{
    public function __construct(
        protected MpesaExpenseStatementParser $parser,
        protected MpesaTransactionClassifier $classifier,
        protected ExpenseWorkflowService $expenseWorkflow,
    ) {}

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
            $import->update($stats);

            return ['success' => true, 'import' => $import->fresh(['uploader'])];
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

            $classification = $this->classifier->classify($narration, $withdrawn, $paidIn);
            $direction = $withdrawn > 0 ? 'out' : 'in';

            if ($direction === 'out') {
                $outgoingCount++;
                $outgoingTotal += $withdrawn;
            }

            $completedAt = $this->parseCompletedAt($txn);
            $profile = $profiles->get($classification['group_key']);

            $reviewStatus = ExpenseStatementLine::REVIEW_PENDING;
            $categoryId = null;
            $description = null;

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
                'line_fingerprint' => ExpenseStatementLine::fingerprint(
                    $txn['transaction_code'] ?? null,
                    $completedAt,
                    $narration,
                ),
                'withdrawn_amount' => $withdrawn,
                'paid_in_amount' => $paidIn,
                'direction' => $direction,
                'transaction_type' => $classification['transaction_type'],
                'is_transaction_fee' => $classification['is_transaction_fee'],
                'recipient_name' => $classification['recipient_name'],
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

        return [
            'line_count' => $lineCount,
            'outgoing_count' => $outgoingCount,
            'outgoing_total' => round($outgoingTotal, 2),
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

            $feeLine->update([
                'group_key' => $parent->group_key,
                'recipient_name' => $parent->recipient_name,
                'paybill_number' => $parent->paybill_number,
                'account_reference' => $parent->account_reference,
                'review_status' => $parent->review_status,
                'expense_category_id' => $parent->expense_category_id,
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
    public function groupedLines(ExpenseStatementImport $import, ?string $filter = null)
    {
        $query = $import->lines()
            ->where('direction', 'out')
            ->with('category');

        if ($filter === 'pending') {
            $query->where('review_status', ExpenseStatementLine::REVIEW_PENDING);
        } elseif ($filter === 'confirmed') {
            $query->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED);
        } elseif ($filter === 'fees') {
            $query->where('is_transaction_fee', true);
        }

        $lines = $query->orderByDesc('completed_at')->get();

        return $lines->groupBy('group_key')->map(function ($groupLines) {
            $first = $groupLines->first();

            return (object) [
                'group_key' => $first->group_key,
                'display_name' => $first->recipient_name
                    ?: ($first->paybill_number ? 'Paybill ' . $first->paybill_number : null)
                    ?: $first->narration,
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
        int $userId
    ): void {
        DB::transaction(function () use ($import, $groupKey, $reviewStatus, $categoryId, $description, $remember, $userId) {
            $lines = $import->lines()->where('group_key', $groupKey)->get();
            if ($lines->isEmpty()) {
                return;
            }

            $first = $lines->first();

            foreach ($lines as $line) {
                $this->applyReviewToLine($line, $reviewStatus, $categoryId, $description);
            }

            if ($remember && $reviewStatus !== ExpenseStatementLine::REVIEW_PENDING) {
                ExpenseStatementRecipientProfile::updateOrCreate(
                    ['group_key' => $groupKey],
                    [
                        'display_name' => $first->recipient_name
                            ?: ($first->paybill_number ? 'Paybill ' . $first->paybill_number : 'Unknown'),
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

            if ($reviewStatus === ExpenseStatementLine::REVIEW_CONFIRMED) {
                $this->convertGroupToExpense($import, $groupKey, $userId);
            }

            $this->refreshImportConfirmedTotal($import);
        });
    }

    public function applyLineReview(
        ExpenseStatementImport $import,
        int $lineId,
        string $reviewStatus,
        ?int $categoryId,
        ?string $description,
        int $userId,
    ): void {
        DB::transaction(function () use ($import, $lineId, $reviewStatus, $categoryId, $description, $userId) {
            $line = $import->lines()->whereKey($lineId)->firstOrFail();

            $this->applyReviewToLine($line, $reviewStatus, $categoryId, $description);

            if (! $line->is_transaction_fee && $line->receipt_no) {
                $import->lines()
                    ->where('receipt_no', $line->receipt_no)
                    ->where('is_transaction_fee', true)
                    ->whereKeyNot($line->id)
                    ->get()
                    ->each(fn (ExpenseStatementLine $feeLine) => $this->applyReviewToLine(
                        $feeLine,
                        $reviewStatus,
                        $categoryId,
                        $description,
                    ));
            }

            if ($reviewStatus === ExpenseStatementLine::REVIEW_CONFIRMED && $line->group_key) {
                $this->convertGroupToExpense($import, $line->group_key, $userId);
            }

            $this->refreshImportConfirmedTotal($import);
        });
    }

    protected function applyReviewToLine(
        ExpenseStatementLine $line,
        string $reviewStatus,
        ?int $categoryId,
        ?string $description,
    ): void {
        $line->update([
            'review_status' => $reviewStatus,
            'expense_category_id' => $reviewStatus === ExpenseStatementLine::REVIEW_CONFIRMED ? $categoryId : null,
            'expense_description' => $description,
        ]);
    }

    /**
     * Turn the confirmed business transactions of a group into actual, already-paid
     * expense records and post them to the general ledger. Lines already linked to an
     * expense are skipped, so this is safe to call repeatedly.
     */
    protected function convertGroupToExpense(ExpenseStatementImport $import, string $groupKey, int $userId): void
    {
        $lines = $import->lines()
            ->where('group_key', $groupKey)
            ->where('direction', 'out')
            ->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED)
            ->whereNull('expense_id')
            ->whereNotNull('expense_category_id')
            ->orderBy('completed_at')
            ->get();

        if ($lines->isEmpty()) {
            return;
        }

        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $creditAccount = Account::where('code', '1002')->first()
            ?? Account::where('code', '1000')->first();

        foreach ($lines->groupBy('expense_category_id') as $categoryLines) {
            try {
                $this->createPaidExpenseFromLines($import, $categoryLines, $user, $creditAccount);
            } catch (\Throwable $e) {
                Log::warning('Failed to auto-convert M-Pesa transactions to an expense', [
                    'import_id' => $import->id,
                    'group_key' => $groupKey,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ExpenseStatementLine>  $lines
     */
    protected function createPaidExpenseFromLines(
        ExpenseStatementImport $import,
        $lines,
        User $user,
        ?Account $creditAccount,
    ): void {
        $first = $lines->first();
        $recipientLabel = $first->recipient_name
            ?: ($first->paybill_number ? 'Paybill ' . $first->paybill_number : null)
            ?: mb_substr($first->narration, 0, 80);

        $paidAt = $lines->sortByDesc('completed_at')->first()?->completed_at ?? now();
        $expenseDate = $lines->sortBy('completed_at')->first()?->completed_at?->toDateString()
            ?? now()->toDateString();

        $expense = Expense::create([
            'source_type' => 'mpesa_statement',
            'requested_by' => $user->id,
            'expense_date' => $expenseDate,
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

        $voucher = PaymentVoucher::create([
            'expense_id' => $expense->id,
            'payee' => $recipientLabel,
            'payment_method' => 'M-Pesa',
            'payment_date' => $paidAt,
            'amount' => $expense->total,
            'status' => 'approved',
            'prepared_by' => $user->id,
            'approved_by' => $user->id,
        ]);

        $this->expenseWorkflow->payVoucher($voucher, $user, [
            'amount' => $expense->total,
            'paid_at' => $paidAt,
            'account_id' => $creditAccount?->id,
            'account_source' => 'M-Pesa',
            'reference_no' => $first->receipt_no,
        ]);
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
                $recipientLabel = $first->recipient_name
                    ?: ($first->paybill_number ? 'Paybill ' . $first->paybill_number : null)
                    ?: mb_substr($first->narration, 0, 80);

                $expense = Expense::create([
                    'source_type' => 'mpesa_statement',
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
