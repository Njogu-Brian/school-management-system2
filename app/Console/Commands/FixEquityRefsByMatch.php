<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use Illuminate\Console\Command;

class FixEquityRefsByMatch extends Command
{
    protected $signature = 'finance:fix-equity-refs-by-match
                            {--description= : Description to match (e.g. "APP/KENNETH MUIGAI MWIHIA/")}
                            {--date= : Transaction date YYYY-MM-DD (e.g. 2026-03-06)}
                            {--amount= : Amount to match (e.g. 10000)}
                            {--reference= : Correct reference to set (e.g. 54118185)}
                            {--dry-run : Show what would be updated without changing data}
                            {--list-empty : List Equity transactions with empty/N/A reference (to identify what to fix)}
                            {--all-banks : Include all bank types (not just Equity) when listing or fixing}';

    protected $description = 'Fix reference_number by matching description+date+amount. Use when PDF is missing and reparse cannot run. Run --list-empty first to see transactions needing fixes.';

    public function handle(): int
    {
        $allBanks = (bool) $this->option('all-banks');
        $listEmpty = $this->option('list-empty');
        if ($listEmpty) {
            return $this->listEmptyRefs($allBanks);
        }

        $description = trim((string) $this->option('description'));
        $date = trim((string) $this->option('date'));
        $amount = $this->option('amount') !== null ? (float) $this->option('amount') : null;
        $reference = trim((string) $this->option('reference'));
        $dryRun = $this->option('dry-run');

        if ($description === '' || $date === '' || $amount === null || $reference === '') {
            $this->error('Provide --description, --date, --amount, and --reference.');
            $this->line('Example: php artisan finance:fix-equity-refs-by-match --description="APP/KENNETH MUIGAI MWIHIA/" --date=2026-03-06 --amount=10000 --reference=54118185');
            $this->line('');
            $this->line('Run with --list-empty to see transactions with empty reference.');
            return 1;
        }

        $likePattern = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $description) . '%';
        $query = BankStatementTransaction::where('transaction_type', 'credit');
        if (!$allBanks) {
            $query->where('bank_type', 'equity');
        }
        $query->where('description', 'LIKE', $likePattern)
            ->whereDate('transaction_date', $date)
            ->whereRaw('ABS(amount - ?) < 0.01', [$amount]);

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            $this->warn("No transactions found matching description, date {$date}, amount {$amount}.");
            $this->line('Try --list-empty to see available transactions.');
            return 1;
        }

        $this->line('Found ' . $transactions->count() . ' transaction(s) to update:');
        foreach ($transactions as $txn) {
            $current = $txn->reference_number ?? '';
            $this->line(sprintf(
                '  #%d | %s | %s | Ref: %s -> %s',
                $txn->id,
                $txn->transaction_date?->format('Y-m-d'),
                number_format((float) $txn->amount, 2),
                $current === '' ? '(empty)' : $current,
                $reference
            ));
        }

        if ($dryRun) {
            $this->warn('[Dry run] Would update ' . $transactions->count() . ' transaction(s). Run without --dry-run to apply.');
            return 0;
        }

        $updated = $query->update(['reference_number' => $reference]);
        $this->info("Updated {$updated} transaction(s) with reference: {$reference}");

        return 0;
    }

    private function listEmptyRefs(bool $allBanks = false): int
    {
        $query = BankStatementTransaction::where('transaction_type', 'credit');
        if (!$allBanks) {
            $query->where('bank_type', 'equity');
        }
        $query->where(function ($q) {
                $q->whereNull('reference_number')
                    ->orWhere('reference_number', '')
                    ->orWhere('reference_number', 'N/A');
            })
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id');

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            $this->info('No transactions with empty/N/A reference found.');
            return 0;
        }

        $this->line(($allBanks ? 'All bank' : 'Equity') . ' transactions with empty or N/A reference:');
        $this->line('');
        foreach ($transactions as $txn) {
            $desc = $txn->description ?? '';
            if (strlen($desc) > 50) {
                $desc = substr($desc, 0, 47) . '...';
            }
            $this->line(sprintf(
                '  #%-5d | %s | %12s | %-8s | %s',
                $txn->id,
                $txn->transaction_date?->format('Y-m-d') ?? '',
                number_format((float) $txn->amount, 2),
                $txn->bank_type ?? '?',
                $desc
            ));
        }
        $this->line('');
        $this->info(count($transactions) . ' transaction(s). Fix with:');
        $this->line('  php artisan finance:fix-equity-refs-by-match --description="<desc>" --date=YYYY-MM-DD --amount=<amt> --reference=<ref>');
        $this->line('  Or: php artisan finance:set-bank-transaction-reference <id> <reference>');

        return 0;
    }
}
