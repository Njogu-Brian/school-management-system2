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

        // Pass 3: Same description + amount + date even when only one side has a reference
        // (e.g. Draft N/A + Collected 54106936 for Kimwaki).
        // Skip generic charge narrations — many distinct APP payments share the same SMS charge text.
        $descGroups = BankStatementTransaction::query()
            ->select('description', 'amount', DB::raw('DATE(transaction_date) as txn_date'))
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->whereRaw("UPPER(description) NOT LIKE '%CHARGE%'")
            ->whereRaw("UPPER(description) NOT LIKE '%SMS CHARGE%'")
            ->groupBy('description', 'amount', DB::raw('DATE(transaction_date)'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($descGroups as $group) {
            $candidates = BankStatementTransaction::where('description', $group->description)
                ->where('amount', $group->amount)
                ->whereDate('transaction_date', $group->txn_date)
                ->orderByRaw("CASE WHEN reference_number IS NULL OR reference_number = '' OR reference_number = 'N/A' THEN 1 ELSE 0 END")
                ->orderByRaw("CASE WHEN status IN ('confirmed','collected','allocated') THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();
            $marked += $this->markGroup($candidates, $dryRun);
        }

        // Pass 4: Embedded TPG/remittance code in description matches another row's reference
        // (Equity S-serial draft vs M-Pesa/TPG collected for the same Pesalink payment)
        $marked += $this->markEmbeddedRemittanceDuplicates($dryRun);

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

    private function markEmbeddedRemittanceDuplicates(bool $dryRun): int
    {
        $marked = 0;
        $rows = BankStatementTransaction::query()
            ->where('is_duplicate', false)
            ->whereNotNull('description')
            ->where(function ($q) {
                $q->where('description', 'like', '%TPG %')
                    ->orWhere('description', 'like', '%Pesalink%');
            })
            ->orderBy('id')
            ->get(['id', 'amount', 'transaction_date', 'reference_number', 'description', 'payment_id', 'status', 'is_duplicate']);

        foreach ($rows as $row) {
            if ($row->is_duplicate) {
                continue;
            }
            $codes = \App\Services\BankStatementParser::extractEmbeddedRemittanceCodes($row->description);
            if ($codes === []) {
                continue;
            }
            $original = BankStatementTransaction::query()
                ->where('is_duplicate', false)
                ->where('id', '!=', $row->id)
                ->where('amount', $row->amount)
                ->whereDate('transaction_date', $row->transaction_date)
                ->where(function ($q) use ($codes) {
                    $q->whereIn('reference_number', $codes);
                    foreach ($codes as $code) {
                        $q->orWhere('description', 'like', '%' . $code . '%');
                    }
                })
                ->orderByRaw("CASE WHEN status IN ('confirmed','collected','allocated') THEN 0 ELSE 1 END")
                ->orderByRaw("CASE WHEN payment_id IS NULL THEN 1 ELSE 0 END")
                ->orderBy('id')
                ->first();

            if (!$original) {
                continue;
            }

            // Prefer keeping the collected/confirmed row
            $keep = $original;
            $drop = $row;
            if (in_array($row->status, ['confirmed', 'collected', 'allocated'], true)
                && ! in_array($original->status, ['confirmed', 'collected', 'allocated'], true)) {
                $keep = $row;
                $drop = $original;
            }
            if ($drop->is_duplicate) {
                continue;
            }
            if ($dryRun) {
                $this->line("[Would mark] #{$drop->id} (original #{$keep->id}) embedded-remittance {$drop->amount}");
            } else {
                $update = [
                    'is_duplicate' => true,
                    'duplicate_of_payment_id' => $keep->payment_id,
                ];
                if (Schema::hasColumn('bank_statement_transactions', 'duplicate_of_transaction_id')) {
                    $update['duplicate_of_transaction_id'] = $keep->id;
                }
                $drop->update($update);
                $this->line("Marked duplicate: #{$drop->id} (original #{$keep->id}) embedded-remittance {$drop->amount}");
            }
            $marked++;
        }

        return $marked;
    }
}
