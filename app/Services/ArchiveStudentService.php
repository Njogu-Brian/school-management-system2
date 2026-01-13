<?php

namespace App\Services;

use App\Models\Student;
use App\Models\ArchiveAudit;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class ArchiveStudentService
{
    /**
    * Archive a student and all per-student records (soft delete).
    * Does NOT delete shared family/parent data.
    */
    public function archive(Student $student, ?string $reason = null, ?int $actorId = null, ?string $notes = null): array
    {
        if ($student->archive) {
            return ['skipped' => true, 'message' => 'Student already archived'];
        }

        // Guard: active siblings keep shared records
        $activeSiblings = Student::where('family_id', $student->family_id)
            ->where('id', '!=', $student->id)
            ->where('archive', 0)
            ->count();

        $counts = [
            'attendance' => 0,
            'homework_diary' => 0,
            'exam_marks' => 0,
            'unpaid_current_term_invoices' => 0,
        ];

        DB::transaction(function () use ($student, $reason, $actorId, $activeSiblings, &$counts, $notes) {
            // Delete unpaid invoices for the current term
            $currentTermId = get_current_term_id();
            if ($currentTermId) {
                // Find invoices for the current term that have no payments or allocations
                $invoices = Invoice::where('student_id', $student->id)
                    ->where('term_id', $currentTermId)
                    ->with('items.allocations')
                    ->get();
                
                foreach ($invoices as $invoice) {
                    // Check if invoice has any payment allocations through its items
                    $hasPayments = $invoice->items->some(function ($item) {
                        return $item->allocations->isNotEmpty();
                    });
                    
                    // Only delete if there are no payment allocations
                    if (!$hasPayments) {
                        // Delete invoice items first (cascade)
                        $invoice->items()->delete();
                        // Delete the invoice
                        $invoice->delete();
                        $counts['unpaid_current_term_invoices']++;
                    }
                }
            }

            // Attendance
            $counts['attendance'] = \App\Models\Attendance::where('student_id', $student->id)->delete();

            // Homework diaries/submissions
            $counts['homework_diary'] = \App\Models\Academics\HomeworkDiary::where('student_id', $student->id)->delete();

            // Exam marks (if model/table exists)
            if (class_exists(\App\Models\Academics\ExamMark::class)) {
                $counts['exam_marks'] = \App\Models\Academics\ExamMark::where('student_id', $student->id)->delete();
            }

            // Mark student as archived (keep row for restoration)
            $student->archive = 1;
            $student->archived_at = now();
            $student->archived_reason = $reason;
            $student->archived_notes = $notes;
            $student->archived_by = $actorId;
            $student->save();

            // Audit
            ArchiveAudit::create([
                'student_id' => $student->id,
                'actor_id' => $actorId,
                'action' => 'archive',
                'reason' => $reason,
                'counts' => array_merge($counts, ['active_siblings' => $activeSiblings]),
            ]);
        });

        return ['skipped' => false, 'counts' => $counts];
    }
}

