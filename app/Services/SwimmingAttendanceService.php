<?php

namespace App\Services;

use App\Models\{
    SwimmingAttendance, SwimmingWallet, Student, OptionalFee, Classroom, User
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Swimming Attendance Service
 * Handles attendance marking and payment validation
 */
class SwimmingAttendanceService
{
    protected $walletService;

    public function __construct(SwimmingWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get per-visit cost from settings
     */
    public function getPerVisitCost(): float
    {
        return (float) setting('swimming_per_visit_cost', 0);
    }

    /**
     * Check if student has active termly swimming optional fee
     */
    public function hasActiveTermlyFee(Student $student, ?int $year = null, ?int $term = null): ?OptionalFee
    {
        $year = $year ?? (int) setting('current_year', date('Y'));
        $term = $term ?? (int) setting('current_term', 1);
        
        // Find swimming votehead (by name or code)
        $swimmingVotehead = \App\Models\Votehead::where(function($q) {
            $q->where('name', 'like', '%swimming%')
              ->orWhere('code', 'like', '%SWIM%');
        })->where('is_mandatory', false)->first();
        
        if (!$swimmingVotehead) {
            return null;
        }
        
        return OptionalFee::where('student_id', $student->id)
            ->where('votehead_id', $swimmingVotehead->id)
            ->where('year', $year)
            ->where('term', $term)
            ->where('status', 'billed')
            ->first();
    }

    /**
     * Mark attendance for a student
     */
    public function markAttendance(
        Student $student,
        Classroom $classroom,
        Carbon $date,
        ?string $notes = null,
        ?User $markedBy = null
    ): SwimmingAttendance {
        return DB::transaction(function () use ($student, $classroom, $date, $notes, $markedBy) {
            // Check if attendance already exists
            $existing = SwimmingAttendance::where('student_id', $student->id)
                ->whereDate('attendance_date', $date->toDateString())
                ->first();
            
            if ($existing) {
                throw new \Exception("Attendance already marked for this student on {$date->format('Y-m-d')}");
            }
            
            $perVisitCost = $this->getPerVisitCost();
            $termlyFee = $this->hasActiveTermlyFee($student);
            
            // Step 1: Check termly fee
            $paymentStatus = SwimmingAttendance::STATUS_UNPAID;
            $termlyFeeCovered = false;
            
            if ($termlyFee) {
                $paymentStatus = SwimmingAttendance::STATUS_PAID;
                $termlyFeeCovered = true;
            } else {
                // Step 2: Check wallet balance
                if ($perVisitCost > 0 && $this->walletService->hasSufficientBalance($student, $perVisitCost)) {
                    // Debit wallet
                    $attendance = SwimmingAttendance::create([
                        'student_id' => $student->id,
                        'classroom_id' => $classroom->id,
                        'attendance_date' => $date->toDateString(),
                        'payment_status' => SwimmingAttendance::STATUS_UNPAID, // Will update after debit
                        'session_cost' => $perVisitCost,
                        'termly_fee_covered' => false,
                        'notes' => $notes,
                        'marked_by' => $markedBy?->id ?? auth()->id(),
                        'marked_at' => now(),
                    ]);
                    
                    try {
                        $this->walletService->debitForAttendance($student, $perVisitCost, $attendance->id);
                        $attendance->update(['payment_status' => SwimmingAttendance::STATUS_PAID]);
                        $paymentStatus = SwimmingAttendance::STATUS_PAID;
                    } catch (\Exception $e) {
                        Log::warning('Failed to debit wallet for attendance', [
                            'attendance_id' => $attendance->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Attendance remains unpaid
                    }
                } else {
                    // Insufficient balance - mark as unpaid
                    $paymentStatus = SwimmingAttendance::STATUS_UNPAID;
                }
            }
            
            // Create attendance record if not already created
            if (!isset($attendance)) {
                $attendance = SwimmingAttendance::create([
                    'student_id' => $student->id,
                    'classroom_id' => $classroom->id,
                    'attendance_date' => $date->toDateString(),
                    'payment_status' => $paymentStatus,
                    'session_cost' => $perVisitCost,
                    'termly_fee_covered' => $termlyFeeCovered,
                    'notes' => $notes,
                    'marked_by' => $markedBy?->id ?? auth()->id(),
                    'marked_at' => now(),
                ]);
            }
            
            return $attendance->fresh();
        });
    }

    /**
     * Mark bulk attendance for a class
     */
    public function markBulkAttendance(
        Classroom $classroom,
        Carbon $date,
        array $studentIds,
        ?User $markedBy = null
    ): array {
        $results = [
            'success' => [],
            'failed' => [],
        ];
        
        foreach ($studentIds as $studentId) {
            try {
                $student = Student::findOrFail($studentId);
                $attendance = $this->markAttendance($student, $classroom, $date, null, $markedBy);
                $results['success'][] = $attendance->id;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'student_id' => $studentId,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Retry payment for unpaid attendance
     */
    public function retryPayment(SwimmingAttendance $attendance): bool
    {
        if ($attendance->isPaid()) {
            return true;
        }
        
        $student = $attendance->student;
        $perVisitCost = $attendance->session_cost ?? $this->getPerVisitCost();
        
        if ($perVisitCost <= 0) {
            return false;
        }
        
        // Check wallet balance
        if ($this->walletService->hasSufficientBalance($student, $perVisitCost)) {
            try {
                $this->walletService->debitForAttendance($student, $perVisitCost, $attendance->id);
                $attendance->update(['payment_status' => SwimmingAttendance::STATUS_PAID]);
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to retry payment for attendance', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return false;
    }
}
