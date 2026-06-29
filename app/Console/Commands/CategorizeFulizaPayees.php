<?php

namespace App\Console\Commands;

use App\Models\ExpenseCategory;
use App\Models\ExpenseStatementLine;
use App\Models\ExpenseStatementRecipientProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-categorises pending, unbooked statement lines for a fixed set of KNOWN
 * recurring payees (confirmed with the user): mobile-loan apps, KPLC electricity and
 * church giving. It marks them as business expenses with the right category and
 * remembers the payee for future imports.
 *
 * SAFETY:
 *   - Only touches lines with review_status = pending AND expense_id IS NULL, so it
 *     can never reverse or alter an already-booked expense, and posts NO journals.
 *   - Does NOT approve anything — lines become "business + category" and wait for the
 *     user to Submit + Approve in the UI, so final sign-off stays with the user.
 *   - Maps by category NAME and aborts if any mapped category is missing.
 *
 * Dry-run by default. Pass --apply to commit. --import= scopes to one statement.
 */
class CategorizeFulizaPayees extends Command
{
    protected $signature = 'finance:categorize-fuliza-payees
        {--apply : Persist the changes (without this flag the command only reports)}
        {--import= : Limit to a single statement import id}';

    protected $description = 'Categorise pending, unbooked lines for known recurring payees (mobile loans, electricity, giving).';

    /**
     * paybill number => [category name, clean vendor label].
     *
     * @var array<string, array{0:string,1:string}>
     */
    protected array $map = [
        '7787614' => ['Mobile Loan', 'Signalwave Ltd'],
        '589036'  => ['Mobile Loan', 'Tingg / Cellulant'],
        '597686'  => ['Mobile Loan', 'Tingg / Cellulant'],
        '4135035' => ['Mobile Loan', 'HFM Investments Ltd'],
        '998608'  => ['Mobile Loan', 'Branch Microfinance'],
        '851900'  => ['Mobile Loan', 'Tala'],
        '979988'  => ['Mobile Loan', 'Zenka Digital'],
        '4133807' => ['Mobile Loan', 'Zenka Digital'],
        '888880'  => ['Electricity', 'KPLC Prepaid'],
        '888888'  => ['Electricity', 'KPLC'],
        '4047991' => ['Donations', 'Deliverance Church Lower Kabete'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $importFilter = $this->option('import');

        $this->info($apply
            ? 'APPLYING categorisation (changes WILL be saved).'
            : 'DRY RUN (no changes saved — pass --apply to commit).');

        // Resolve category names -> ids; abort if any are missing so nothing lands in
        // the wrong bucket.
        $names = collect($this->map)->map(fn ($m) => $m[0])->unique()->values();
        $cats = ExpenseCategory::whereIn('name', $names)->get()->keyBy('name');
        $missing = $names->reject(fn ($n) => $cats->has($n));
        if ($missing->isNotEmpty()) {
            $this->error('Missing categor(ies): ' . $missing->implode(', ') . '. Aborting — nothing changed.');

            return self::FAILURE;
        }

        $chargeId = ExpenseCategory::where('code', 'TXN_COST')->value('id');
        $userId = User::query()->orderBy('id')->value('id');

        $rows = [];
        $totalLines = 0;
        $totalFees = 0;

        foreach ($this->map as $paybill => [$catName, $vendor]) {
            $catId = $cats[$catName]->id;

            $primaries = ExpenseStatementLine::query()
                ->where('direction', 'out')
                ->where('is_transaction_fee', false)
                ->where('review_status', ExpenseStatementLine::REVIEW_PENDING)
                ->whereNull('expense_id')
                ->where('paybill_number', $paybill)
                ->when($importFilter, fn ($q) => $q->where('import_id', (int) $importFilter))
                ->get();

            if ($primaries->isEmpty()) {
                continue;
            }

            $rows[] = [
                $vendor,
                $paybill,
                $catName,
                $primaries->count(),
                number_format((float) $primaries->sum('withdrawn_amount'), 2),
            ];
            $totalLines += $primaries->count();

            if (! $apply) {
                continue;
            }

            DB::transaction(function () use ($primaries, $catId, $chargeId, $vendor, $userId, &$totalFees) {
                ExpenseStatementLine::whereIn('id', $primaries->pluck('id'))->update([
                    'review_status' => ExpenseStatementLine::REVIEW_CONFIRMED,
                    'expense_category_id' => $catId,
                    'vendor_name' => $vendor,
                ]);

                // Confirm the matching M-Pesa charge lines and book them to charges.
                $receipts = $primaries->pluck('receipt_no')->filter()->unique();
                if ($chargeId && $receipts->isNotEmpty()) {
                    $totalFees += ExpenseStatementLine::query()
                        ->where('is_transaction_fee', true)
                        ->whereIn('receipt_no', $receipts->all())
                        ->where('review_status', ExpenseStatementLine::REVIEW_PENDING)
                        ->whereNull('expense_id')
                        ->update([
                            'review_status' => ExpenseStatementLine::REVIEW_CONFIRMED,
                            'expense_category_id' => $chargeId,
                        ]);
                }

                // Remember this payee so future imports auto-classify it.
                foreach ($primaries->pluck('group_key')->unique() as $groupKey) {
                    $first = $primaries->firstWhere('group_key', $groupKey);
                    ExpenseStatementRecipientProfile::updateOrCreate(
                        ['group_key' => $groupKey],
                        [
                            'display_name' => $vendor,
                            'default_vendor_name' => $vendor,
                            'transaction_type' => $first->transaction_type,
                            'is_business_expense' => true,
                            'expense_category_id' => $catId,
                            'updated_by' => $userId,
                        ]
                    );
                }
            });
        }

        if ($rows !== []) {
            $this->table(['vendor', 'paybill', 'category', 'lines', 'amount'], $rows);
        }

        $this->line('');
        $this->info(sprintf(
            '%d primary line(s)%s across %d payee(s) %s.',
            $totalLines,
            $apply ? (' + ' . $totalFees . ' charge line(s)') : '',
            count($rows),
            $apply ? 'categorised (business, awaiting your approval)' : 'would be categorised'
        ));

        if (! $apply) {
            $this->comment('Re-run with --apply to commit. Nothing is approved/posted — you Submit + Approve in the UI.');
        }

        return self::SUCCESS;
    }
}
