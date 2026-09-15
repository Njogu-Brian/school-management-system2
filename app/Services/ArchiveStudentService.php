<?php

namespace App\Services;

use App\Models\Student;
use App\Models\ArchiveAudit;
use App\Models\Invoice;
use App\Models\Term;
use App\Models\User;
use App\Services\FamilyArchiveService;
use App\Services\Finance\InvoiceReversalService;
use Illuminate\Support\Facades\DB;

class ArchiveStudentService
{
    /**
    * Archive a student and all per-student records (soft delete).
    * Does NOT delete shared family/parent data.
    * Later-term unpaid invoices are reversed (audit trail); paid/allocated invoices stay.
    */
    public function archive(
        Student $student,
        ?string $reason = null,
        ?int $actorId = null,
        ?string $notes = null,
        ?string $transferDate = null
    ): array
    {
        if ($student->archive) {
            return ['skipped' => true, 'message' => 'Student already archived'];
        }

        $activeSiblings = Student::where('family_id', $student->family_id)
            ->where('id', '!=', $student->id)
            ->where('archive', 0)
            ->count();

        $counts = [
            'attendance' => 0,
            'homework_diary' => 0,
            'exam_marks' => 0,
            'reversed_later_term_invoices' => 0,
        ];

        $departureDate = $transferDate ? \Carbon\Carbon::parse($transferDate)->toDateString() : now()->toDateString();
        $actor = $actorId ? User::find($actorId) : auth()->user();

        DB::transaction(function () use ($student, $reason, $actorId, $actor, $activeSiblings, &$counts, $notes, $departureDate) {
            $counts['reversed_later_term_invoices'] = $this->reverseLaterTermUnpaidInvoices(
                $student,
                $departureDate,
                $actor instanceof User ? $actor : null
            );

            $counts['attendance'] = \App\Models\Attendance::where('student_id', $student->id)->delete();

            $counts['homework_diary'] = \App\Models\Academics\HomeworkDiary::where('student_id', $student->id)->delete();

            if (class_exists(\App\Models\Academics\ExamMark::class)) {
                $counts['exam_marks'] = \App\Models\Academics\ExamMark::where('student_id', $student->id)->delete();
            }

            $student->archive = 1;
            $student->archived_at = now();
            $student->archived_reason = $reason;
            $student->archived_notes = $notes;
            $student->archived_by = $actorId;
            $student->transfer_date = $departureDate;
            $student->save();

            \App\Models\FamilyUpdateLink::where('student_id', $student->id)
                ->whereNull('family_id')
                ->update(['is_active' => false]);

            ArchiveAudit::create([
                'student_id' => $student->id,
                'actor_id' => $actorId,
                'action' => 'archive',
                'reason' => $reason,
                'counts' => array_merge($counts, ['active_siblings' => $activeSiblings, 'transfer_date' => $departureDate]),
            ]);

            app(FamilyArchiveService::class)->onStudentArchivedOrAlumni($student);
        });

        return ['skipped' => false, 'counts' => $counts];
    }

    /**
     * Reverse invoices for terms that start after the departure term, only when no payment is allocated.
     */
    protected function reverseLaterTermUnpaidInvoices(Student $student, string $departureDate, ?User $actor): int
    {
        $cutoff = $this->laterTermCutoff($departureDate);
        if (! $cutoff) {
            return 0;
        }

        $laterTermIds = Term::query()
            ->whereDate('opening_date', '>', $cutoff)
            ->pluck('id');

        if ($laterTermIds->isEmpty()) {
            return 0;
        }

        $invoices = Invoice::query()
            ->where('student_id', $student->id)
            ->whereIn('term_id', $laterTermIds)
            ->whereNull('reversed_at')
            ->with('items.allocations')
            ->get();

        $reversal = app(InvoiceReversalService::class);
        $count = 0;
        foreach ($invoices as $invoice) {
            $hasPayments = $invoice->items->some(function ($item) {
                return $item->allocations->isNotEmpty();
            });
            if ($hasPayments) {
                continue;
            }
            $reversal->reverse(
                $invoice,
                'Student archived: departure date falls in an earlier term (unpaid later-term invoice reversed).',
                $actor
            );
            $count++;
        }

        return $count;
    }

    protected function laterTermCutoff(string $departureDate): ?string
    {
        $term = Term::query()
            ->whereDate('opening_date', '<=', $departureDate)
            ->whereDate('closing_date', '>=', $departureDate)
            ->orderByDesc('opening_date')
            ->first();

        if ($term?->closing_date) {
            return \Carbon\Carbon::parse($term->closing_date)->toDateString();
        }

        return $departureDate;
    }
}
