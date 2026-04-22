<?php

namespace App\Console\Commands;

use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Find transactions in "auto-assigned" that already have payments (e.g. from payment link/STK)
 * and move them to collected status. This fixes cases where the STK webhook created the payment
 * but the C2B callback or bank statement transaction was never linked.
 *
 * Run once to clean up; the C2B callback fix ensures this won't recur.
 */
class MoveAutoAssignedToCollected extends Command
{
    protected $signature = 'finance:move-auto-assigned-to-collected
                            {--dry-run : Show what would be done without making changes}
                            {--diagnose : Report draft count mismatch and other diagnostics}';

    protected $description = 'Move auto-assigned transactions that already have payments to collected status';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $diagnose = $this->option('diagnose');

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be saved.');
        }

        if ($diagnose) {
            $this->runDiagnostics();
        }

        $bankUpdated = $this->processBankTransactions($dryRun);
        $c2bUpdated = $this->processC2BTransactions($dryRun);

        $total = $bankUpdated + $c2bUpdated;
        $this->info("Total moved to collected: {$total} (Bank: {$bankUpdated}, C2B: {$c2bUpdated}).");

        if ($dryRun && $total > 0) {
            $this->comment('Run without --dry-run to apply changes.');
        }

        return 0;
    }

    protected function runDiagnostics(): void
    {
        $this->info('=== Diagnostics ===');

        // Bank: status=draft vs draft tab criteria (match_status/confidence)
        $statusDraft = BankStatementTransaction::where('status', 'draft')
            ->where('is_duplicate', false)
            ->where('is_archived', false)
            ->count();

        $draftTabCriteria = BankStatementTransaction::where(function ($q) {
            $q->where('match_status', 'multiple_matches')
                ->orWhere(function ($q2) {
                    $q2->where('match_status', 'matched')
                        ->where('match_confidence', '>', 0)
                        ->where('match_confidence', '<', 0.85);
                });
        })
            ->where('payment_created', false)
            ->where('is_duplicate', false)
            ->where('is_archived', false)
            ->where('transaction_type', 'credit')
            ->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Bank: status = "draft"', $statusDraft],
                ['Bank: draft tab criteria (match_status/confidence)', $draftTabCriteria],
            ]
        );

        if ($statusDraft > 0 && $draftTabCriteria === 0) {
            $this->warn('Draft mismatch: You have ' . $statusDraft . ' transactions with status=draft but the draft tab shows 0 (it uses match_status/confidence, not status).');
        }
    }

    protected function processBankTransactions(bool $dryRun): int
    {
        $updated = 0;
        $candidates = BankStatementTransaction::where('match_status', 'matched')
            ->where('match_confidence', '>=', 0.85)
            ->where('payment_created', false)
            ->where('is_duplicate', false)
            ->where('is_archived', false)
            ->where('transaction_type', 'credit')
            ->get();

        foreach ($candidates as $txn) {
            $ref = $txn->reference_number;
            if (!$ref) {
                continue;
            }

            $payments = Payment::where('reversed', false)
                ->whereNull('deleted_at')
                ->where(function ($q) use ($ref) {
                    $q->where('transaction_code', $ref)
                        ->orWhere('transaction_code', 'like', $ref . '-%');
                })
                ->get();

            $activeSum = (float) $payments->sum('amount');
            $needed = (float) $txn->amount - 0.01;

            if ($activeSum >= $needed) {
                $firstPayment = $payments->first();
                if (!$dryRun) {
                    $txn->update([
                        'payment_created' => true,
                        'payment_id' => $firstPayment->id,
                        'status' => 'confirmed',
                        'match_status' => 'manual',
                    ]);
                }
                $updated++;
                $this->line("  Bank txn id={$txn->id} ref={$ref} -> payment_id={$firstPayment->id} (collected)");
            }
        }

        $this->info("Bank transactions moved to collected: {$updated}.");
        return $updated;
    }

    protected function processC2BTransactions(bool $dryRun): int
    {
        $updated = 0;

        if (!Schema::hasTable('mpesa_c2b_transactions')) {
            $this->comment('C2B table not found, skipping.');
            return 0;
        }

        // Matches the "auto-assigned" tab filter used by UnifiedTransactionService so we don't
        // miss rows that were matched by channels that never set allocation_status.
        $candidates = MpesaC2BTransaction::where('match_confidence', '>=', 80)
            ->whereNull('payment_id')
            ->where('is_duplicate', false)
            ->get();

        foreach ($candidates as $c2b) {
            $ref = $c2b->trans_id;
            if (!$ref) {
                continue;
            }

            $payments = Payment::where('reversed', false)
                ->whereNull('deleted_at')
                ->where(function ($q) use ($ref) {
                    $q->where('transaction_code', $ref)
                        ->orWhere('transaction_code', 'like', $ref . '-%');
                })
                ->get();

            $activeSum = (float) $payments->sum('amount');
            $needed = (float) $c2b->trans_amount - 0.01;

            if ($activeSum >= $needed) {
                $firstPayment = $payments->first();
                if (!$dryRun) {
                    $c2b->update([
                        'payment_id' => $firstPayment->id,
                        'status' => 'processed',
                        'allocated_amount' => $c2b->trans_amount,
                        'unallocated_amount' => 0,
                    ]);
                }
                $updated++;
                $this->line("  C2B id={$c2b->id} trans_id={$ref} -> payment_id={$firstPayment->id} (collected)");
            }
        }

        $this->info("C2B transactions moved to collected: {$updated}.");
        return $updated;
    }
}
