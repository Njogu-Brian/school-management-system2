<?php

namespace App\Services;

use App\Models\Student;
use App\Models\ArchiveAudit;
use App\Services\FamilyArchiveService;
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
            'payment_allocations' => 0,
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

            // Restore exam marks if model supports soft deletes
            if (class_exists(\App\Models\Academics\ExamMark::class)) {
                $examMarkModel = new \App\Models\Academics\ExamMark;
                if (method_exists($examMarkModel, 'withTrashed')) {
                    $counts['exam_marks'] = \App\Models\Academics\ExamMark::withTrashed()
                        ->where('student_id', $student->id)
                        ->restore();
                }
            }

            // Restore invoices and related
            $invoices = \App\Models\Invoice::withTrashed()->where('student_id', $student->id)->get();
            foreach ($invoices as $invoice) {
                // Items
                $itemRelation = $invoice->items();
                if (method_exists($itemRelation->getRelated(), 'runSoftDelete')) {
                    $counts['invoice_items'] += $itemRelation->withTrashed()->restore();
                }
                // Credit notes
                $cnRelation = $invoice->creditNotes();
                if (method_exists($cnRelation->getRelated(), 'runSoftDelete')) {
                    $counts['credit_notes'] += $cnRelation->withTrashed()->restore();
                }
                // Debit notes
                $dnRelation = $invoice->debitNotes();
                if (method_exists($dnRelation->getRelated(), 'runSoftDelete')) {
                    $counts['debit_notes'] += $dnRelation->withTrashed()->restore();
                }
                $invoice->restore();
                $counts['invoices']++;
            }

            // Restore payments
            $payments = \App\Models\Payment::withTrashed()->where('student_id', $student->id)->get();
            foreach ($payments as $payment) {
                $allocRelation = $payment->allocations();
                if (method_exists($allocRelation->getRelated(), 'runSoftDelete')) {
                    $counts['payment_allocations'] += $allocRelation->withTrashed()->restore();
                }
                $payment->restore();
                $counts['payments']++;
            }

            // Reactivate student
            $student->archive = 0;
            $student->archived_at = null;
            $student->archived_reason = null;
            $student->archived_notes = null;
            $student->archived_by = null;
            $student->save();

            // Restore family if this student had one removed (archived_family_id set)
            app(FamilyArchiveService::class)->onStudentRestored($student->fresh());

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

