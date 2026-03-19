<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarkBankStatementDuplicates extends Command
{
    protected $signature = 'finance:mark-bank-duplicates
                            {--dry-run : Show what would be marked without updating}
                            {--no-ref : Also match duplicates by description+date+amount when reference is empty (e.g. EAZZY-FUNDS with N/A)}';

    protected $description = 'Find bank statement transactions with same reference_number, amount and date and mark duplicates. Use --no-ref for transactions missing reference (e.g. N/A).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $includeNoRef = $this->option('no-ref');
        if ($dryRun) {
            $this->warn('Dry run – no changes will be made.');
        }

        $marked = 0;

        // Pass 1: Groups with same reference_number, amount, date
        $groups = BankStatementTransaction::query()
            ->select('reference_number', 'amount', DB::raw('DATE(transaction_date) as txn_date'))
            ->whereNotNull('reference_number')
            ->where('reference_number', '!=', '')
            ->where('reference_number', '!=', 'N/A')
            ->groupBy('reference_number', 'amount', DB::raw('DATE(transaction_date)'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $marked += $this->markGroup(
                BankStatementTransaction::where('reference_number', $group->reference_number)
                    ->where('amount', $group->amount)
                    ->whereDate('transaction_date', $group->txn_date)
                    ->orderBy('id')
                    ->get(),
                $dryRun
            );
        }

        // Pass 2: Groups with empty reference (description + amount + date)
        if ($includeNoRef) {
            $noRefGroups = BankStatementTransaction::query()
                ->select('description', 'amount', DB::raw('DATE(transaction_date) as txn_date'))
                ->where(function ($q) {
                    $q->whereNull('reference_number')
                        ->orWhere('reference_number', '')
                        ->orWhere('reference_number', 'N/A');
                })
                ->whereNotNull('description')
                ->where('description', '!=', '')
                ->groupBy('description', 'amount', DB::raw('DATE(transaction_date)'))
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($noRefGroups as $group) {
                $marked += $this->markGroup(
                    BankStatementTransaction::where('description', $group->description)
                        ->where('amount', $group->amount)
                        ->whereDate('transaction_date', $group->txn_date)
                        ->where(function ($q) {
                            $q->whereNull('reference_number')
                                ->orWhere('reference_number', '')
                                ->orWhere('reference_number', 'N/A');
                        })
                        ->orderBy('id')
                        ->get(),
                    $dryRun
                );
            }
        }

        if ($marked > 0) {
            $this->info(($dryRun ? 'Would mark ' : 'Marked ') . $marked . ' transaction(s) as duplicate.');
        } else {
            $this->info('No new duplicates to mark.' . ($includeNoRef ? '' : ' Try --no-ref for transactions with missing reference (N/A).'));
        }

        return 0;
    }

    private function markGroup($candidates, bool $dryRun): int
    {
        $marked = 0;
        $keepFirst = $candidates->first();
        foreach ($candidates as $txn) {
            if ($txn->id === $keepFirst->id) {
                continue;
            }
            if ($txn->is_duplicate) {
                continue;
            }
            $refLabel = $txn->reference_number ?: '(no ref)';
            if (!$dryRun) {
                $update = [
                    'is_duplicate' => true,
                    'duplicate_of_payment_id' => $keepFirst->payment_id,
                ];
                if (Schema::hasColumn('bank_statement_transactions', 'duplicate_of_transaction_id')) {
                    $update['duplicate_of_transaction_id'] = $keepFirst->id;
                }
                $txn->update($update);
                $this->line("Marked duplicate: #{$txn->id} (original #{$keepFirst->id}) {$refLabel} {$txn->amount}");
            } else {
                $this->line("[Would mark] #{$txn->id} (original #{$keepFirst->id}) {$refLabel} {$txn->amount}");
            }
            $marked++;
        }
        return $marked;
    }
}
