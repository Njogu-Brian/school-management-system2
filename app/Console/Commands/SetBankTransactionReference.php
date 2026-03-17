<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use Illuminate\Console\Command;

class SetBankTransactionReference extends Command
{
    protected $signature = 'finance:set-bank-transaction-reference
                            {id : Bank statement transaction ID}
                            {reference : Correct reference number (e.g. 54118185)}
                            {--dry-run : Show what would be updated without changing data}';

    protected $description = 'Set reference_number for a bank statement transaction. Use when Equity APP transactions show N/A but the correct ref is in the statement (Transaction Reference column). Fixing refs helps avoid duplicates.';

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $reference = trim((string) $this->argument('reference'));
        $dryRun = $this->option('dry-run');

        if ($reference === '') {
            $this->error('Reference cannot be empty.');
            return 1;
        }

        $transaction = BankStatementTransaction::find($id);
        if (!$transaction) {
            $this->error("Bank statement transaction #{$id} not found.");
            return 1;
        }

        $current = $transaction->reference_number ?? '';
        if ($current === $reference) {
            $this->info("Transaction #{$id} already has reference: {$reference}");
            return 0;
        }

        $this->line(sprintf(
            'Transaction #%d | Amount %s | Date %s | Description: %s',
            $transaction->id,
            $transaction->amount,
            $transaction->transaction_date?->format('Y-m-d') ?? '',
            substr($transaction->description ?? '', 0, 50) . (strlen($transaction->description ?? '') > 50 ? '...' : '')
        ));
        $this->line('  Current reference: ' . ($current === '' ? '(empty)' : $current));
        $this->line('  New reference: ' . $reference);

        if ($dryRun) {
            $this->warn('[Dry run] Would set reference_number to: ' . $reference);
            return 0;
        }

        $transaction->update(['reference_number' => $reference]);
        $this->info('Reference updated successfully.');

        return 0;
    }
}
