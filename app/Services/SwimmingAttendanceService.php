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
                // Step 2: For students WITHOUT optional fees - charge daily rate to wallet only
                // Daily attendance charges should NOT appear in invoices - only tracked in swimming wallet
                // Only termly swimming fees (from optional fees) should appear in invoices
                // Default to 150 if not set, to ensure all attendance is charged
                $sessionCost = $perVisitCost > 0 ? $perVisitCost : 150;
                
                // Create attendance record first
                $attendance = SwimmingAttendance::create([
                    'student_id' => $student->id,
                    'classroom_id' => $classroom->id,
                    'attendance_date' => $date->toDateString(),
                    'payment_status' => SwimmingAttendance::STATUS_UNPAID, // Will update after debit
                    'session_cost' => $sessionCost,
                    'termly_fee_covered' => false,
                    'notes' => $notes,
                    'marked_by' => $markedBy?->id ?? auth()->id(),
                    'marked_at' => now(),
                ]);
                
                // Debit wallet for daily attendance (allows negative balance to track unpaid amounts)
                // This will be tracked in swimming wallet/ledger, not in invoices
                try {
                    $this->walletService->debitForAttendance($student, $sessionCost, $attendance->id, "Daily swimming attendance - {$date->format('Y-m-d')}");
                    $attendance->update(['payment_status' => SwimmingAttendance::STATUS_PAID]);
                    $paymentStatus = SwimmingAttendance::STATUS_PAID;
                } catch (\Exception $e) {
                    Log::warning('Failed to debit wallet for daily swimming attendance', [
                        'attendance_id' => $attendance->id,
                        'student_id' => $student->id,
                        'session_cost' => $sessionCost,
                        'error' => $e->getMessage(),
                    ]);
                    // Attendance remains unpaid if debit fails
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
     * Unmark attendance for a student (reverse/delete attendance and refund wallet)
     */
    public function unmarkAttendance(
        SwimmingAttendance $attendance,
        ?User $unmarkedBy = null
    ): bool {
        return DB::transaction(function () use ($attendance, $unmarkedBy) {
            $student = $attendance->student;
            $sessionCost = $attendance->session_cost ?? 0;
            
            // If the student was charged (payment_status is paid), reverse the wallet debit
            if ($attendance->payment_status === SwimmingAttendance::STATUS_PAID && $sessionCost > 0) {
                try {
                    $this->walletService->reverseAttendanceDebit(
                        $student, 
                        $attendance->id, 
                        $sessionCost,
                        "Attendance reversal for {$attendance->attendance_date->format('Y-m-d')}"
                    );
                    
                    Log::info('Reversed swimming attendance charge', [
                        'attendance_id' => $attendance->id,
                        'student_id' => $student->id,
                        'amount_refunded' => $sessionCost,
                        'unmarked_by' => $unmarkedBy?->id ?? auth()->id(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to reverse swimming attendance charge', [
                        'attendance_id' => $attendance->id,
                        'student_id' => $student->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue to delete attendance even if reversal fails
                }
            }
            
            // Note: Daily attendance charges are no longer added to invoices
            // They are only tracked in swimming wallet/ledger
            // Only termly swimming fees (from optional fees) appear in invoices
            
            // Delete the attendance record
            $attendance->delete();
            
            return true;
        });
    }

    /**
     * Mark bulk attendance for a class (original method - only adds new)
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
     * Sync bulk attendance for a class - handles both marking new and unmarking removed students
     * This is the proper update method that should be used when updating attendance for a day
     */
    public function syncBulkAttendance(
        Classroom $classroom,
        Carbon $date,
        array $markedStudentIds,
        ?User $markedBy = null
    ): array {
        $results = [
            'marked' => [],
            'unmarked' => [],
            'already_marked' => [],
            'failed' => [],
        ];
        
        // Ensure all IDs are integers for proper comparison
        $markedStudentIds = array_map('intval', $markedStudentIds);
        
        Log::info('Syncing swimming attendance', [
            'classroom_id' => $classroom->id,
            'date' => $date->toDateString(),
            'marked_student_ids' => $markedStudentIds,
        ]);
        
        // Get all students in the classroom
        $allClassroomStudentIds = Student::where('classroom_id', $classroom->id)
            ->where('archive', 0)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();
        
        // Get existing attendance records for this date
        $existingAttendance = SwimmingAttendance::where('classroom_id', $classroom->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->get()
            ->keyBy('student_id');
        
        $existingStudentIds = $existingAttendance->keys()->map(fn($id) => (int) $id)->toArray();
        
        Log::info('Swimming attendance sync state', [
            'all_classroom_students' => count($allClassroomStudentIds),
            'existing_attendance_count' => count($existingStudentIds),
            'existing_student_ids' => $existingStudentIds,
            'requested_student_ids' => $markedStudentIds,
        ]);
        
        // Determine which students to mark (new) and which to unmark (removed)
        $studentsToMark = array_values(array_diff($markedStudentIds, $existingStudentIds));
        $studentsToUnmark = array_values(array_diff($existingStudentIds, $markedStudentIds));
        $studentsAlreadyMarked = array_values(array_intersect($markedStudentIds, $existingStudentIds));
        
        Log::info('Swimming attendance sync plan', [
            'to_mark' => $studentsToMark,
            'to_unmark' => $studentsToUnmark,
            'already_marked' => $studentsAlreadyMarked,
        ]);
        
        // Mark new students
        foreach ($studentsToMark as $studentId) {
            try {
                $student = Student::find($studentId);
                if (!$student) {
                    $results['failed'][] = [
                        'student_id' => $studentId,
                        'action' => 'mark',
                        'error' => 'Student not found',
                    ];
                    continue;
                }
                
                $attendance = $this->markAttendance($student, $classroom, $date, null, $markedBy);
                $results['marked'][] = [
                    'student_id' => $studentId,
                    'attendance_id' => $attendance->id,
                    'student_name' => $student->full_name,
                ];
                
                Log::info('Marked swimming attendance', [
                    'student_id' => $studentId,
                    'attendance_id' => $attendance->id,
                    'date' => $date->toDateString(),
                ]);
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'student_id' => $studentId,
                    'action' => 'mark',
                    'error' => $e->getMessage(),
                ];
                
                Log::error('Failed to mark swimming attendance', [
                    'student_id' => $studentId,
                    'date' => $date->toDateString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Unmark removed students (refund wallet)
        foreach ($studentsToUnmark as $studentId) {
            try {
                $attendance = $existingAttendance->get($studentId);
                if (!$attendance) {
                    Log::warning('Attendance record not found for unmark', [
                        'student_id' => $studentId,
                        'date' => $date->toDateString(),
                    ]);
                    continue;
                }
                
                $student = $attendance->student;
                $studentName = $student ? $student->full_name : "Student #{$studentId}";
                $refundAmount = $attendance->session_cost ?? 0;
                
                $this->unmarkAttendance($attendance, $markedBy);
                $results['unmarked'][] = [
                    'student_id' => $studentId,
                    'student_name' => $studentName,
                    'refunded_amount' => $refundAmount,
                ];
                
                Log::info('Unmarked swimming attendance', [
                    'student_id' => $studentId,
                    'date' => $date->toDateString(),
                    'refunded_amount' => $refundAmount,
                ]);
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'student_id' => $studentId,
                    'action' => 'unmark',
                    'error' => $e->getMessage(),
                ];
                
                Log::error('Failed to unmark swimming attendance', [
                    'student_id' => $studentId,
                    'date' => $date->toDateString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Track already marked students (no action needed)
        foreach ($studentsAlreadyMarked as $studentId) {
            $attendance = $existingAttendance->get($studentId);
            $student = $attendance?->student;
            $results['already_marked'][] = [
                'student_id' => $studentId,
                'student_name' => $student ? $student->full_name : "Student #{$studentId}",
            ];
        }
        
        Log::info('Swimming attendance sync completed', [
            'marked_count' => count($results['marked']),
            'unmarked_count' => count($results['unmarked']),
            'already_marked_count' => count($results['already_marked']),
            'failed_count' => count($results['failed']),
        ]);
        
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
                $parentWhatsApp = $parent->father_whatsapp ?? $parent->mother_whatsapp ?? $parent->guardian_whatsapp ?? $parentPhone ?? null;
                
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
                
                // WhatsApp message
                if (in_array('whatsapp', $channels) && $parentWhatsApp) {
                    try {
                        $whatsappMessage = "Dear Parent,\n\n";
                        $whatsappMessage .= "Your child {$studentName} ({$student->admission_number}) has {$sessionCount} unpaid swimming session(s).\n\n";
                        $whatsappMessage .= "Total Amount: KES {$amountFormatted}\n";
                        $whatsappMessage .= "Dates: {$dates}\n\n";
                        $whatsappMessage .= "Please make payment to credit your child's swimming wallet. You can make payments through:\n";
                        $whatsappMessage .= "• Bank transfer/deposit (mark transaction as swimming)\n";
                        $whatsappMessage .= "• M-PESA payment (mark as swimming)\n";
                        $whatsappMessage .= "• Direct payment at the finance office\n\n";
                        $whatsappMessage .= "Thank you for your continued support.\n\n";
                        $whatsappMessage .= "Royal Kings School";
                        
                        $commService->sendWhatsApp('parent', $parent->id, $parentWhatsApp, $whatsappMessage, 'Swimming Payment Reminder');
                        $sent++;
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error('Failed to send swimming payment reminder WhatsApp', [
                            'parent_id' => $parent->id,
                            'student_id' => $student->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                if (!in_array('sms', $channels) && !in_array('email', $channels) && !in_array('whatsapp', $channels)) {
                    $failed++;
                } elseif (!$parentPhone && !$parentEmail && !$parentWhatsApp) {
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
