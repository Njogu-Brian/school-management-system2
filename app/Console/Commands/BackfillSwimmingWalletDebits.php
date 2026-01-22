<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{SwimmingAttendance, SwimmingLedger, SwimmingWallet};
use App\Services\SwimmingWalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillSwimmingWalletDebits extends Command
{
    protected $signature = 'backfill:swimming-wallet-debits {--dry-run : Show what would be processed without actually processing}';
    protected $description = 'Backfill wallet debits for swimming attendance records that were marked but never debited to wallet';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Finding swimming attendance records without wallet ledger entries...');
        $this->newLine();
        
        // Find attendance records that don't have corresponding ledger entries
        // Check by looking for ledger entries with source='attendance' and source_id matching attendance id
        $allAttendances = SwimmingAttendance::where('session_cost', '>', 0)
            ->with('student')
            ->get();
        
        $attendances = $allAttendances->filter(function($attendance) {
            // Check if there's a ledger entry for this attendance
            $hasLedger = SwimmingLedger::where('student_id', $attendance->student_id)
                ->where('source', SwimmingLedger::SOURCE_ATTENDANCE)
                ->where('source_id', $attendance->id)
                ->exists();
            
            return !$hasLedger;
        });
        
        $this->info("Found {$attendances->count()} attendance records without wallet debits");
        
        if ($attendances->isEmpty()) {
            $this->info('No records to process.');
            return 0;
        }
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No wallet debits will be created');
            $this->newLine();
            
            foreach ($attendances->take(10) as $attendance) {
                $student = $attendance->student;
                $this->line("Would debit: {$student->first_name} {$student->last_name} ({$student->admission_number})");
                $this->line("  Attendance: {$attendance->attendance_date->format('Y-m-d')} - Cost: {$attendance->session_cost}");
                $this->line("  Termly Fee Covered: " . ($attendance->termly_fee_covered ? 'Yes' : 'No'));
            }
            
            if ($attendances->count() > 10) {
                $this->line("... and " . ($attendances->count() - 10) . " more records");
            }
            
            return 0;
        }
        
        $this->warn('This will create wallet debit entries for attendance records.');
        $this->warn('This will NOT affect invoice items (they were already cleaned up).');
        if (!$this->confirm('Continue?')) {
            $this->info('Cancelled.');
            return 0;
        }
        
        $walletService = app(SwimmingWalletService::class);
        $processed = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($attendances as $attendance) {
            try {
                DB::transaction(function () use ($attendance, $walletService, &$processed) {
                    $student = $attendance->student;
                    
                    if (!$student) {
                        throw new \Exception("Student not found for attendance ID {$attendance->id}");
                    }
                    
                    // Check if ledger entry already exists (double-check)
                    $existingLedger = SwimmingLedger::where('student_id', $student->id)
                        ->where('source', SwimmingLedger::SOURCE_ATTENDANCE)
                        ->where('source_id', $attendance->id)
                        ->first();
                    
                    if ($existingLedger) {
                        // Already has ledger entry, skip
                        return;
                    }
                    
                    // Debit wallet for this attendance
                    $walletService->debitForAttendance(
                        $student,
                        (float)$attendance->session_cost,
                        $attendance->id,
                        "Backfilled debit for swimming attendance on {$attendance->attendance_date->format('Y-m-d')}"
                    );
                    
                    // Update attendance payment status if it was unpaid
                    if ($attendance->payment_status === SwimmingAttendance::STATUS_UNPAID) {
                        $attendance->update(['payment_status' => SwimmingAttendance::STATUS_PAID]);
                    }
                    
                    $processed++;
                });
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Attendance ID {$attendance->id}: " . $e->getMessage();
                Log::error('Failed to backfill wallet debit for attendance', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $this->newLine();
        $this->info("Processed: {$processed} attendance records");
        if ($failed > 0) {
            $this->warn("Failed: {$failed} records");
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }
        
        return 0;
    }
}
