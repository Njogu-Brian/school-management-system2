<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Student, SwimmingWallet, SwimmingLedger, InvoiceItem};

class CheckSwimmingWallet extends Command
{
    protected $signature = 'check:swimming-wallet {admission_number}';
    protected $description = 'Check swimming wallet status for a student';

    public function handle()
    {
        $admissionNumber = $this->argument('admission_number');
        
        $student = Student::where('admission_number', $admissionNumber)->first();
        if (!$student) {
            $this->error("Student with admission number {$admissionNumber} not found");
            return 1;
        }
        
        $this->info("Student: {$student->first_name} {$student->last_name} (ID: {$student->id})");
        $this->newLine();
        
        // Check wallet
        $wallet = SwimmingWallet::where('student_id', $student->id)->first();
        if ($wallet) {
            $this->info("Wallet Status:");
            $this->line("  Balance: {$wallet->balance}");
            $this->line("  Total Credited: {$wallet->total_credited}");
            $this->line("  Total Debited: {$wallet->total_debited}");
            $this->line("  Last Transaction: " . ($wallet->last_transaction_at ? $wallet->last_transaction_at->format('Y-m-d H:i:s') : 'Never'));
        } else {
            $this->warn("No wallet found for this student");
        }
        
        $this->newLine();
        
        // Check ledger entries
        $ledgerCount = SwimmingLedger::where('student_id', $student->id)->count();
        $this->info("Ledger Entries: {$ledgerCount}");
        
        if ($ledgerCount > 0) {
            $ledgers = SwimmingLedger::where('student_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            
            $this->line("Recent transactions:");
            foreach ($ledgers as $ledger) {
                $this->line("  - {$ledger->created_at->format('Y-m-d')}: {$ledger->type} {$ledger->amount} ({$ledger->source}) - Balance: {$ledger->balance_after}");
            }
        }
        
        $this->newLine();
        
        // Check if there were invoice items with swimming_attendance source
        $invoiceItems = InvoiceItem::whereHas('invoice', function($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->where('source', 'swimming_attendance')
        ->withTrashed()
        ->get();
        
        $this->info("Invoice Items with source='swimming_attendance':");
        $this->line("  Active: " . $invoiceItems->where('deleted_at', null)->count());
        $this->line("  Deleted: " . $invoiceItems->where('deleted_at', '!=', null)->count());
        
        if ($invoiceItems->where('deleted_at', '!=', null)->count() > 0) {
            $this->warn("  ⚠️  Some invoice items were deleted (likely by cleanup command)");
        }
        
        return 0;
    }
}
