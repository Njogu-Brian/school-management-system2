<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Student, SwimmingWallet, SwimmingLedger, SwimmingAttendance, InvoiceItem};

class CheckSwimmingAttendanceAndWallets extends Command
{
    protected $signature = 'check:swimming-attendance-wallets {admission_number?}';
    protected $description = 'Check swimming attendance records and wallet status';

    public function handle()
    {
        $admissionNumber = $this->argument('admission_number');
        
        if ($admissionNumber) {
            $this->checkStudent($admissionNumber);
        } else {
            // Check all students who had invoice items deleted
            $this->info('Checking all students who had swimming_attendance invoice items deleted...');
            $this->newLine();
            
            $deletedItems = InvoiceItem::onlyTrashed()
                ->where('source', 'swimming_attendance')
                ->with(['invoice.student'])
                ->get();
            
            $studentIds = $deletedItems->pluck('invoice.student_id')->filter()->unique();
            
            $this->info("Found {$deletedItems->count()} deleted invoice items for " . $studentIds->count() . " students");
            $this->newLine();
            
            foreach ($studentIds->take(10) as $studentId) {
                $student = Student::find($studentId);
                if ($student) {
                    $this->checkStudent($student->admission_number, false);
                    $this->newLine();
                }
            }
            
            if ($studentIds->count() > 10) {
                $this->line("... and " . ($studentIds->count() - 10) . " more students");
            }
        }
        
        return 0;
    }
    
    private function checkStudent($admissionNumber, $showHeader = true)
    {
        $student = Student::where('admission_number', $admissionNumber)->first();
        if (!$student) {
            $this->error("Student with admission number {$admissionNumber} not found");
            return;
        }
        
        if ($showHeader) {
            $this->info("Student: {$student->first_name} {$student->last_name} (ID: {$student->id})");
            $this->newLine();
        } else {
            $this->line("Student: {$student->first_name} {$student->last_name} ({$admissionNumber})");
        }
        
        // Check swimming attendance records
        $attendanceCount = SwimmingAttendance::where('student_id', $student->id)->count();
        $this->line("  Swimming Attendance Records: {$attendanceCount}");
        
        if ($attendanceCount > 0) {
            $attendances = SwimmingAttendance::where('student_id', $student->id)
                ->orderBy('attendance_date', 'desc')
                ->take(5)
                ->get();
            
            $this->line("  Recent attendance:");
            foreach ($attendances as $att) {
                $status = $att->payment_status === 'paid' ? '✓' : '✗';
                $this->line("    {$status} {$att->attendance_date->format('Y-m-d')} - Cost: {$att->session_cost}, Termly: " . ($att->termly_fee_covered ? 'Yes' : 'No'));
            }
        }
        
        // Check wallet
        $wallet = SwimmingWallet::where('student_id', $student->id)->first();
        if ($wallet) {
            $this->line("  Wallet Balance: {$wallet->balance}");
            $this->line("  Total Credited: {$wallet->total_credited}");
            $this->line("  Total Debited: {$wallet->total_debited}");
        } else {
            $this->warn("  ⚠️  No wallet found");
        }
        
        // Check ledger entries
        $ledgerCount = SwimmingLedger::where('student_id', $student->id)->count();
        $this->line("  Ledger Entries: {$ledgerCount}");
        
        if ($ledgerCount > 0) {
            $ledgers = SwimmingLedger::where('student_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get();
            
            foreach ($ledgers as $ledger) {
                $this->line("    - {$ledger->created_at->format('Y-m-d')}: {$ledger->type} {$ledger->amount} ({$ledger->source})");
            }
        } else {
            $this->warn("  ⚠️  No ledger entries found - wallet should have debits if attendance exists!");
        }
        
        // Check if there's a mismatch
        if ($attendanceCount > 0 && $ledgerCount === 0) {
            $this->error("  ❌ PROBLEM: Student has attendance records but NO wallet ledger entries!");
            $this->error("     This means attendance was marked but wallet was never debited.");
        }
    }
}
