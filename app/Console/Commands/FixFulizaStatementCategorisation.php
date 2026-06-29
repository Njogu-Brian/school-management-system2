<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ExpenseStatementImport;
use App\Models\ExpenseStatementLine;
use App\Services\Finance\ExpenseStatementImportService;
use App\Services\Finance\MpesaTransactionClassifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repairs M-Pesa statement lines whose recipient/paybill could not be parsed because
 * the narration carried the Fuliza funding phrase ("Pay Bill Online Fuliza M-Pesa to
 * 247247 ..."). The old parser left paybill_number / recipient / account NULL, so all
 * such payments collapsed into ONE review group and were mass-mislabelled (e.g. the
 * whole group booked as "Electricity"), then turned into confirmed/paid expenses.
 *
 * SCOPE (deliberately narrow — this command is destructive):
 *   A line is only "affected" when ALL of these hold:
 *     - it is an outgoing, NON-fee line,
 *     - its narration actually contains the word "Fuliza",
 *     - it currently has NO paybill_number and NO recipient_name (the parser failed), and
 *     - re-classifying it now RECOVERS a paybill/recipient and changes its group_key.
 *   Fee lines (whose group_key is inherited from their parent) and any line that
 *   already parsed a payee are never touched, so it cannot blindly re-group the whole
 *   statement set the way an earlier version did.
 *
 * For every affected line this command:
 *   1. Re-classifies it with the fixed {@see MpesaTransactionClassifier}.
 *   2. Reverses (posted) or rejects (submitted) any expense that was wrongly created
 *      from it — posting a proper contra journal entry where one exists.
 *   3. Rewrites the structured fields (paybill_number, recipient_name,
 *      account_reference, group_key, transaction_type) so the line regroups correctly
 *      (vendor_name overrides set manually are preserved).
 *   4. Marks deposits into the school's own bank accounts as IGNORED (internal
 *      transfers), otherwise leaves the line PENDING/uncategorised for re-review.
 *
 * Genuine KPLC electricity lines parsed fine originally, so their classification does
 * not change and they are left completely untouched.
 *
 * Dry-run by default. Pass --apply to commit.
 */
class FixFulizaStatementCategorisation extends Command
{
    protected $signature = 'finance:fix-fuliza-statements
        {--apply : Persist the changes (without this flag the command only reports)}
        {--import= : Limit to a single statement import id}';

    protected $description = 'Re-classify Fuliza-funded statement lines and reverse the expenses they were wrongly booked into.';

    public function __construct(
        protected MpesaTransactionClassifier $classifier,
        protected ExpenseStatementImportService $importService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $importFilter = $this->option('import');

        $this->info($apply
            ? 'APPLYING fixes (changes WILL be saved).'
            : 'DRY RUN (no changes saved — pass --apply to commit).');

        // ONLY look at outgoing, non-fee lines whose narration actually carries the
        // Fuliza funding phrase. Fee lines inherit their parent's group_key (so they
        // would always "differ") and non-Fuliza lines were never affected by the bug;
        // scoping at the SQL level here is what keeps this command from over-matching.
        $query = ExpenseStatementLine::query()
            ->where('direction', 'out')
            ->where('is_transaction_fee', false)
            ->where('narration', 'like', '%Fuliza%')
            ->when($importFilter, fn ($q) => $q->where('import_id', (int) $importFilter))
            ->orderBy('import_id')
            ->orderBy('id');

        $affected = [];
        $scanned = 0;        // Fuliza lines examined
        $skippedParsed = 0;  // already had a payee → left untouched

        $query->chunkById(500, function ($lines) use (&$affected, &$scanned, &$skippedParsed) {
            foreach ($lines as $line) {
                $scanned++;

                // If the line already has a paybill/recipient, the old parser handled it
                // fine despite the Fuliza phrase — never disturb it.
                $oldHasPayee = trim((string) $line->paybill_number) !== ''
                    || trim((string) $line->recipient_name) !== '';
                if ($oldHasPayee) {
                    $skippedParsed++;
                    continue;
                }

                $new = $this->classifier->classify(
                    (string) $line->narration,
                    (float) $line->withdrawn_amount,
                    (float) $line->paid_in_amount,
                );

                // Only act when the corrected parse genuinely RECOVERS a payee/paybill
                // that was previously missing AND that regroups the line. Anything that
                // doesn't recover a payee is left alone (no blind re-grouping).
                $newHasPayee = trim((string) $new['paybill_number']) !== ''
                    || trim((string) $new['recipient_name']) !== '';
                if (! $newHasPayee || $new['group_key'] === $line->group_key) {
                    continue;
                }

                $affected[] = ['line' => $line, 'new' => $new];
            }
        });

        $this->line(sprintf(
            'Scanned %d Fuliza line(s): %d already parsed (left untouched), %d recoverable.',
            $scanned,
            $skippedParsed,
            count($affected)
        ));

        if (empty($affected)) {
            $this->info('No affected lines found. Nothing to do.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->info(count($affected) . ' statement line(s) will be re-classified.');

        $stats = [
            'reversed_paid' => 0,
            'rejected_submitted' => 0,
            'detached_other' => 0,
            'amount_unbooked' => 0.0,
            'marked_internal_transfer' => 0,
            'reset_pending' => 0,
            'skipped_errors' => 0,
        ];

        // Preview table (first 20 rows).
        $rows = [];
        foreach (array_slice($affected, 0, 20) as $item) {
            $line = $item['line'];
            $new = $item['new'];
            $expense = $line->expense_id ? Expense::find($line->expense_id) : null;
            $rows[] = [
                $line->id,
                number_format((float) $line->withdrawn_amount, 2),
                $new['paybill_number'] ?: '-',
                mb_strimwidth((string) ($new['recipient_name'] ?: '-'), 0, 28, '…'),
                $new['account_reference'] ?: '-',
                $this->importService->isInternalOwnAccountTransfer($new) ? 'internal-transfer' : 're-review',
                $expense ? ($expense->expense_no . ' [' . $expense->status . ']') : '-',
            ];
        }
        $this->table(
            ['line', 'amount', 'paybill', 'recipient', 'account', 'outcome', 'current expense'],
            $rows
        );
        if (count($affected) > 20) {
            $this->line('... and ' . (count($affected) - 20) . ' more.');
        }

        $importCache = [];

        foreach ($affected as $item) {
            /** @var ExpenseStatementLine $line */
            $line = $item['line'];
            $new = $item['new'];
            $isInternal = $this->importService->isInternalOwnAccountTransfer($new);

            try {
                if (! $apply) {
                    $this->tallyDryRun($line, $isInternal, $stats);

                    continue;
                }

                $importId = (int) $line->import_id;
                $import = $importCache[$importId]
                    ??= ExpenseStatementImport::find($importId);

                DB::transaction(function () use ($line, $new, $isInternal, $import, &$stats) {
                    $this->reverseLinkedExpense($line, $import, $stats);

                    // Rewrite structured fields from the corrected classification.
                    $line->refresh();
                    $line->transaction_type = $new['transaction_type'];
                    $line->recipient_name = $new['recipient_name'];
                    $line->recipient_phone = $new['recipient_phone'];
                    $line->paybill_number = $new['paybill_number'];
                    $line->account_reference = $new['account_reference'];
                    $line->merchant_reference = $new['merchant_reference'];
                    $line->group_key = $new['group_key'];
                    $line->expense_id = null;
                    $line->expense_category_id = null;
                    $line->expense_description = null;

                    if ($isInternal) {
                        $line->review_status = ExpenseStatementLine::REVIEW_IGNORED;
                        $raw = $line->raw_data ?? [];
                        $raw['internal_transfer'] = true;
                        $line->raw_data = $raw;
                        $stats['marked_internal_transfer']++;
                    } else {
                        $line->review_status = ExpenseStatementLine::REVIEW_PENDING;
                        $stats['reset_pending']++;
                    }

                    $line->save();
                });
            } catch (\Throwable $e) {
                $stats['skipped_errors']++;
                $this->warn("Line {$line->id}: {$e->getMessage()}");
            }
        }

        $this->line('');
        $this->info('Summary' . ($apply ? '' : ' (dry run)') . ':');
        $this->line('  Paid/approved expenses reversed : ' . $stats['reversed_paid']);
        $this->line('  Submitted expenses rejected     : ' . $stats['rejected_submitted']);
        $this->line('  Other expenses detached/deleted : ' . $stats['detached_other']);
        $this->line('  Total amount un-booked          : ' . number_format($stats['amount_unbooked'], 2));
        $this->line('  Lines marked internal transfer  : ' . $stats['marked_internal_transfer']);
        $this->line('  Lines reset to pending re-review: ' . $stats['reset_pending']);
        if ($stats['skipped_errors']) {
            $this->warn('  Lines skipped due to errors     : ' . $stats['skipped_errors']);
        }

        if (! $apply) {
            $this->line('');
            $this->comment('Re-run with --apply to commit these changes.');
        }

        return self::SUCCESS;
    }

    /**
     * Reverse / reject the expense a line was wrongly booked into, posting a contra
     * journal entry where one exists. Leaves nothing booked for this line.
     */
    protected function reverseLinkedExpense(ExpenseStatementLine $line, ?ExpenseStatementImport $import, array &$stats): void
    {
        if (! $line->expense_id || ! $import) {
            return;
        }

        $expense = Expense::find($line->expense_id);
        if (! $expense) {
            return;
        }

        $amount = (float) $line->withdrawn_amount;
        $userId = (int) ($import->uploaded_by ?: $expense->requested_by);

        if (in_array($expense->status, [Expense::STATUS_APPROVED, Expense::STATUS_PAID], true)) {
            $this->importService->reverseStatementExpenses($import, $userId, $expense->id);
            $stats['reversed_paid']++;
            $stats['amount_unbooked'] += $amount;

            return;
        }

        if ($expense->status === Expense::STATUS_SUBMITTED) {
            $this->importService->rejectStatementExpense($import, $expense->id);
            $stats['rejected_submitted']++;
            $stats['amount_unbooked'] += $amount;

            return;
        }

        // Draft / rejected: just unlink and remove the orphan expense.
        $import->lines()->where('expense_id', $expense->id)->update([
            'expense_id' => null,
            'review_status' => ExpenseStatementLine::REVIEW_PENDING,
            'expense_category_id' => null,
        ]);
        $expense->vouchers()->delete();
        $expense->lines()->delete();
        $expense->delete();
        $stats['detached_other']++;
        $stats['amount_unbooked'] += $amount;
    }

    protected function tallyDryRun(ExpenseStatementLine $line, bool $isInternal, array &$stats): void
    {
        if ($line->expense_id) {
            $expense = Expense::find($line->expense_id);
            if ($expense) {
                $amount = (float) $line->withdrawn_amount;
                $stats['amount_unbooked'] += $amount;
                if (in_array($expense->status, [Expense::STATUS_APPROVED, Expense::STATUS_PAID], true)) {
                    $stats['reversed_paid']++;
                } elseif ($expense->status === Expense::STATUS_SUBMITTED) {
                    $stats['rejected_submitted']++;
                } else {
                    $stats['detached_other']++;
                }
            }
        }

        if ($isInternal) {
            $stats['marked_internal_transfer']++;
        } else {
            $stats['reset_pending']++;
        }
    }
}
