<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CheckC2BDuplicatesAndIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c2b:check-duplicates 
                            {--detailed : Show detailed information for each issue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for duplicate IDs between bank statements and C2B transactions, and identify payment conflicts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for duplicate IDs and payment issues...');
        $this->newLine();

        // 1. Check for duplicate IDs
        $this->info('=== Checking for Duplicate IDs ===');
        $duplicateIds = $this->findDuplicateIds();
        
        if ($duplicateIds->isEmpty()) {
            $this->info('✓ No duplicate IDs found between bank statements and C2B transactions.');
        } else {
            $this->warn("⚠ Found {$duplicateIds->count()} duplicate ID(s):");
            foreach ($duplicateIds as $id) {
                $bank = BankStatementTransaction::find($id);
                $c2b = MpesaC2BTransaction::find($id);
                
                $this->line("  ID {$id}:");
                if ($bank) {
                    $this->line("    Bank Statement: #{$bank->id}, Ref: {$bank->reference_number}, Amount: {$bank->amount}, Status: {$bank->status}, Student: {$bank->student_id}");
                }
                if ($c2b) {
                    $this->line("    C2B Transaction: #{$c2b->id}, Ref: {$c2b->trans_id}, Amount: {$c2b->trans_amount}, Status: {$c2b->status}, Student: {$c2b->student_id}");
                }
            }
        }
        $this->newLine();

        // 2. Check for C2B transactions with payment conflicts
        $this->info('=== Checking C2B Transactions with Payment Issues ===');
        $paymentIssues = $this->findPaymentIssues();
        
        if ($paymentIssues->isEmpty()) {
            $this->info('✓ No payment issues found in C2B transactions.');
        } else {
            $this->warn("⚠ Found {$paymentIssues->count()} C2B transaction(s) with payment issues:");
            foreach ($paymentIssues as $issue) {
                $this->line("  Transaction ID {$issue['transaction_id']}:");
                $this->line("    Issue: {$issue['issue']}");
                if ($this->option('detailed')) {
                    $this->line("    Reference: {$issue['reference']}");
                    $this->line("    Amount: {$issue['amount']}");
                    $this->line("    Status: {$issue['status']}");
                    if (isset($issue['payment_id'])) {
                        $this->line("    Payment ID: {$issue['payment_id']}");
                    }
                    if (isset($issue['payment_details'])) {
                        $this->line("    Payment Details: {$issue['payment_details']}");
                    }
                }
            }
        }
        $this->newLine();

        // 3. Check for C2B transactions with reversed/deleted payments
        $this->info('=== Checking C2B Transactions with Reversed/Deleted Payments ===');
        $reversedPaymentIssues = $this->findReversedPaymentIssues();
        
        if ($reversedPaymentIssues->isEmpty()) {
            $this->info('✓ No C2B transactions found with reversed/deleted payments.');
        } else {
            $this->warn("⚠ Found {$reversedPaymentIssues->count()} C2B transaction(s) with reversed/deleted payments:");
            foreach ($reversedPaymentIssues as $issue) {
                $this->line("  Transaction ID {$issue['transaction_id']}:");
                $this->line("    Reference: {$issue['reference']}");
                $this->line("    Payment ID: {$issue['payment_id']}");
                $this->line("    Payment Status: " . ($issue['payment_reversed'] ? 'REVERSED' : 'DELETED'));
                if ($issue['payment_reversed'] && isset($issue['reversal_reason'])) {
                    $this->line("    Reversal Reason: {$issue['reversal_reason']}");
                }
            }
        }
        $this->newLine();

        // Summary
        $totalIssues = $duplicateIds->count() + $paymentIssues->count() + $reversedPaymentIssues->count();
        if ($totalIssues > 0) {
            $this->error("Total issues found: {$totalIssues}");
            $this->newLine();
            $this->comment('Recommendations:');
            $this->comment('1. For duplicate IDs: These need to be resolved manually as they share the same ID in different tables.');
            $this->comment('2. For payment issues: Review each transaction and either link the correct payment or create a new one.');
            $this->comment('3. For reversed/deleted payments: Consider rejecting these transactions to allow manual re-matching.');
            $this->comment('4. Run "php artisan c2b:reject-all" to reject all C2B transactions for manual matching.');
        } else {
            $this->info('✓ No issues found! All C2B transactions are clean.');
        }

        return 0;
    }

    /**
     * Find duplicate IDs between bank statements and C2B transactions
     */
    protected function findDuplicateIds()
    {
        $bankIds = BankStatementTransaction::pluck('id');
        $c2bIds = MpesaC2BTransaction::pluck('id');
        
        return $bankIds->intersect($c2bIds);
    }

    /**
     * Find C2B transactions with payment issues
     */
    protected function findPaymentIssues()
    {
        $issues = collect();
        
        $c2bTransactions = MpesaC2BTransaction::all();
        
        foreach ($c2bTransactions as $transaction) {
            $issue = null;
            $paymentDetails = null;
            
            // Check if payment_id is set but payment doesn't exist or is reversed/deleted
            if ($transaction->payment_id) {
                $payment = Payment::withTrashed()->find($transaction->payment_id);
                if (!$payment) {
                    $issue = "Payment ID {$transaction->payment_id} set but payment not found (deleted)";
                } elseif ($payment->reversed || $payment->deleted_at) {
                    $issue = "Payment ID {$transaction->payment_id} is reversed or deleted";
                    $paymentDetails = "Reversed: " . ($payment->reversed ? 'YES' : 'NO') . ", Deleted: " . ($payment->deleted_at ? 'YES' : 'NO');
                } elseif ($payment->student_id != $transaction->student_id) {
                    $issue = "Payment ID {$transaction->payment_id} exists but for different student (Payment student: {$payment->student_id}, Transaction student: {$transaction->student_id})";
                    $paymentDetails = "Payment student: {$payment->student_id}, Transaction student: {$transaction->student_id}";
                }
            }
            
            // Check if transaction_code matches a payment but payment_id is not set
            if (!$issue && $transaction->trans_id) {
                $paymentByCode = Payment::where('transaction_code', $transaction->trans_id)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->first();
                
                if ($paymentByCode && $transaction->payment_id != $paymentByCode->id) {
                    $issue = "Payment exists with transaction_code '{$transaction->trans_id}' but payment_id is not linked";
                    $paymentDetails = "Payment ID: {$paymentByCode->id}, Student: {$paymentByCode->student_id}";
                }
            }
            
            // Check if status is 'processed' but no payment exists
            if (!$issue && $transaction->status === 'processed' && !$transaction->payment_id) {
                $paymentByCode = Payment::where('transaction_code', $transaction->trans_id)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->first();
                
                if (!$paymentByCode) {
                    $issue = "Status is 'processed' but no payment found";
                }
            }
            
            if ($issue) {
                $issues->push([
                    'transaction_id' => $transaction->id,
                    'reference' => $transaction->trans_id,
                    'amount' => $transaction->trans_amount,
                    'status' => $transaction->status,
                    'issue' => $issue,
                    'payment_id' => $transaction->payment_id,
                    'payment_details' => $paymentDetails,
                ]);
            }
        }
        
        return $issues;
    }

    /**
     * Find C2B transactions with reversed/deleted payments
     */
    protected function findReversedPaymentIssues()
    {
        $issues = collect();
        
        $c2bTransactions = MpesaC2BTransaction::whereNotNull('payment_id')->get();
        
        foreach ($c2bTransactions as $transaction) {
            $payment = Payment::withTrashed()->find($transaction->payment_id);
            
            if ($payment && ($payment->reversed || $payment->deleted_at)) {
                $issues->push([
                    'transaction_id' => $transaction->id,
                    'reference' => $transaction->trans_id,
                    'payment_id' => $transaction->payment_id,
                    'payment_reversed' => $payment->reversed,
                    'reversal_reason' => $payment->reversal_reason ?? null,
                ]);
            }
        }
        
        return $issues;
    }
}
