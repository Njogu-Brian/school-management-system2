<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use App\Models\Payment;
use Illuminate\Console\Command;

class FixBankStatementLinkages extends Command
{
    protected $signature = 'finance:fix-bank-statement-linkages
                            {--dry-run : Show what would be fixed without updating}
                            {--transaction= : Only fix a specific transaction ID}
                            {--all : Fix all bank types (default: Equity only)}';

    protected $description = 'Fix wrong or inconsistent payment linkages on Equity bank statement transactions (sync payment_id with linked_payment_ids, remove reversed/deleted payments). Use --all to include M-Pesa/C2B.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $transactionId = $this->option('transaction');
        $allTypes = $this->option('all');

        if ($dryRun) {
            $this->warn('Dry run – no changes will be made.');
        }

        if (!\Schema::hasColumn('bank_statement_transactions', 'linked_payment_ids')) {
            $this->error('Column linked_payment_ids does not exist on bank_statement_transactions.');
            return 1;
        }

        $query = BankStatementTransaction::query()
            ->where(function ($q) {
                $q->whereNotNull('payment_id')
                    ->orWhereNotNull('linked_payment_ids');
            });

        // Default: Equity only (link-to-existing-payments is for Equity; M-Pesa C2B uses different flow)
        if (!$allTypes && \Schema::hasColumn('bank_statement_transactions', 'bank_type')) {
            $query->where('bank_type', 'equity');
            $this->line('Scope: <info>Equity</info> bank transactions only (use --all to include all bank types).');
        }

        if ($transactionId !== null) {
            $query->where('id', (int) $transactionId);
        }

        $transactions = $query->get();
        $fixed = 0;

        foreach ($transactions as $transaction) {
            $changes = $this->fixTransactionLinkage($transaction, $dryRun);
            if ($changes) {
                $fixed++;
            }
        }

        if ($fixed > 0) {
            $this->info(($dryRun ? 'Would fix ' : 'Fixed ') . $fixed . ' transaction(s).');
        } else {
            $this->info('No linkage issues found.');
        }

        return 0;
    }

    /**
     * Fix one transaction's payment_id and linked_payment_ids.
     * Returns true if any change was (or would be) made.
     */
    private function fixTransactionLinkage(BankStatementTransaction $transaction, bool $dryRun): bool
    {
        $paymentId = $transaction->payment_id ? (int) $transaction->payment_id : null;
        $linkedIds = $transaction->linked_payment_ids;
        if (!is_array($linkedIds)) {
            $linkedIds = $linkedIds ? (json_decode($linkedIds, true) ?? []) : [];
        }
        $linkedIds = array_values(array_filter(array_map('intval', $linkedIds)));

        // 1) Resolve valid payment IDs (exist, not reversed, not soft-deleted)
        $validIds = [];
        if (!empty($linkedIds)) {
            $payments = Payment::withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class)
                ->whereIn('id', $linkedIds)
                ->get();
            foreach ($payments as $p) {
                if ($p->reversed || $p->trashed()) {
                    continue;
                }
                $validIds[] = (int) $p->id;
            }
        }

        // If payment_id is set but not in valid list, check it separately (might be only link)
        if ($paymentId && !in_array($paymentId, $validIds, true)) {
            $primary = Payment::withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class)
                ->find($paymentId);
            if ($primary && !$primary->reversed && !$primary->trashed()) {
                $validIds = array_values(array_unique(array_merge([$paymentId], $validIds)));
            } else {
                $paymentId = null; // primary is invalid, will set to first valid below
            }
        }

        // 2) Ensure payment_id is first in linked list and linked list is consistent
        $newLinkedIds = $validIds;
        $newPaymentId = $paymentId;
        $clearPaymentCreated = false;

        if (empty($newLinkedIds)) {
            $newPaymentId = null;
            $clearPaymentCreated = true;
        } else {
            if ($newPaymentId && !in_array($newPaymentId, $newLinkedIds, true)) {
                $newLinkedIds = array_values(array_unique(array_merge([$newPaymentId], $newLinkedIds)));
            }
            if (!$newPaymentId || !in_array($newPaymentId, $newLinkedIds, true)) {
                $newPaymentId = $newLinkedIds[0];
            }
            // Keep primary first
            $newLinkedIds = array_values(array_unique(array_merge([$newPaymentId], array_diff($newLinkedIds, [$newPaymentId]))));
        }

        // 3) Detect if we need to update
        $linkedChanged = json_encode(array_values($linkedIds)) !== json_encode(array_values($newLinkedIds));
        $paymentIdChanged = $paymentId !== $newPaymentId;
        $needClearCreated = !empty($linkedIds) && empty($newLinkedIds);

        if (!$linkedChanged && !$paymentIdChanged && !$needClearCreated) {
            return false;
        }

        $this->line(sprintf(
            '%s Transaction #%d: payment_id %s -> %s, linked_payment_ids %s -> %s',
            $dryRun ? '[Would fix]' : '[Fixed]',
            $transaction->id,
            $paymentId ?? 'null',
            $newPaymentId ?? 'null',
            json_encode($linkedIds),
            json_encode($newLinkedIds)
        ));

        if (!$dryRun) {
            $data = [
                'payment_id' => $newPaymentId,
                'linked_payment_ids' => $newLinkedIds,
            ];
            if ($clearPaymentCreated && \Schema::hasColumn('bank_statement_transactions', 'payment_created')) {
                $data['payment_created'] = false;
            }
            $transaction->update($data);
        }

        return true;
    }
}
