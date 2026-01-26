<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use App\Models\TransactionFixAudit;
use Illuminate\Support\Facades\DB;

class FixTransactionStatusWithPayments extends Command
{
    protected $signature = 'transactions:fix-status-with-payments {--dry-run : Show what would be fixed without applying}';

    protected $description = 'Update transaction status when payments are linked but status is still draft';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be applied');
            $this->newLine();
        }

        $this->info('🔧 Fixing transaction status for transactions with linked payments...');
        $this->newLine();

        try {
            DB::beginTransaction();

            $fixed = 0;

            // Bank Statement Transactions
            $bankTransactions = BankStatementTransaction::where('status', 'draft')
                ->where(function($q) {
                    $q->whereNotNull('payment_id')
                      ->orWhere('payment_created', true);
                })
                ->get();

            foreach ($bankTransactions as $transaction) {
                $payment = null;
                
                // Check if payment_id exists and is valid
                if ($transaction->payment_id) {
                    $payment = Payment::where('id', $transaction->payment_id)
                        ->where('reversed', false)
                        ->whereNull('deleted_at')
                        ->first();
                }
                
                // If no valid payment by payment_id, check by reference
                if (!$payment && $transaction->reference_number) {
                    $payment = Payment::where('transaction_code', $transaction->reference_number)
                        ->orWhere('transaction_code', 'LIKE', $transaction->reference_number . '-%')
                        ->where('reversed', false)
                        ->whereNull('deleted_at')
                        ->first();
                }

                if ($payment) {
                    $oldValues = [
                        'status' => $transaction->status,
                        'payment_id' => $transaction->payment_id,
                        'payment_created' => $transaction->payment_created,
                    ];

                    $newValues = [
                        'status' => 'confirmed',
                        'payment_id' => $payment->id,
                        'payment_created' => true,
                    ];

                    TransactionFixAudit::create([
                        'fix_type' => 'update_status_with_payment',
                        'entity_type' => 'bank_statement_transaction',
                        'entity_id' => $transaction->id,
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                        'reason' => 'Transaction has linked payment but status was still draft',
                        'applied' => !$dryRun,
                        'applied_at' => $dryRun ? null : now(),
                    ]);

                    if (!$dryRun) {
                        $transaction->update($newValues);
                    }

                    $this->line("   Transaction #{$transaction->id}: Draft → Confirmed (Payment: {$payment->receipt_number})");
                    $fixed++;
                }
            }

            // C2B Transactions
            $c2bTransactions = MpesaC2BTransaction::where('status', 'pending')
                ->whereNotNull('payment_id')
                ->get();

            foreach ($c2bTransactions as $transaction) {
                $payment = Payment::where('id', $transaction->payment_id)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->first();

                if ($payment) {
                    $oldValues = [
                        'status' => $transaction->status,
                        'payment_id' => $transaction->payment_id,
                    ];

                    $newValues = [
                        'status' => 'processed',
                        'payment_id' => $payment->id,
                    ];

                    TransactionFixAudit::create([
                        'fix_type' => 'update_status_with_payment',
                        'entity_type' => 'mpesa_c2b_transaction',
                        'entity_id' => $transaction->id,
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                        'reason' => 'C2B transaction has linked payment but status was still pending',
                        'applied' => !$dryRun,
                        'applied_at' => $dryRun ? null : now(),
                    ]);

                    if (!$dryRun) {
                        $transaction->update($newValues);
                    }

                    $this->line("   C2B Transaction #{$transaction->id}: Pending → Processed (Payment: {$payment->receipt_number})");
                    $fixed++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('⚠️  DRY RUN - All changes rolled back');
            } else {
                DB::commit();
                $this->info("✅ Fixed {$fixed} transactions");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
