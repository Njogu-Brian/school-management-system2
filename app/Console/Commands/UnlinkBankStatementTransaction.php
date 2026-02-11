<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use Illuminate\Console\Command;

class UnlinkBankStatementTransaction extends Command
{
    protected $signature = 'finance:unlink-bank-transaction
                            {ids* : Bank statement transaction ID(s) to unlink (e.g. 580 586)}
                            {--reference= : Set correct reference_number for a single transaction (e.g. Equity MPS code UA6R831FVY)}
                            {--dry-run : Show what would be done without updating}';

    protected $description = 'Unlink bank statement transaction(s) from wrong payment(s) and optionally set correct reference_number. Use when transactions were incorrectly linked (e.g. same receipt linked to two transactions). Do NOT delete and re-upload the statement—that can delete real payments.';

    public function handle(): int
    {
        $ids = array_map('intval', array_filter($this->argument('ids')));
        $reference = $this->option('reference');
        $dryRun = $this->option('dry-run');

        if (empty($ids)) {
            $this->error('Provide at least one transaction ID, e.g. php artisan finance:unlink-bank-transaction 580 586');
            return 1;
        }

        if (count($ids) > 1 && $reference !== null && $reference !== '') {
            $this->warn('--reference is ignored when unlinking multiple IDs. Run the command once per transaction to set a reference.');
        }

        $fixed = 0;
        foreach ($ids as $id) {
            $transaction = BankStatementTransaction::find($id);
            if (!$transaction) {
                $this->error("Bank statement transaction #{$id} not found.");
                continue;
            }

            $this->line('Transaction #' . $transaction->id . ' | Amount: ' . $transaction->amount . ' | Date: ' . ($transaction->transaction_date?->format('Y-m-d') ?? ''));
            $this->line('  Current reference_number: ' . ($transaction->reference_number ?? 'null'));
            $this->line('  payment_id: ' . ($transaction->payment_id ?? 'null') . ' | linked_payment_ids: ' . json_encode($transaction->linked_payment_ids ?? []));

            $updates = [
                'payment_id' => null,
                'payment_created' => false,
            ];
            if (\Schema::hasColumn('bank_statement_transactions', 'linked_payment_ids')) {
                $updates['linked_payment_ids'] = null;
            }
            $useReference = (count($ids) === 1 && $reference !== null && $reference !== '');
            if ($useReference) {
                $updates['reference_number'] = trim($reference);
            }

            if ($dryRun) {
                $this->warn('  [Dry run] Would set: ' . json_encode($updates));
                $fixed++;
                continue;
            }

            $transaction->update($updates);
            $this->info('  Unlinked.' . ($useReference ? " reference_number set to: {$reference}" : ''));
            $fixed++;
        }

        if ($fixed > 0 && !$dryRun) {
            $this->info('Done. ' . $fixed . ' transaction(s) unlinked.');
        } elseif ($dryRun) {
            $this->warn('Dry run – no changes were made.');
        }

        return 0;
    }
}
