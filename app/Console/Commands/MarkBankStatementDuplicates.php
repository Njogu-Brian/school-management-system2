<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarkBankStatementDuplicates extends Command
{
    protected $signature = 'finance:mark-bank-duplicates
                            {--dry-run : Show what would be marked without updating}';

    protected $description = 'Find bank statement transactions with same reference_number, amount and date and mark duplicates (so they only appear in Duplicates tab)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Dry run – no changes will be made.');
        }

        $marked = 0;
        $groups = BankStatementTransaction::query()
            ->select('reference_number', 'amount', DB::raw('DATE(transaction_date) as txn_date'))
            ->whereNotNull('reference_number')
            ->where('reference_number', '!=', '')
            ->groupBy('reference_number', 'amount', DB::raw('DATE(transaction_date)'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $candidates = BankStatementTransaction::where('reference_number', $group->reference_number)
                ->where('amount', $group->amount)
                ->whereDate('transaction_date', $group->txn_date)
                ->orderBy('id')
                ->get();

            $keepFirst = $candidates->first();
            foreach ($candidates as $txn) {
                if ($txn->id === $keepFirst->id) {
                    continue; // keep first as non-duplicate
                }
                if ($txn->is_duplicate) {
                    continue; // already marked
                }
                if (!$dryRun) {
                    $update = [
                        'is_duplicate' => true,
                        'duplicate_of_payment_id' => $keepFirst->payment_id,
                    ];
                    if (Schema::hasColumn('bank_statement_transactions', 'duplicate_of_transaction_id')) {
                        $update['duplicate_of_transaction_id'] = $keepFirst->id;
                    }
                    $txn->update($update);
                    $this->line("Marked duplicate: #{$txn->id} (original #{$keepFirst->id}) {$txn->reference_number} {$txn->amount}");
                } else {
                    $this->line("[Would mark] #{$txn->id} (original #{$keepFirst->id}) {$txn->reference_number} {$txn->amount}");
                }
                $marked++;
            }
        }

        if ($marked > 0) {
            $this->info(($dryRun ? 'Would mark ' : 'Marked ') . $marked . ' transaction(s) as duplicate.');
        } else {
            $this->info('No new duplicates to mark.');
        }

        return 0;
    }
}
