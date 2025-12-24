<?php

namespace App\Services;

use App\Models\Student;
use App\Models\ArchiveAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestoreStudentService
{
    /**
    * Restore an archived student and soft-deleted related records.
    */
    public function restore(Student $student, ?string $reason = null, ?int $actorId = null): array
    {
        if (!$student->archive) {
            return ['skipped' => true, 'message' => 'Student is already active'];
        }

        // Guard: ensure admission number not in use by another active student
        $conflict = Student::where('admission_number', $student->admission_number)
            ->where('id', '!=', $student->id)
            ->where('archive', 0)
            ->exists();
        if ($conflict) {
            throw new \RuntimeException('Admission number conflict on restore.');
        }

        $counts = [
            'attendance' => 0,
            'homework_diary' => 0,
            'invoices' => 0,
            'invoice_items' => 0,
            'payments' => 0,
            'credit_notes' => 0,
            'debit_notes' => 0,
            'exam_marks' => 0,
        ];

        DB::transaction(function () use ($student, $reason, $actorId, &$counts) {
            // Restore attendance
            $counts['attendance'] = \App\Models\Attendance::withTrashed()
                ->where('student_id', $student->id)
                ->restore();

            // Restore homework diary
            $counts['homework_diary'] = \App\Models\Academics\HomeworkDiary::withTrashed()
                ->where('student_id', $student->id)
                ->restore();

            // Restore exam marks if model exists
            if (class_exists(\App\Models\Academics\ExamMark::class)) {
                $counts['exam_marks'] = \App\Models\Academics\ExamMark::withTrashed()
                    ->where('student_id', $student->id)
                    ->restore();
            }

            // Restore invoices and related
            $invoices = \App\Models\Invoice::withTrashed()->where('student_id', $student->id)->get();
            foreach ($invoices as $invoice) {
                $counts['invoice_items'] += $invoice->items()->withTrashed()->restore();
                $counts['credit_notes'] += $invoice->creditNotes()->withTrashed()->restore();
                $counts['debit_notes'] += $invoice->debitNotes()->withTrashed()->restore();
                $invoice->restore();
                $counts['invoices']++;
            }

            // Restore payments
            $payments = \App\Models\Payment::withTrashed()->where('student_id', $student->id)->get();
            foreach ($payments as $payment) {
                $payment->allocations()->withTrashed()->restore();
                $payment->restore();
                $counts['payments']++;
            }

            // Reactivate student
            $student->archive = 0;
            $student->archived_at = null;
            $student->save();

            ArchiveAudit::create([
                'student_id' => $student->id,
                'actor_id' => $actorId,
                'action' => 'restore',
                'reason' => $reason,
                'counts' => $counts,
            ]);
        });

        return ['skipped' => false, 'counts' => $counts];
    }
}

