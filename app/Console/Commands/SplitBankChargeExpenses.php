<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseStatementLine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Moves bundled bank / M-Pesa transaction-charge lines out of merchant expenses
 * into their own no-vendor "Bank Charges" expense, so charges are no longer
 * attributed to the merchant.
 *
 * The general ledger is NOT re-posted: the payment journal already debits each
 * line's category account, so charge amounts are already sitting in the Bank
 * Charges account. This only restructures the expense records.
 */
class SplitBankChargeExpenses extends Command
{
    protected $signature = 'expenses:split-bank-charges {--dry-run : Show what would change without saving}';

    protected $description = 'Split bundled transaction-charge lines into standalone no-vendor Bank Charges expenses.';

    public function handle(): int
    {
        $chargeId = ExpenseCategory::where('code', 'TXN_COST')->value('id');
        if (! $chargeId) {
            $this->error('Bank charges category (code TXN_COST) not found. Seed the chart of accounts first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $created = 0;
        $linesMoved = 0;
        $amountMoved = 0.0;

        Expense::query()
            ->whereHas('lines', fn ($q) => $q->where('category_id', $chargeId))
            ->whereHas('lines', fn ($q) => $q->where('category_id', '!=', $chargeId))
            ->with('lines')
            ->orderBy('id')
            ->chunkById(200, function ($expenses) use ($chargeId, $dryRun, &$created, &$linesMoved, &$amountMoved) {
                foreach ($expenses as $parent) {
                    $chargeLines = $parent->lines->where('category_id', $chargeId);
                    $otherLines = $parent->lines->where('category_id', '!=', $chargeId);
                    if ($chargeLines->isEmpty() || $otherLines->isEmpty()) {
                        continue;
                    }

                    $sum = (float) $chargeLines->sum(fn ($l) => (float) $l->line_total);

                    if ($dryRun) {
                        $created++;
                        $linesMoved += $chargeLines->count();
                        $amountMoved += $sum;

                        continue;
                    }

                    DB::transaction(function () use ($parent, $chargeLines, &$created, &$linesMoved, &$amountMoved) {
                        $charge = Expense::create([
                            'source_type' => $parent->source_type ?: 'mpesa_statement',
                            'vendor_id' => null,
                            'requested_by' => $parent->requested_by,
                            'expense_date' => $parent->expense_date,
                            'currency' => $parent->currency ?: 'KES',
                            'status' => $parent->status,
                            'submitted_at' => $parent->submitted_at,
                            'approved_at' => $parent->approved_at,
                            'approved_by' => $parent->approved_by,
                            'notes' => 'Bank & M-Pesa transaction charges — split from ' . $parent->expense_no,
                        ]);

                        foreach ($chargeLines as $line) {
                            $line->expense_id = $charge->id;
                            $line->save();
                            $linesMoved++;
                            $amountMoved += (float) $line->line_total;
                        }

                        // Re-link the originating fee statement lines to the new charge expense.
                        ExpenseStatementLine::where('expense_id', $parent->id)
                            ->where('is_transaction_fee', true)
                            ->update(['expense_id' => $charge->id]);

                        $charge->recalculateTotals();
                        $charge->save();

                        $parent->recalculateTotals();
                        $parent->save();

                        $created++;
                    });
                }
            });

        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Done.');
        $this->table(['Metric', 'Value'], [
            ['Charge expenses ' . ($dryRun ? 'to create' : 'created'), $created],
            ['Charge lines ' . ($dryRun ? 'to move' : 'moved'), $linesMoved],
            ['Total charge amount', number_format($amountMoved, 2)],
        ]);

        if ($dryRun) {
            $this->comment('Run again without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
