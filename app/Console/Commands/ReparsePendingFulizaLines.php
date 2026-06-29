<?php

namespace App\Console\Commands;

use App\Models\ExpenseStatementLine;
use App\Services\Finance\ExpenseStatementImportService;
use App\Services\Finance\MpesaTransactionClassifier;
use Illuminate\Console\Command;

/**
 * Re-parses PENDING, not-yet-booked Fuliza statement lines so they group by their
 * real payee. The original parser left Fuliza-funded "Customer Transfer", "Merchant
 * Payment ... Online" and similar narrations as TYPE_OTHER (the whole narration was
 * stored as the recipient), so each one became its own single-row group. With the
 * improved {@see MpesaTransactionClassifier} these now resolve to a clean
 * send-money / buy-goods / paybill payee that regroups correctly.
 *
 * SAFETY — this command is deliberately incapable of doing damage:
 *   - It only ever selects lines with review_status = pending AND expense_id IS NULL,
 *     so it can NEVER touch a confirmed/submitted/approved line or reverse an expense
 *     or post a journal entry. It purely relabels + regroups unbooked pending rows.
 *   - It only rewrites a line when the new parse yields a STRUCTURED payee (not OTHER)
 *     and the grouping actually changes; otherwise the line is left untouched.
 *
 * Dry-run by default. Pass --apply to commit. --import= scopes to one statement.
 */
class ReparsePendingFulizaLines extends Command
{
    protected $signature = 'finance:reparse-pending-fuliza
        {--apply : Persist the changes (without this flag the command only reports)}
        {--import= : Limit to a single statement import id}';

    protected $description = 'Re-group pending, unbooked Fuliza lines by their real payee using the improved parser.';

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
            ? 'APPLYING re-parse (changes WILL be saved).'
            : 'DRY RUN (no changes saved — pass --apply to commit).');

        $query = ExpenseStatementLine::query()
            ->where('direction', 'out')
            ->where('is_transaction_fee', false)
            ->where('review_status', ExpenseStatementLine::REVIEW_PENDING)
            ->whereNull('expense_id') // never touch a booked line
            ->where('narration', 'like', '%Fuliza%')
            ->when($importFilter, fn ($q) => $q->where('import_id', (int) $importFilter))
            ->orderBy('import_id')
            ->orderBy('id');

        $scanned = 0;
        $changed = 0;
        $internal = 0;
        $rows = [];

        $query->chunkById(500, function ($lines) use (&$scanned, &$changed, &$internal, &$rows, $apply) {
            foreach ($lines as $line) {
                $scanned++;

                $new = $this->classifier->classify(
                    (string) $line->narration,
                    (float) $line->withdrawn_amount,
                    (float) $line->paid_in_amount,
                );

                // Only act when re-parsing produces a structured payee (not OTHER) and
                // actually regroups the line — never blindly rewrite.
                if ($new['transaction_type'] === ExpenseStatementLine::TYPE_OTHER) {
                    continue;
                }

                $hasPayee = trim((string) $new['recipient_name']) !== ''
                    || trim((string) $new['paybill_number']) !== '';
                if (! $hasPayee || $new['group_key'] === $line->group_key) {
                    continue;
                }

                $isInternal = $this->importService->isInternalOwnAccountTransfer($new);

                if (count($rows) < 25) {
                    $rows[] = [
                        $line->id,
                        number_format((float) $line->withdrawn_amount, 2),
                        $new['paybill_number'] ?: '-',
                        mb_strimwidth((string) ($new['recipient_name'] ?: '-'), 0, 30, '…'),
                        $isInternal ? 'ignored (internal)' : 'pending',
                    ];
                }

                if ($apply) {
                    $line->transaction_type = $new['transaction_type'];
                    $line->recipient_name = $new['recipient_name'];
                    $line->recipient_phone = $new['recipient_phone'];
                    $line->paybill_number = $new['paybill_number'];
                    $line->account_reference = $new['account_reference'];
                    $line->merchant_reference = $new['merchant_reference'];
                    $line->group_key = $new['group_key'];

                    if ($isInternal) {
                        $line->review_status = ExpenseStatementLine::REVIEW_IGNORED;
                        $raw = $line->raw_data ?? [];
                        $raw['internal_transfer'] = true;
                        $line->raw_data = $raw;
                    }

                    $line->save();
                }

                $changed++;
                if ($isInternal) {
                    $internal++;
                }
            }
        });

        if ($rows !== []) {
            $this->table(['line', 'amount', 'paybill', 'recipient', 'status'], $rows);
        }

        $this->line('');
        $this->info(sprintf(
            'Scanned %d pending Fuliza line(s): %d will be re-grouped (%d internal transfers).',
            $scanned,
            $changed,
            $internal
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to commit.');
        }

        return self::SUCCESS;
    }
}
