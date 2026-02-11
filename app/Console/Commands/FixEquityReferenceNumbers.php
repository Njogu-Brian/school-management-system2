<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use App\Services\BankStatementParser;
use Illuminate\Console\Command;

class FixEquityReferenceNumbers extends Command
{
    protected $signature = 'finance:fix-equity-reference-numbers
                            {--statement= : Only fix transactions from this statement file path}
                            {--dry-run : Show what would be updated without changing data}
                            {--limit= : Max number of transactions to process (default: all)}';

    protected $description = 'Fix MPS-type Equity refs only: re-extract reference from description (MPS <phone> <code> -> use code). For APP/other types use finance:fix-equity-refs-from-reparse --statement=<path>.';

    public function handle(): int
    {
        $statementPath = $this->option('statement');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $query = BankStatementTransaction::where('bank_type', 'equity')
            ->where('transaction_type', 'credit')
            ->where(function ($q) {
                $q->whereNotNull('description')->where('description', '!=', '');
            });

        if ($statementPath !== null && $statementPath !== '') {
            $query->where('statement_file_path', $statementPath);
            $this->line('Filtering by statement: ' . $statementPath);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $transactions = $query->get();
        $updated = 0;
        $skipped = 0;

        foreach ($transactions as $transaction) {
            $extracted = BankStatementParser::extractReferenceFromEquityDescription($transaction->description);
            if ($extracted === null) {
                $skipped++;
                continue;
            }
            $current = $transaction->reference_number ?? '';
            if (trim((string) $current) === $extracted) {
                $skipped++;
                continue;
            }
            $this->line(sprintf(
                'Transaction #%d | Amount %s | Date %s | Current ref: %s -> New ref: %s',
                $transaction->id,
                $transaction->amount,
                $transaction->transaction_date?->format('Y-m-d') ?? '',
                $current === '' ? '(empty)' : $current,
                $extracted
            ));
            if (!$dryRun) {
                $transaction->update(['reference_number' => $extracted]);
                $updated++;
            } else {
                $this->warn('  [Dry run] Would set reference_number to: ' . $extracted);
                $updated++;
            }
        }

        $this->line('');
        if ($updated > 0) {
            $this->info(($dryRun ? 'Would update ' : 'Updated ') . $updated . ' transaction(s).' . ($skipped > 0 ? " Skipped {$skipped} (no change or no MPS pattern)." : ''));
        } else {
            $this->info('No transactions needed updating.' . ($skipped > 0 ? " {$skipped} had no MPS pattern or already correct." : ''));
        }
        if ($skipped > 0) {
            $this->line('For APP / other types (ref in statement column): php artisan finance:fix-equity-refs-from-reparse --statement=<path>');
        }
        if ($dryRun && $updated > 0) {
            $this->warn('Run without --dry-run to apply changes.');
        }

        return 0;
    }
}
