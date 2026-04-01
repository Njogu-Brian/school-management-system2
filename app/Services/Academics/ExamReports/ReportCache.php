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
}

