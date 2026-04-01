<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Exam;

class ClassSheetSubjectResolver
{
    /**
     * Pick the best exam row for a subject paper in a class / year / term, optional stream.
     */
    public function resolveExam(int $subjectId, int $classroomId, int $academicYearId, int $termId, ?int $streamId): ?Exam
    {
        $candidates = Exam::query()
            ->with(['subject', 'examType'])
            ->where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->whereNotNull('subject_id')
            ->orderByDesc('id')
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($streamId) {
            $match = $candidates->first(fn (Exam $e) => (int) ($e->stream_id ?? 0) === (int) $streamId);
            if ($match) {
                return $match;
            }
            $fallback = $candidates->first(fn (Exam $e) => $e->stream_id === null);

            return $fallback ?? $candidates->first();
        }

        return $candidates->first(fn (Exam $e) => $e->stream_id === null) ?? $candidates->first();
    }
}
