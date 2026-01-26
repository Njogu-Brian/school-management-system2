<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;

class CheckTransaction extends Command
{
    protected $signature = 'transaction:check {id}';

    protected $description = 'Check a specific transaction status and payment linkage';

    public function handle()
    {
        $id = $this->argument('id');
        
        // Check bank statement transaction
        $bankTxn = BankStatementTransaction::find($id);
        if ($bankTxn) {
            $this->info("Bank Statement Transaction #{$id}");
            $this->line("Status: {$bankTxn->status}");
            $this->line("Payment ID: " . ($bankTxn->payment_id ?? 'NULL'));
            $this->line("Payment Created: " . ($bankTxn->payment_created ? 'true' : 'false'));
            $this->line("Reference: " . ($bankTxn->reference_number ?? 'NULL'));
            $this->line("Student ID: " . ($bankTxn->student_id ?? 'NULL'));
            
            if ($bankTxn->payment_id) {
                $payment = Payment::find($bankTxn->payment_id);
                if ($payment) {
                    $this->line("Payment Receipt: {$payment->receipt_number}");
                    $this->line("Payment Reversed: " . ($payment->reversed ? 'true' : 'false'));
                    $this->line("Payment Amount: " . number_format($payment->amount, 2));
                } else {
                    $this->warn("Payment not found!");
                }
            }
            
            // Check by reference
            if ($bankTxn->reference_number) {
                $refPayment = Payment::where('transaction_code', $bankTxn->reference_number)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->first();
                if ($refPayment) {
                    $this->info("Found payment by reference: {$refPayment->receipt_number} (ID: {$refPayment->id})");
                    if (!$bankTxn->payment_id || $bankTxn->payment_id != $refPayment->id) {
                        $this->warn("⚠️  Payment exists but not linked to transaction!");
                    }
                }
            }
            
            return 0;
        }
        
        // Check C2B transaction
        $c2bTxn = MpesaC2BTransaction::find($id);
        if ($c2bTxn) {
            $this->info("C2B Transaction #{$id}");
            $this->line("Status: {$c2bTxn->status}");
            $this->line("Payment ID: " . ($c2bTxn->payment_id ?? 'NULL'));
            $this->line("Trans ID: " . ($c2bTxn->trans_id ?? 'NULL'));
            $this->line("Student ID: " . ($c2bTxn->student_id ?? 'NULL'));
            
            if ($c2bTxn->payment_id) {
                $payment = Payment::find($c2bTxn->payment_id);
                if ($payment) {
                    $this->line("Payment Receipt: {$payment->receipt_number}");
                    $this->line("Payment Reversed: " . ($payment->reversed ? 'true' : 'false'));
                    $this->line("Payment Amount: " . number_format($payment->amount, 2));
                } else {
                    $this->warn("Payment not found!");
                }
            }
            
            // Check by trans_id
            if ($c2bTxn->trans_id) {
                $refPayment = Payment::where('transaction_code', $c2bTxn->trans_id)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->first();
                if ($refPayment) {
                    $this->info("Found payment by trans_id: {$refPayment->receipt_number} (ID: {$refPayment->id})");
                    if (!$c2bTxn->payment_id || $c2bTxn->payment_id != $refPayment->id) {
                        $this->warn("⚠️  Payment exists but not linked to transaction!");
                    }
                }
            }
            
            return 0;
        }
        
        $this->error("Transaction #{$id} not found in either table");
        return 1;
    }
}
