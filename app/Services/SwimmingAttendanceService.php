<?php

namespace App\Services;

use App\Models\{
    SwimmingAttendance, SwimmingWallet, Student, OptionalFee, User
};
use App\Models\Academics\Classroom;
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
     * Get per-visit cost for termly fee students from settings
     */
    public function getTermlyPerVisitCost(): float
    {
        return (float) setting('swimming_termly_per_visit_cost', 0);
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
            $sessionCost = $perVisitCost;
            
            if ($termlyFee) {
                // Student has termly fee - deduct different amount from wallet
                $termlyPerVisitCost = $this->getTermlyPerVisitCost();
                $sessionCost = $termlyPerVisitCost > 0 ? $termlyPerVisitCost : 0;
                
                if ($sessionCost > 0 && $this->walletService->hasSufficientBalance($student, $sessionCost)) {
                    // Debit wallet with termly per-visit cost
                    $attendance = SwimmingAttendance::create([
                        'student_id' => $student->id,
                        'classroom_id' => $classroom->id,
                        'attendance_date' => $date->toDateString(),
                        'payment_status' => SwimmingAttendance::STATUS_UNPAID, // Will update after debit
                        'session_cost' => $sessionCost,
                        'termly_fee_covered' => true,
                        'notes' => $notes,
                        'marked_by' => $markedBy?->id ?? auth()->id(),
                        'marked_at' => now(),
                    ]);
                    
                    try {
                        $this->walletService->debitForAttendance($student, $sessionCost, $attendance->id);
                        $attendance->update(['payment_status' => SwimmingAttendance::STATUS_PAID]);
                        $paymentStatus = SwimmingAttendance::STATUS_PAID;
                        $termlyFeeCovered = true;
                    } catch (\Exception $e) {
                        Log::warning('Failed to debit wallet for termly fee attendance', [
                            'attendance_id' => $attendance->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Attendance remains unpaid
                    }
                } else {
                    // If no termly per-visit cost set or insufficient balance, mark as paid (covered by termly fee)
                    $paymentStatus = SwimmingAttendance::STATUS_PAID;
                    $termlyFeeCovered = true;
                    $sessionCost = 0; // No deduction if termly fee covers it
                }
            } else {
                // Step 2: For students WITHOUT optional fees - create invoice item for daily rate
                // Students without swimming optional fees should be invoiced (not debited from wallet)
                if ($perVisitCost > 0) {
                    // Find or create swimming votehead for invoice
                    $swimmingVotehead = \App\Models\Votehead::where(function($q) {
                        $q->where('name', 'like', '%swimming%')
                          ->orWhere('code', 'like', '%SWIM%');
                    })->where('is_mandatory', false)->first();
                    
                    if ($swimmingVotehead) {
                        // Create invoice item for daily swimming attendance
                        $year = (int) setting('current_year', date('Y'));
                        $term = (int) setting('current_term', 1);
                        
                        // Get or create invoice for current term
                        $invoice = \App\Models\Invoice::firstOrCreate([
                            'student_id' => $student->id,
                            'year' => $year,
                            'term' => $term,
                            'status' => 'active',
                        ], [
                            'issued_date' => now(),
                            'due_date' => now()->addDays(30),
                            'total' => 0,
                            'paid_amount' => 0,
                            'balance' => 0,
                        ]);
                        
                        // Create invoice item for this swimming session
                        \App\Models\InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'votehead_id' => $swimmingVotehead->id,
                            'amount' => $perVisitCost,
                            'original_amount' => $perVisitCost,
                            'discount_amount' => 0,
                            'status' => 'active',
                            'source' => 'swimming_attendance',
                            'effective_date' => $date->toDateString(),
                        ]);
                        
                        // Update invoice totals
                        $invoice->refresh();
                        $invoice->update([
                            'total' => $invoice->items()->sum('amount'),
                            'balance' => $invoice->total - $invoice->paid_amount,
                        ]);
                        
                        // Mark attendance as unpaid (will be paid when invoice is paid)
                        $paymentStatus = SwimmingAttendance::STATUS_UNPAID;
                    }
                    
                    // Create attendance record
                    $attendance = SwimmingAttendance::create([
                        'student_id' => $student->id,
                        'classroom_id' => $classroom->id,
                        'attendance_date' => $date->toDateString(),
                        'payment_status' => $paymentStatus,
                        'session_cost' => $perVisitCost,
                        'termly_fee_covered' => false,
                        'notes' => $notes,
                        'marked_by' => $markedBy?->id ?? auth()->id(),
                        'marked_at' => now(),
                    ]);
                } else {
                    // No cost set - mark as paid
                    $paymentStatus = SwimmingAttendance::STATUS_PAID;
                }
            }
            
            // Create attendance record if not already created
            if (!isset($attendance)) {
                $attendance = SwimmingAttendance::create([
                    'student_id' => $student->id,
                    'classroom_id' => $classroom->id,
                    'attendance_date' => $date->toDateString(),
                    'payment_status' => $paymentStatus,
                    'session_cost' => $sessionCost,
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

    /**
     * Bulk retry payment for unpaid attendance records
     * This processes all unpaid attendance for students with optional fees who now have wallet balance
     */
    public function bulkRetryPayments(?array $attendanceIds = null): array
    {
        $query = SwimmingAttendance::where('payment_status', SwimmingAttendance::STATUS_UNPAID)
            ->where('termly_fee_covered', true) // Only students with optional fees
            ->where('session_cost', '>', 0)
            ->with(['student']);
        
        if ($attendanceIds) {
            $query->whereIn('id', $attendanceIds);
        }
        
        $unpaidAttendance = $query->get();
        
        $processed = 0;
        $failed = 0;
        $insufficient = 0;
        $errors = [];
        
        foreach ($unpaidAttendance as $attendance) {
            try {
                $student = $attendance->student;
                if (!$student) {
                    $failed++;
                    continue;
                }
                
                $sessionCost = $attendance->session_cost ?? $this->getTermlyPerVisitCost();
                
                if ($sessionCost <= 0) {
                    $failed++;
                    continue;
                }
                
                // Check if wallet has sufficient balance
                if ($this->walletService->hasSufficientBalance($student, $sessionCost)) {
                    try {
                        $this->walletService->debitForAttendance($student, $sessionCost, $attendance->id);
                        $attendance->update(['payment_status' => SwimmingAttendance::STATUS_PAID]);
                        $processed++;
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = "Attendance #{$attendance->id}: {$e->getMessage()}";
                        Log::error('Failed to debit wallet for attendance in bulk retry', [
                            'attendance_id' => $attendance->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    $insufficient++;
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Attendance #{$attendance->id}: {$e->getMessage()}";
            }
        }
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'insufficient' => $insufficient,
            'errors' => $errors,
        ];
    }
}
