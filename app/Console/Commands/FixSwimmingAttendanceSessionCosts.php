<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SwimmingAttendance;
use App\Services\SwimmingAttendanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixSwimmingAttendanceSessionCosts extends Command
{
    protected $signature = 'swimming:fix-attendance-session-costs';
    
    protected $description = 'Fix swimming attendance records with missing session costs';

    protected $attendanceService;

    public function __construct(SwimmingAttendanceService $attendanceService)
    {
        parent::__construct();
        $this->attendanceService = $attendanceService;
    }

    public function handle()
    {
        $this->info('Fixing swimming attendance records with missing session costs...');
        
        // Find all attendance records with session_cost = 0 or null
        $attendances = SwimmingAttendance::where(function($q) {
            $q->where('session_cost', 0)
              ->orWhereNull('session_cost');
        })
        ->with('student')
        ->get();
        
        if ($attendances->isEmpty()) {
            $this->info('No attendance records with missing session costs found.');
            return 0;
        }
        
        $this->info("Found {$attendances->count()} attendance record(s) to fix.");
        
        $fixed = 0;
        $failed = 0;
        
        foreach ($attendances as $attendance) {
            try {
                $student = $attendance->student;
                if (!$student) {
                    $this->warn("Skipping attendance #{$attendance->id}: Student not found");
                    $failed++;
                    continue;
                }
                
                // Determine correct session cost based on whether student has termly fee
                $termlyFee = $this->attendanceService->hasActiveTermlyFee($student, null, $attendance->attendance_date->year ?? null);
                
                if ($termlyFee) {
                    // Student has termly fee - use termly per-visit cost (default: 120)
                    $sessionCost = $this->attendanceService->getTermlyPerVisitCost();
                    $sessionCost = $sessionCost > 0 ? $sessionCost : 120;
                    $termlyFeeCovered = true;
                } else {
                    // Student without termly fee - use per-visit cost (default: 150)
                    $sessionCost = $this->attendanceService->getPerVisitCost();
                    $sessionCost = $sessionCost > 0 ? $sessionCost : 150;
                    $termlyFeeCovered = false;
                }
                
                // Update attendance record
                $attendance->update([
                    'session_cost' => $sessionCost,
                    'termly_fee_covered' => $termlyFeeCovered,
                ]);
                
                $fixed++;
                
                $this->line("Fixed attendance #{$attendance->id} - Student: {$student->admission_number}, Cost: Ksh {$sessionCost}");
                
            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed to fix attendance #{$attendance->id}: {$e->getMessage()}");
                Log::error('Failed to fix swimming attendance session cost', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $this->info("\nFixed {$fixed} attendance record(s).");
        if ($failed > 0) {
            $this->warn("Failed to fix {$failed} record(s).");
        }
        
        return 0;
    }
}
