<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MpesaC2BTransaction;
use Illuminate\Support\Facades\DB;

class FixC2BTransactionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c2b:fix-status 
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix C2B transactions that have payments but status is not "processed"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Finding C2B transactions with payments but incorrect status...');
        
        // Find C2B transactions that have payments (by payment_id OR by transaction_code match)
        // but status is not 'processed'
        $allTransactions = MpesaC2BTransaction::where('status', '!=', 'processed')
            ->where('status', '!=', 'failed') // Don't update failed transactions
            ->get();
        
        $transactions = collect();
        foreach ($allTransactions as $transaction) {
            $hasPayment = false;
            
            // Check by payment_id
            if ($transaction->payment_id) {
                $payment = \App\Models\Payment::find($transaction->payment_id);
                if ($payment && !$payment->reversed) {
                    $hasPayment = true;
                }
            }
            
            // Also check by transaction_code (trans_id)
            if (!$hasPayment && $transaction->trans_id) {
                $paymentByCode = \App\Models\Payment::where('transaction_code', $transaction->trans_id)
                    ->where('reversed', false)
                    ->first();
                if ($paymentByCode) {
                    $hasPayment = true;
                    // Link the payment_id if not set
                    if (!$transaction->payment_id) {
                        $transaction->payment_id = $paymentByCode->id;
                    }
                }
            }
            
            if ($hasPayment) {
                $transactions->push($transaction);
            }
        }
        
        if ($transactions->isEmpty()) {
            $this->info('No transactions found that need fixing.');
            return 0;
        }
        
        $this->info("Found {$transactions->count()} transaction(s) that need fixing.");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->table(
                ['ID', 'Trans ID', 'Amount', 'Current Status', 'Payment ID', 'Would Update To'],
                $transactions->map(function($txn) {
                    return [
                        $txn->id,
                        $txn->trans_id,
                        number_format($txn->trans_amount, 2),
                        $txn->status,
                        $txn->payment_id,
                        'processed'
                    ];
                })->toArray()
            );
            return 0;
        }
        
        $updated = 0;
        $linked = 0;
        $fixedTransactions = [];
        
        DB::transaction(function () use ($transactions, &$updated, &$linked, &$fixedTransactions) {
            foreach ($transactions as $transaction) {
                // Get payment (by payment_id or by transaction_code)
                $payment = null;
                $wasLinked = false;
                
                if ($transaction->payment_id) {
                    $payment = \App\Models\Payment::find($transaction->payment_id);
                }
                
                if (!$payment && $transaction->trans_id) {
                    $payment = \App\Models\Payment::where('transaction_code', $transaction->trans_id)
                        ->where('reversed', false)
                        ->first();
                    
                    // Link payment_id if found but not set
                    if ($payment && !$transaction->payment_id) {
                        $transaction->payment_id = $payment->id;
                        $linked++;
                        $wasLinked = true;
                    }
                }
                
                if ($payment && !$payment->reversed) {
                    $oldStatus = $transaction->status;
                    $transaction->update([
                        'status' => 'processed',
                        'payment_id' => $payment->id, // Ensure payment_id is set
                    ]);
                    $updated++;
                    
                    // Store details of fixed transaction
                    $fixedTransactions[] = [
                        'id' => $transaction->id,
                        'trans_id' => $transaction->trans_id,
                        'amount' => number_format($transaction->trans_amount, 2),
                        'old_status' => $oldStatus,
                        'payment_id' => $payment->id,
                        'receipt_number' => $payment->receipt_number ?? $payment->transaction_code,
                        'was_linked' => $wasLinked,
                    ];
                } else {
                    $this->warn("Transaction #{$transaction->id} has no valid payment, skipping.");
                }
            }
        });
        
        if ($linked > 0) {
            $this->info("Linked {$linked} payment(s) to transactions.");
        }
        
        $this->info("Successfully updated {$updated} transaction(s) to 'processed' status.");
        
        if (!empty($fixedTransactions)) {
            $this->newLine();
            $this->info('Fixed Transactions:');
            $this->table(
                ['Transaction ID', 'Trans ID', 'Amount', 'Old Status', 'Payment ID', 'Receipt Number', 'Payment Linked'],
                array_map(function($txn) {
                    return [
                        $txn['id'],
                        $txn['trans_id'],
                        'KES ' . $txn['amount'],
                        $txn['old_status'],
                        $txn['payment_id'],
                        $txn['receipt_number'],
                        $txn['was_linked'] ? 'Yes' : 'No',
                    ];
                }, $fixedTransactions)
            );
        }
        
        return 0;
    }
}
