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
                // Student has termly fee - automatically debit wallet with termly per-visit cost (default: 120)
                $termlyPerVisitCost = $this->getTermlyPerVisitCost();
                // Default to 120 if not set, to ensure all attendance is charged
                $sessionCost = $termlyPerVisitCost > 0 ? $termlyPerVisitCost : 120;
                
                // Create attendance record
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
                
                // Automatically debit wallet (allows negative balance to track unpaid amounts)
                try {
                    $this->walletService->debitForAttendance($student, $sessionCost, $attendance->id);
                    $attendance->update(['payment_status' => SwimmingAttendance::STATUS_PAID]);
                    $paymentStatus = SwimmingAttendance::STATUS_PAID;
                    $termlyFeeCovered = true;
                } catch (\Exception $e) {
                    Log::warning('Failed to automatically debit wallet for termly fee attendance', [
                        'attendance_id' => $attendance->id,
                        'student_id' => $student->id,
                        'session_cost' => $sessionCost,
                        'error' => $e->getMessage(),
                    ]);
                    // Attendance remains unpaid if debit fails
                    $paymentStatus = SwimmingAttendance::STATUS_UNPAID;
                }
            } else {
                // Step 2: For students WITHOUT optional fees - create invoice item for daily rate (default: 150)
                // Default to 150 if not set, to ensure all attendance is charged
                $sessionCost = $perVisitCost > 0 ? $perVisitCost : 150;
                
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
                        'amount' => $sessionCost,
                        'original_amount' => $sessionCost,
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
                }
                
                // Mark attendance as unpaid (will be paid when invoice is paid)
                $paymentStatus = SwimmingAttendance::STATUS_UNPAID;
                
                // Create attendance record
                $attendance = SwimmingAttendance::create([
                    'student_id' => $student->id,
                    'classroom_id' => $classroom->id,
                    'attendance_date' => $date->toDateString(),
                    'payment_status' => $paymentStatus,
                    'session_cost' => $sessionCost,
                    'termly_fee_covered' => false,
                    'notes' => $notes,
                    'marked_by' => $markedBy?->id ?? auth()->id(),
                    'marked_at' => now(),
                ]);
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
        // Find all unpaid attendance - process all of them
        $query = SwimmingAttendance::where('payment_status', SwimmingAttendance::STATUS_UNPAID)
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
                    $errors[] = "Attendance #{$attendance->id}: Student not found";
                    continue;
                }
                
                // Determine session cost based on whether student has termly fee
                $hasTermlyFee = $this->hasActiveTermlyFee($student);
                $sessionCost = 0;
                
                if ($hasTermlyFee) {
                    // Student has termly fee - debit 120 (termly per-visit cost)
                    $sessionCost = $this->getTermlyPerVisitCost();
                } else {
                    // Student has no termly fee - debit 150 (per-visit cost)
                    $sessionCost = $this->getPerVisitCost();
                }
                
                if ($sessionCost <= 0) {
                    $failed++;
                    $errors[] = "Attendance #{$attendance->id}: Session cost not set (termly: {$this->getTermlyPerVisitCost()}, regular: {$this->getPerVisitCost()})";
                    continue;
                }
                
                // Always debit wallet - allow negative balances to track unpaid amounts
                try {
                    // Update attendance record with correct session cost if different
                    if ($attendance->session_cost != $sessionCost) {
                        $attendance->update(['session_cost' => $sessionCost]);
                    }
                    
                    // Debit wallet (even if balance goes negative - this tracks what parents owe)
                    $this->walletService->debitForAttendance($student, $sessionCost, $attendance->id);
                    $attendance->update(['payment_status' => SwimmingAttendance::STATUS_PAID]);
                    $processed++;
                    
                    Log::info('Debited wallet for unpaid attendance', [
                        'attendance_id' => $attendance->id,
                        'student_id' => $student->id,
                        'amount' => $sessionCost,
                        'has_termly_fee' => $hasTermlyFee,
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Attendance #{$attendance->id}: {$e->getMessage()}";
                    Log::error('Failed to debit wallet for attendance in bulk retry', [
                        'attendance_id' => $attendance->id,
                        'student_id' => $student->id,
                        'session_cost' => $sessionCost,
                        'error' => $e->getMessage(),
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Attendance #{$attendance->id}: {$e->getMessage()}";
                Log::error('Exception in bulkRetryPayments', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'insufficient' => 0, // No longer tracking insufficient balance - always debit
            'errors' => $errors,
        ];
    }

    /**
     * Send payment reminders for unpaid swimming attendance
     */
    public function sendPaymentReminders(array $channels, ?string $date = null, ?int $classroomId = null): array
    {
        // Get attendance records that are unpaid OR where student has negative wallet balance
        $query = SwimmingAttendance::where('session_cost', '>', 0)
            ->with(['student.parent', 'classroom']);
        
        if ($date) {
            $query->whereDate('attendance_date', $date);
        }
        
        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }
        
        $allAttendance = $query->get();
        
        // Get wallet balances for students
        $studentIds = $allAttendance->pluck('student_id')->unique();
        $wallets = \App\Models\SwimmingWallet::whereIn('student_id', $studentIds)
            ->pluck('balance', 'student_id');
        
        // Filter to only unpaid attendance: payment_status = unpaid OR wallet balance < 0
        $unpaidAttendance = $allAttendance->filter(function($attendance) use ($wallets) {
            // If payment status is unpaid, include it
            if ($attendance->payment_status === SwimmingAttendance::STATUS_UNPAID) {
                return true;
            }
            
            // If payment status is paid but wallet is negative, they still owe money - include it
            $walletBalance = $wallets->get($attendance->student_id, 0);
            if ($walletBalance < 0) {
                return true;
            }
            
            return false;
        });
        
        $sent = 0;
        $failed = 0;
        $groupedByParent = [];
        
        // Group attendance by student/parent to avoid duplicate messages
        foreach ($unpaidAttendance as $attendance) {
            $student = $attendance->student;
            if (!$student || !$student->parent) {
                $failed++;
                continue;
            }
            
            $parentId = $student->parent->id;
            if (!isset($groupedByParent[$parentId])) {
                $groupedByParent[$parentId] = [
                    'parent' => $student->parent,
                    'student' => $student,
                    'attendances' => [],
                    'total_amount' => 0,
                ];
            }
            
            $groupedByParent[$parentId]['attendances'][] = $attendance;
            $groupedByParent[$parentId]['total_amount'] += $attendance->session_cost ?? 0;
        }
        
        $commService = app(\App\Services\CommunicationService::class);
        
        foreach ($groupedByParent as $group) {
            try {
                $parent = $group['parent'];
                $student = $group['student'];
                $attendances = $group['attendances'];
                $totalAmount = $group['total_amount'];
                
                $studentName = $student->first_name . ' ' . $student->last_name;
                $sessionCount = count($attendances);
                $amountFormatted = number_format($totalAmount, 2);
                
                // Get parent contact info
                $parentPhone = $parent->primary_contact_phone ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null;
                $parentEmail = $parent->primary_contact_email ?? $parent->father_email ?? $parent->mother_email ?? $parent->guardian_email ?? null;
                
                // Build attendance dates list
                $dates = $attendances->map(function($att) {
                    return $att->attendance_date->format('d M Y');
                })->unique()->sort()->implode(', ');
                
                // SMS message
                if (in_array('sms', $channels) && $parentPhone) {
                    try {
                        $smsMessage = "Dear Parent,\n\n";
                        $smsMessage .= "Your child {$studentName} ({$student->admission_number}) has {$sessionCount} unpaid swimming session(s).\n\n";
                        $smsMessage .= "Total Amount: KES {$amountFormatted}\n";
                        $smsMessage .= "Dates: {$dates}\n\n";
                        $smsMessage .= "Please make payment to credit your child's swimming wallet. Thank you.\n\n";
                        $smsMessage .= "Royal Kings School";
                        
                        $commService->sendSMS('parent', $parent->id, $parentPhone, $smsMessage, 'Swimming Payment Reminder', 'RKS_FINANCE');
                        $sent++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error('Failed to send swimming payment reminder SMS', [
                            'parent_id' => $parent->id,
                            'student_id' => $student->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // Email message
                if (in_array('email', $channels) && $parentEmail) {
                    try {
                        $emailSubject = "Swimming Payment Reminder - {$studentName}";
                        $emailContent = "<p>Dear Parent,</p>";
                        $emailContent .= "<p>Your child <strong>{$studentName}</strong> (Admission: {$student->admission_number}) has <strong>{$sessionCount}</strong> unpaid swimming session(s).</p>";
                        $emailContent .= "<p><strong>Total Amount Due:</strong> KES {$amountFormatted}</p>";
                        $emailContent .= "<p><strong>Session Dates:</strong> {$dates}</p>";
                        $emailContent .= "<p>Please make payment to credit your child's swimming wallet. You can make payments through:</p>";
                        $emailContent .= "<ul>";
                        $emailContent .= "<li>Bank transfer/deposit (mark transaction as swimming)</li>";
                        $emailContent .= "<li>M-PESA payment (mark as swimming)</li>";
                        $emailContent .= "<li>Direct payment at the finance office</li>";
                        $emailContent .= "</ul>";
                        $emailContent .= "<p>Thank you for your continued support.</p>";
                        $emailContent .= "<p>Royal Kings School</p>";
                        
                        $commService->sendEmail('parent', $parent->id, $parentEmail, $emailSubject, $emailContent);
                        $sent++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error('Failed to send swimming payment reminder email', [
                            'parent_id' => $parent->id,
                            'student_id' => $student->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                if (!in_array('sms', $channels) && !in_array('email', $channels)) {
                    $failed++;
                } elseif (!$parentPhone && !$parentEmail) {
                    $failed++;
                }
                
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to send swimming payment reminder', [
                    'parent_id' => $group['parent']->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return [
            'sent' => $sent,
            'failed' => $failed,
        ];
    }
}
