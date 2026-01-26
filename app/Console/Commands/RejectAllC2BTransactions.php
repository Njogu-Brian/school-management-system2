<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RejectAllC2BTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c2b:reject-all 
                            {--dry-run : Show what would be rejected without making changes}
                            {--status= : Only reject transactions with specific status (pending, processed, failed)}
                            {--with-payments : Also reject transactions that have payments (will reverse payments)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reject all C2B transactions to allow manual matching';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $statusFilter = $this->option('status');
        $withPayments = $this->option('with-payments');
        
        $this->info('Finding C2B transactions to reject...');
        
        // Build query
        $query = MpesaC2BTransaction::query();
        
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }
        
        // If not including payments, exclude transactions with active payments
        if (!$withPayments) {
            $query->where(function($q) {
                $q->whereNull('payment_id')
                  ->orWhereNotIn('payment_id', function($subQ) {
                      $subQ->select('id')
                          ->from('payments')
                          ->where('reversed', false)
                          ->whereNull('deleted_at');
                  });
            });
        }
        
        $transactions = $query->get();
        
        if ($transactions->isEmpty()) {
            $this->info('No C2B transactions found to reject.');
            return 0;
        }
        
        $this->info("Found {$transactions->count()} C2B transaction(s) to reject.");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }
        
        $rejected = 0;
        $errors = 0;
        
        foreach ($transactions as $transaction) {
            try {
                if ($dryRun) {
                    $this->line("Would reject: Transaction ID {$transaction->id}, Ref: {$transaction->trans_id}, Status: {$transaction->status}, Student: {$transaction->student_id}");
                } else {
                    DB::transaction(function () use ($transaction) {
                        $this->rejectTransaction($transaction);
                    });
                    $rejected++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to reject transaction {$transaction->id}: " . $e->getMessage());
                Log::error('Failed to reject C2B transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        if ($dryRun) {
            $this->newLine();
            $this->info("Would reject {$transactions->count()} transaction(s).");
            $this->comment('Run without --dry-run to actually reject them.');
        } else {
            $this->newLine();
            if ($rejected > 0) {
                $this->info("Successfully rejected {$rejected} transaction(s).");
            }
            if ($errors > 0) {
                $this->error("Failed to reject {$errors} transaction(s).");
            }
        }
        
        return 0;
    }

    /**
     * Reject a single C2B transaction
     */
    protected function rejectTransaction(MpesaC2BTransaction $transaction)
    {
        // 1. Find and reverse ALL related payments
        $relatedPayments = collect();
        $ref = $transaction->trans_id;
        
        if ($ref) {
            $relatedPayments = Payment::where('reversed', false)
                ->where(function ($q) use ($ref) {
                    $q->where('transaction_code', $ref)
                      ->orWhere('transaction_code', 'LIKE', $ref . '-%');
                })
                ->get();
        }
        
        if ($relatedPayments->isEmpty() && $transaction->payment_id) {
            $p = Payment::find($transaction->payment_id);
            if ($p && !$p->reversed) {
                $relatedPayments = collect([$p]);
            }
        }
        
        // Reverse all related payments
        foreach ($relatedPayments as $payment) {
            if (!$payment || $payment->reversed) {
                continue;
            }
            
            // Delete allocations
            $invoiceIds = collect();
            foreach ($payment->allocations as $allocation) {
                if ($allocation->invoiceItem && $allocation->invoiceItem->invoice) {
                    $invoiceIds->push($allocation->invoiceItem->invoice_id);
                }
            }
            foreach ($payment->allocations as $allocation) {
                $allocation->delete();
            }
            
            // Reverse payment
            $payment->update([
                'reversed' => true,
                'reversed_by' => 1, // System user
                'reversed_at' => now(),
                'reversal_reason' => 'Transaction rejected – reset to unassigned (bulk reject)',
            ]);
            
            // Recalculate invoices
            foreach ($invoiceIds->unique() as $invoiceId) {
                $invoice = \App\Models\Invoice::find($invoiceId);
                if ($invoice) {
                    \App\Services\InvoiceService::recalc($invoice);
                }
            }
            
            // Soft delete payment
            $payment->delete();
            
            Log::info('Payment reversed and deleted due to C2B transaction rejection', [
                'transaction_id' => $transaction->id,
                'payment_id' => $payment->id,
            ]);
        }
        
        // 2. Reverse swimming allocations if this is a swimming transaction
        if ($transaction->student_id) {
            $wallet = \App\Models\SwimmingWallet::where('student_id', $transaction->student_id)->first();
            if ($wallet && $transaction->payment_id) {
                $payment = Payment::withTrashed()->find($transaction->payment_id);
                if ($payment) {
                    $oldBalance = $wallet->balance;
                    $newBalance = max(0, $oldBalance - $transaction->trans_amount);
                    $wallet->update([
                        'balance' => $newBalance,
                        'total_debited' => ($wallet->total_debited ?? 0) + $transaction->trans_amount,
                        'last_transaction_at' => now(),
                    ]);
                    
                    // Create ledger entry if table exists
                    if (\Illuminate\Support\Facades\Schema::hasTable('swimming_ledgers')) {
                        \App\Models\SwimmingLedger::create([
                            'student_id' => $transaction->student_id,
                            'type' => \App\Models\SwimmingLedger::TYPE_DEBIT,
                            'amount' => $transaction->trans_amount,
                            'balance_after' => $newBalance,
                            'source' => \App\Models\SwimmingLedger::SOURCE_ADJUSTMENT,
                            'description' => 'Transaction rejected – allocation reversed (bulk reject): ' . ($transaction->trans_id ?? 'N/A'),
                            'created_by' => 1, // System user
                        ]);
                    }
                }
            }
        }
        
        // 3. Reset transaction to unmatched state
        $transaction->update([
            'student_id' => null,
            'payment_id' => null,
            'status' => 'pending',
            'allocation_status' => 'unallocated',
            'match_confidence' => 0,
            'match_reason' => null,
            'matching_suggestions' => null,
        ]);
        
        Log::info('C2B transaction rejected and reset to unassigned', [
            'transaction_id' => $transaction->id,
            'trans_id' => $transaction->trans_id,
        ]);
    }
}
