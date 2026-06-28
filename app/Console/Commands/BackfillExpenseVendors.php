<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ExpenseStatementLine;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillExpenseVendors extends Command
{
    protected $signature = 'expenses:backfill-vendors
        {--dry-run : Show what would change without saving}
        {--force : Overwrite vendors even when an expense already has one}';

    protected $description = 'Set each expense vendor to the payee from its source M-Pesa transaction (and backfill line descriptions).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        // Primary (non-charge) statement lines that produced an expense, keyed by expense.
        $linesByExpense = ExpenseStatementLine::query()
            ->whereNotNull('expense_id')
            ->where('is_transaction_fee', false)
            ->orderBy('completed_at')
            ->get()
            ->groupBy('expense_id');

        if ($linesByExpense->isEmpty()) {
            $this->warn('No statement-linked expenses found. Nothing to backfill.');

            return self::SUCCESS;
        }

        $vendorsSet = 0;
        $descriptionsSet = 0;
        $skipped = 0;
        $missingPayee = 0;

        foreach ($linesByExpense as $expenseId => $lines) {
            /** @var ExpenseStatementLine $primary */
            $primary = $lines->first();
            $expense = Expense::find($expenseId);
            if (! $expense) {
                continue;
            }

            $payee = $primary->payeeName();

            // --- Vendor ---
            if ($payee === null || trim($payee) === '') {
                $missingPayee++;
            } elseif ($expense->vendor_id && ! $force) {
                $skipped++;
            } else {
                if ($dryRun) {
                    $this->line(sprintf('  [%s] vendor -> "%s"', $expense->expense_no, $payee));
                    $vendorsSet++;
                } else {
                    DB::transaction(function () use ($expense, $payee) {
                        $vendor = Vendor::firstOrCreateByName($payee);
                        if ($vendor) {
                            $expense->vendor_id = $vendor->id;
                            $expense->save();
                        }
                    });
                    $vendorsSet++;
                }
            }

            // --- Description: fill blank primary expense line(s) from the transaction ---
            $desc = $primary->expense_description ?: $primary->narration;
            if ($desc) {
                $blankLines = $expense->lines()
                    ->where(function ($q) {
                        $q->whereNull('description')->orWhere('description', '');
                    })
                    ->get();

                foreach ($blankLines as $line) {
                    if ($dryRun) {
                        $descriptionsSet++;
                    } else {
                        $line->description = $desc;
                        $line->save();
                        $descriptionsSet++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Backfill complete.');
        $this->table(['Metric', 'Count'], [
            ['Vendors set', $vendorsSet],
            ['Descriptions filled', $descriptionsSet],
            ['Skipped (already had vendor)', $skipped],
            ['No payee on transaction', $missingPayee],
        ]);

        if ($dryRun) {
            $this->comment('Run again without --dry-run to apply these changes.');
        }

        return self::SUCCESS;
    }
}
