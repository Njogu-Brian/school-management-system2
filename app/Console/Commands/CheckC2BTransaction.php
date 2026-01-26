<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;

class CheckC2BTransaction extends Command
{
    protected $signature = 'c2b:check {id : The transaction ID to check}';
    protected $description = 'Check the status of a specific C2B transaction';

    public function handle()
    {
        $id = $this->argument('id');
        
        $transaction = MpesaC2BTransaction::find($id);
        
        if (!$transaction) {
            $this->error("Transaction #{$id} not found.");
            return 1;
        }
        
        $this->info("Transaction #{$id} Details:");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $transaction->id],
                ['Trans ID', $transaction->trans_id],
                ['Amount', 'KES ' . number_format($transaction->trans_amount, 2)],
                ['Status', $transaction->status],
                ['Payment ID', $transaction->payment_id ?? 'NULL'],
                ['Allocation Status', $transaction->allocation_status ?? 'N/A'],
                ['Is Swimming', $transaction->is_swimming_transaction ? 'Yes' : 'No'],
            ]
        );
        
        // Check for payments by payment_id
        if ($transaction->payment_id) {
            $payment = Payment::find($transaction->payment_id);
            if ($payment) {
                $this->info("\nPayment found by payment_id:");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Payment ID', $payment->id],
                        ['Receipt Number', $payment->receipt_number ?? 'N/A'],
                        ['Transaction Code', $payment->transaction_code ?? 'N/A'],
                        ['Amount', 'KES ' . number_format($payment->amount, 2)],
                        ['Reversed', $payment->reversed ? 'Yes' : 'No'],
                        ['Student ID', $payment->student_id ?? 'N/A'],
                    ]
                );
            } else {
                $this->warn("\nPayment ID {$transaction->payment_id} not found in payments table.");
            }
        }
        
        // Check for payments by transaction_code (exact match)
        if ($transaction->trans_id) {
            $paymentsByCode = Payment::where('transaction_code', $transaction->trans_id)
                ->get();
            
            if ($paymentsByCode->isNotEmpty()) {
                $this->info("\nPayment(s) found by transaction_code (exact match: {$transaction->trans_id}):");
                foreach ($paymentsByCode as $payment) {
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Payment ID', $payment->id],
                            ['Receipt Number', $payment->receipt_number ?? 'N/A'],
                            ['Transaction Code', $payment->transaction_code ?? 'N/A'],
                            ['Amount', 'KES ' . number_format($payment->amount, 2)],
                            ['Reversed', $payment->reversed ? 'Yes' : 'No'],
                            ['Student ID', $payment->student_id ?? 'N/A'],
                        ]
                    );
                }
            } else {
                $this->warn("\nNo payments found with transaction_code = {$transaction->trans_id}");
            }
            
            // Also check for payments with pattern match (ref-*)
            $paymentsByPattern = Payment::where('transaction_code', 'LIKE', $transaction->trans_id . '-%')
                ->get();
            
            if ($paymentsByPattern->isNotEmpty()) {
                $this->info("\nPayment(s) found by transaction_code pattern ({$transaction->trans_id}-*):");
                foreach ($paymentsByPattern as $payment) {
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Payment ID', $payment->id],
                            ['Receipt Number', $payment->receipt_number ?? 'N/A'],
                            ['Transaction Code', $payment->transaction_code ?? 'N/A'],
                            ['Amount', 'KES ' . number_format($payment->amount, 2)],
                            ['Reversed', $payment->reversed ? 'Yes' : 'No'],
                            ['Student ID', $payment->student_id ?? 'N/A'],
                        ]
                    );
                }
            }
        }
        
        // Check if transaction needs fixing
        $needsFix = false;
        $reason = [];
        
        if ($transaction->status !== 'processed') {
            $hasPayment = false;
            if ($transaction->payment_id) {
                $p = Payment::find($transaction->payment_id);
                if ($p && !$p->reversed) {
                    $hasPayment = true;
                }
            }
            if (!$hasPayment && $transaction->trans_id) {
                $p = Payment::where('transaction_code', $transaction->trans_id)
                    ->where('reversed', false)
                    ->first();
                if ($p) {
                    $hasPayment = true;
                }
            }
            
            if ($hasPayment) {
                $needsFix = true;
                $reason[] = "Has payment but status is '{$transaction->status}' (should be 'processed')";
            }
        }
        
        if ($needsFix) {
            $this->newLine();
            $this->warn("⚠️  This transaction needs fixing!");
            foreach ($reason as $r) {
                $this->line("  - {$r}");
            }
            $this->newLine();
            if ($this->confirm('Would you like to fix this transaction now?', true)) {
                $payment = null;
                if ($transaction->payment_id) {
                    $payment = Payment::find($transaction->payment_id);
                }
                if (!$payment && $transaction->trans_id) {
                    $payment = Payment::where('transaction_code', $transaction->trans_id)
                        ->where('reversed', false)
                        ->first();
                }
                
                if ($payment && !$payment->reversed) {
                    $transaction->update([
                        'status' => 'processed',
                        'payment_id' => $payment->id,
                    ]);
                    $this->info("✅ Transaction #{$id} has been fixed!");
                    $this->info("   - Status updated to 'processed'");
                    $this->info("   - Payment ID linked: {$payment->id}");
                } else {
                    $this->error("Could not find a valid payment to link.");
                }
            }
        } else {
            $this->info("\n✅ Transaction status is correct.");
        }
        
        return 0;
    }
}
