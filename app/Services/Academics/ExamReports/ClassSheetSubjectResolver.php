<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Exam;

class ClassSheetSubjectResolver
{
    public function __construct(
        private readonly TermScopeResolver $terms = new TermScopeResolver(),
    ) {}

    /**
     * Pick the best exam row for a subject paper in a class / year / term, optional stream.
     */
    public function resolveExam(int $subjectId, int $classroomId, int $academicYearId, int $termId, ?int $streamId): ?Exam
    {
        $termIds = $this->terms->termIdsForScope($termId, $academicYearId, null, $classroomId, $streamId);

        $candidates = Exam::query()
            ->with(['subject', 'examType'])
            ->where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('term_id', $termIds)
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
