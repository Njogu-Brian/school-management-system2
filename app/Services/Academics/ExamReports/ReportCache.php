<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Classroom;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamSession;
use Illuminate\Support\Facades\Cache;

class ReportCache
{
    public function rememberExamClassSheet(Exam $exam, Classroom $classroom, ?int $streamId, \Closure $build): array
    {
        $stamp = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereHas('student', function ($q) use ($classroom, $streamId) {
                $q->where('classroom_id', $classroom->id);
                if ($streamId) $q->where('stream_id', $streamId);
            })
            ->max('updated_at');

        $key = implode(':', [
            'exam_reports',
            'class_sheet',
            'exam',
            $exam->id,
            'class',
            $classroom->id,
            'stream',
            $streamId ?: 0,
            'schema',
            2,
            'v',
            $stamp ? strtotime((string) $stamp) : 0,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberExamSessionClassSheet(ExamSession $session, Classroom $classroom, ?int $streamId, \Closure $build): array
    {
        $paperIds = Exam::query()
            ->where('exam_session_id', $session->id)
            ->whereNotNull('subject_id')
            ->pluck('id');

        $stamp = $paperIds->isEmpty()
            ? null
            : ExamMark::query()
                ->whereIn('exam_id', $paperIds)
                ->whereHas('student', function ($q) use ($classroom, $streamId) {
                    $q->where('classroom_id', $classroom->id);
                    if ($streamId) {
                        $q->where('stream_id', $streamId);
                    }
                })
                ->max('updated_at');

        $key = implode(':', [
            'exam_reports',
            'class_sheet',
            'exam_session',
            $session->id,
            'class',
            $classroom->id,
            'stream',
            $streamId ?: 0,
            'schema',
            2,
            'v',
            $stamp ? strtotime((string) $stamp) : 0,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberTermClassSheet(int $academicYearId, int $termId, Classroom $classroom, ?int $streamId, \Closure $build): array
    {
        $stamp = ExamMark::query()
            ->whereHas('exam', function ($q) use ($academicYearId, $termId) {
                $q->where('academic_year_id', $academicYearId)->where('term_id', $termId);
            })
            ->whereHas('student', function ($q) use ($classroom, $streamId) {
                $q->where('classroom_id', $classroom->id);
                if ($streamId) $q->where('stream_id', $streamId);
            })
            ->max('updated_at');

        $key = implode(':', [
            'exam_reports',
            'class_sheet',
            'term',
            $academicYearId,
            $termId,
            'class',
            $classroom->id,
            'stream',
            $streamId ?: 0,
            'schema',
            2,
            'v',
            $stamp ? strtotime((string) $stamp) : 0,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberExamTeacherPerformance(Exam $exam, Classroom $classroom, ?int $streamId, ?int $subjectId, \Closure $build): array
    {
        $stamp = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereHas('student', function ($q) use ($classroom, $streamId) {
                $q->where('classroom_id', $classroom->id);
                if ($streamId) $q->where('stream_id', $streamId);
            })
            ->max('updated_at');

        $key = implode(':', [
            'exam_reports',
            'teacher_perf',
            $exam->id,
            $classroom->id,
            $streamId ?: 0,
            $subjectId ?: 0,
            'v',
            $stamp ? strtotime((string) $stamp) : 0,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberSchoolTeacherPerformance(Exam $exam, ?int $subjectId, \Closure $build): array
    {
        $stamp = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereNotNull('teacher_id')
            ->max('updated_at');

        $key = implode(':', [
            'exam_reports',
            'teacher_perf',
            'school',
            $exam->id,
            $subjectId ?: 0,
            'v',
            $stamp ? strtotime((string) $stamp) : 0,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberExamSubjectPerformance(Exam $exam, Classroom $classroom, ?int $streamId, \Closure $build): array
    {
        $stamp = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereHas('student', function ($q) use ($classroom, $streamId) {
                $q->where('classroom_id', $classroom->id);
                if ($streamId) $q->where('stream_id', $streamId);
            })
            ->max('updated_at');

        $key = implode(':', [
            'exam_reports',
            'subject_perf',
            $exam->id,
            $classroom->id,
            $streamId ?: 0,
            'v',
            $stamp ? strtotime((string) $stamp) : 0,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberExamStudentInsights(Exam $exam, Classroom $classroom, ?int $streamId, \Closure $build): array
    {
        $stamp = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereHas('student', function ($q) use ($classroom, $streamId) {
                $q->where('classroom_id', $classroom->id);
                if ($streamId) $q->where('stream_id', $streamId);
            })
            ->max('updated_at');

        $key = implode(':', [
            'exam_reports',
            'student_insights',
            $exam->id,
            $classroom->id,
            $streamId ?: 0,
            'v',
            $stamp ? strtotime((string) $stamp) : 0,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberTermStudentInsights(int $academicYearId, int $termId, Classroom $classroom, ?int $streamId, \Closure $build): array
    {
        $stamp = ExamMark::query()
            ->whereHas('exam', function ($q) use ($academicYearId, $termId) {
                $q->where('academic_year_id', $academicYearId)->where('term_id', $termId);
            })
            ->whereHas('student', function ($q) use ($classroom, $streamId) {
                $q->where('classroom_id', $classroom->id);
                if ($streamId) $q->where('stream_id', $streamId);
            })
            ->max('updated_at');

        $key = implode(':', [
            'exam_reports',
            'student_insights',
            'term',
            $academicYearId,
            $termId,
            'class',
            $classroom->id,
            'stream',
            $streamId ?: 0,
            'v',
            $stamp ? strtotime((string) $stamp) : 0,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberExamSessionSubjectPerformance(ExamSession $session, Classroom $classroom, ?int $streamId, \Closure $build): array
    {
        $paperIds = Exam::query()
            ->where('exam_session_id', $session->id)
            ->whereNotNull('subject_id')
            ->pluck('id');

        $stamp = $this->marksStampForPapers($paperIds, $classroom, $streamId);
        $key = implode(':', [
            'exam_reports', 'subject_perf', 'exam_session', $session->id, $classroom->id, $streamId ?: 0,
            'v', $stamp,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberExamSessionTeacherPerformance(ExamSession $session, Classroom $classroom, ?int $streamId, \Closure $build): array
    {
        $paperIds = Exam::query()
            ->where('exam_session_id', $session->id)
            ->whereNotNull('subject_id')
            ->pluck('id');

        $stamp = $this->marksStampForPapers($paperIds, $classroom, $streamId);
        $key = implode(':', [
            'exam_reports', 'teacher_perf', 'exam_session', $session->id, $classroom->id, $streamId ?: 0,
            'v', $stamp,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberTermSubjectPerformance(int $academicYearId, int $termId, Classroom $classroom, ?int $streamId, \Closure $build): array
    {
        $stamp = $this->marksStampForTerm($academicYearId, $termId, $classroom, $streamId);
        $key = implode(':', [
            'exam_reports', 'subject_perf', 'term', $academicYearId, $termId, $classroom->id, $streamId ?: 0,
            'v', $stamp,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    public function rememberTermTeacherPerformance(int $academicYearId, int $termId, Classroom $classroom, ?int $streamId, \Closure $build): array
    {
        $stamp = $this->marksStampForTerm($academicYearId, $termId, $classroom, $streamId);
        $key = implode(':', [
            'exam_reports', 'teacher_perf', 'term', $academicYearId, $termId, $classroom->id, $streamId ?: 0,
            'v', $stamp,
        ]);

        return Cache::remember($key, now()->addMinutes(10), fn () => $build());
    }

    private function marksStampForPapers($paperIds, Classroom $classroom, ?int $streamId): int
    {
        if ($paperIds->isEmpty()) {
            return 0;
        }

        $stamp = ExamMark::query()
            ->whereIn('exam_id', $paperIds)
            ->whereHas('student', function ($q) use ($classroom, $streamId) {
                $q->where('classroom_id', $classroom->id);
                if ($streamId) {
                    $q->where('stream_id', $streamId);
                }
            })
            ->max('updated_at');

        return $stamp ? strtotime((string) $stamp) : 0;
    }

    private function marksStampForTerm(int $academicYearId, int $termId, Classroom $classroom, ?int $streamId): int
    {
        $termIds = (new TermScopeResolver())->termIdsForScope($termId, $academicYearId, null, $classroom->id, $streamId);
        $stamp = ExamMark::query()
            ->whereHas('exam', function ($q) use ($academicYearId, $termIds, $classroom, $streamId) {
                $q->where('academic_year_id', $academicYearId)
                    ->whereIn('term_id', $termIds)
                    ->where('classroom_id', $classroom->id)
                    ->when($streamId, fn ($qq) => $qq->where('stream_id', $streamId), fn ($qq) => $qq->whereNull('stream_id'));
            })
            ->whereHas('student', function ($q) use ($classroom, $streamId) {
                $q->where('classroom_id', $classroom->id);
                if ($streamId) {
                    $q->where('stream_id', $streamId);
                }
            })
            ->max('updated_at');

        return $stamp ? strtotime((string) $stamp) : 0;
    }
}

