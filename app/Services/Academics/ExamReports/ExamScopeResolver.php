<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamSession;
use Illuminate\Support\Collection;

final class ExamScopeResolver
{
    public function __construct(
        private readonly TermScopeResolver $terms = new TermScopeResolver(),
    ) {}

    /**
     * Resolve the exam sitting for “by exam type” flows.
     */
    public function findExamSession(
        int $examTypeId,
        int $academicYearId,
        int $termId,
        int $classroomId,
        ?int $streamId
    ): ?ExamSession {
        $session = $this->resolveExamSession($examTypeId, $academicYearId, $termId, $classroomId, $streamId);

        if ($session || ! $streamId) {
            return $session;
        }

        // Class-wide sittings apply to every stream; learners are filtered in the report builder.
        return $this->resolveExamSession($examTypeId, $academicYearId, $termId, $classroomId, null);
    }

    private function resolveExamSession(
        int $examTypeId,
        int $academicYearId,
        int $termId,
        int $classroomId,
        ?int $streamId
    ): ?ExamSession {
        $direct = ExamSession::query()
            ->forScope($examTypeId, $academicYearId, $termId, $classroomId, $streamId)
            ->first();

        if ($direct) {
            return $direct;
        }

        $termIds = $this->terms->termIdsForScope($termId, $academicYearId, $examTypeId, $classroomId, $streamId);

        $session = ExamSession::query()
            ->where('exam_type_id', $examTypeId)
            ->where('academic_year_id', $academicYearId)
            ->where('classroom_id', $classroomId)
            ->whereIn('term_id', $termIds)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId), fn ($q) => $q->whereNull('stream_id'))
            ->orderByDesc('id')
            ->first();

        if ($session) {
            return $session;
        }

        $examSessionId = Exam::query()
            ->whereNotNull('subject_id')
            ->whereNotNull('exam_session_id')
            ->where('academic_year_id', $academicYearId)
            ->where('classroom_id', $classroomId)
            ->whereIn('term_id', $termIds)
            ->where(function ($q) use ($examTypeId) {
                $q->where('exam_type_id', $examTypeId)
                    ->orWhereHas('examSession', fn ($s) => $s->where('exam_type_id', $examTypeId));
            })
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId), fn ($q) => $q->whereNull('stream_id'))
            ->orderByDesc('id')
            ->value('exam_session_id');

        return $examSessionId ? ExamSession::query()->find($examSessionId) : null;
    }

    /**
     * Sort order for in-term exam sittings (Opener → Mid Term → End Term).
     */
    public static function examTypeSortOrder(?string $name, ?string $code = null): int
    {
        $hay = strtolower(trim(($name ?? '').' '.($code ?? '')));

        if ($hay === '' || $hay === '0') {
            return 0;
        }
        if (str_contains($hay, 'open') || preg_match('/\bopn\b/', $hay)) {
            return 1;
        }
        if (str_contains($hay, 'mid') || preg_match('/\bmid\b/', $hay)) {
            return 2;
        }
        if (str_contains($hay, 'end') || preg_match('/\bend\b/', $hay)) {
            return 3;
        }

        return 50;
    }

    /**
     * Previous exam sitting in the same term/class (e.g. Mid Term before End Term).
     */
    public function findPreviousExamSession(ExamSession $session, ?int $streamId = null): ?ExamSession
    {
        $session->loadMissing('examType');
        $currentOrder = self::examTypeSortOrder($session->examType?->name, $session->examType?->code);

        if ($currentOrder <= 1) {
            return null;
        }

        $effectiveStreamId = $session->stream_id ?? $streamId;
        $candidates = $this->examSessionsForTermScope(
            (int) $session->academic_year_id,
            (int) $session->term_id,
            (int) $session->classroom_id,
            $effectiveStreamId
        );

        if ($candidates->isEmpty() && $effectiveStreamId) {
            $candidates = $this->examSessionsForTermScope(
                (int) $session->academic_year_id,
                (int) $session->term_id,
                (int) $session->classroom_id,
                null
            );
        }

        return $candidates
            ->filter(function (ExamSession $candidate) use ($session, $currentOrder) {
                if ((int) $candidate->id === (int) $session->id) {
                    return false;
                }
                $candidate->loadMissing('examType');
                $order = self::examTypeSortOrder($candidate->examType?->name, $candidate->examType?->code);

                return $order > 0 && $order < $currentOrder;
            })
            ->sortByDesc(fn (ExamSession $candidate) => self::examTypeSortOrder(
                $candidate->examType?->name,
                $candidate->examType?->code
            ))
            ->first();
    }

    /**
     * Latest exam sitting in a term for a class (e.g. End Term after Mid Term).
     */
    public function findLatestExamSessionInTerm(
        int $academicYearId,
        int $termId,
        int $classroomId,
        ?int $streamId = null
    ): ?ExamSession {
        $candidates = $this->examSessionsForTermScope($academicYearId, $termId, $classroomId, $streamId);

        if ($candidates->isEmpty() && $streamId) {
            $candidates = $this->examSessionsForTermScope($academicYearId, $termId, $classroomId, null);
        }

        return $candidates
            ->sortByDesc(fn (ExamSession $session) => self::examTypeSortOrder(
                $session->examType?->name,
                $session->examType?->code
            ))
            ->first();
    }

    /**
     * @return Collection<int, ExamSession>
     */
    private function examSessionsForTermScope(
        int $academicYearId,
        int $termId,
        int $classroomId,
        ?int $streamId
    ): Collection {
        $termIds = $this->terms->termIdsForScope($termId, $academicYearId, null, $classroomId, $streamId);

        return ExamSession::query()
            ->with('examType')
            ->where('academic_year_id', $academicYearId)
            ->where('classroom_id', $classroomId)
            ->whereIn('term_id', $termIds)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId), fn ($q) => $q->whereNull('stream_id'))
            ->get();
    }

    /**
     * Subject papers under a sitting.
     *
     * @return Collection<int, Exam>
     */
    public function papersForSession(ExamSession $session): Collection
    {
        return Exam::query()
            ->where('exam_session_id', $session->id)
            ->whereNotNull('subject_id')
            ->with('subject')
            ->orderBy('id')
            ->get();
    }

    /**
     * All subject papers for a class in a term (handles duplicate term rows).
     *
     * @return Collection<int, Exam>
     */
    public function papersForTerm(int $academicYearId, int $termId, int $classroomId, ?int $streamId = null): Collection
    {
        $termIds = $this->terms->termIdsForScope($termId, $academicYearId, null, $classroomId, $streamId);

        $base = Exam::query()
            ->where('academic_year_id', $academicYearId)
            ->whereIn('term_id', $termIds)
            ->where('classroom_id', $classroomId)
            ->whereNotNull('subject_id');

        if ($streamId) {
            $streamPapers = (clone $base)
                ->where('stream_id', $streamId)
                ->orderBy('starts_on')
                ->orderBy('created_at')
                ->get();

            if ($streamPapers->isNotEmpty()) {
                return $streamPapers;
            }
        }

        return $base
            ->whereNull('stream_id')
            ->orderBy('starts_on')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Paper exam ids for a single exam row (session papers or one subject paper).
     *
     * @return list<int>
     */
    public function paperExamIdsForExam(Exam $exam): array
    {
        if ($exam->subject_id) {
            return [(int) $exam->id];
        }

        if ($exam->exam_session_id) {
            return Exam::query()
                ->where('exam_session_id', $exam->exam_session_id)
                ->whereNotNull('subject_id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return [];
    }
}
