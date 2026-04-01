<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Subject;
use App\Models\Staff;
use Illuminate\Support\Collection;

class SchoolWideTeacherRankingService
{
    private function passMarkPercent(): float
    {
        $v = setting('exam_pass_mark_percent', 50);
        $f = is_numeric($v) ? (float) $v : 50.0;
        return max(0.0, min(100.0, $f));
    }

    /**
     * School-wide, by Exam:
     * - per_teacher: ranks teachers by mean of their subject means (within this exam)
     * - per_subject_teacher: ranks teachers within each subject
     */
    public function rankingsForExam(Exam $exam, ?int $subjectId = null): array
    {
        $query = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereNotNull('teacher_id');

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        $marks = $query->get(['teacher_id', 'subject_id', 'score_raw', 'score_moderated', 'grade_label'])
            ->map(function (ExamMark $m) {
                $m->marks_obtained = $m->score_moderated ?? $m->score_raw ?? null;
                return $m;
            })
            ->filter(fn (ExamMark $m) => $m->marks_obtained !== null)
            ->values();

        $teacherIds = $marks->pluck('teacher_id')->unique()->values();
        $teachers = Staff::query()
            ->whereIn('id', $teacherIds)
            ->get(['id', 'first_name', 'middle_name', 'last_name'])
            ->keyBy('id');

        $subjectIds = $marks->pluck('subject_id')->unique()->values();
        $subjects = Subject::query()
            ->whereIn('id', $subjectIds)
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        $maxMarks = (float) ($exam->max_marks ?? 100);
        $passThreshold = ($this->passMarkPercent() / 100.0) * $maxMarks;

        // Subject->Teacher rows
        $perSubjectTeacher = $marks
            ->groupBy(fn (ExamMark $m) => $m->subject_id . ':' . $m->teacher_id)
            ->map(function (Collection $rows) use ($teachers, $subjects, $passThreshold) {
                $first = $rows->first();
                $vals = $rows->pluck('marks_obtained')->map(fn ($v) => (float) $v)->values();
                $passRate = $vals->count()
                    ? round(($vals->filter(fn ($m) => $m >= $passThreshold)->count() / $vals->count()) * 100, 2)
                    : null;

                $t = $teachers->get($first->teacher_id);
                $teacherName = $t ? trim(($t->first_name ?? '') . ' ' . ($t->middle_name ?? '') . ' ' . ($t->last_name ?? '')) : null;
                $s = $subjects->get($first->subject_id);
                $subjectName = $s ? (($s->code ?? '') ? (($s->code ?? '') . ' - ' . ($s->name ?? '')) : ($s->name ?? '')) : null;

                return [
                    'subject_id' => (int) $first->subject_id,
                    'subject' => $subjectName,
                    'teacher_id' => (int) $first->teacher_id,
                    'teacher' => $teacherName,
                    'count' => $vals->count(),
                    'mean' => $vals->count() ? round($vals->avg(), 2) : null,
                    'pass_rate' => $passRate,
                ];
            })
            ->values();

        // Rank within each subject
        $perSubjectTeacherRanked = $perSubjectTeacher
            ->groupBy('subject_id')
            ->map(function (Collection $rows) {
                return $rows->sortByDesc('mean')->values()->map(function ($row, $idx) {
                    $row['rank_in_subject'] = $row['mean'] === null ? null : ($idx + 1);
                    return $row;
                })->values();
            })
            ->values()
            ->flatten(1)
            ->values();

        // Per-teacher rollup (mean of subject means)
        $perTeacher = $perSubjectTeacher
            ->groupBy('teacher_id')
            ->map(function (Collection $rows, $teacherId) {
                $means = $rows->pluck('mean')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();
                return [
                    'teacher_id' => (int) $teacherId,
                    'teacher' => $rows->first()['teacher'],
                    'subjects_count' => $rows->count(),
                    'mean_of_subject_means' => $means->count() ? round($means->avg(), 2) : null,
                ];
            })
            ->sortByDesc('mean_of_subject_means')
            ->values()
            ->map(function ($row, $idx) {
                $row['rank'] = $row['mean_of_subject_means'] === null ? null : ($idx + 1);
                return $row;
            })
            ->values();

        return [
            'meta' => [
                'scope' => 'school',
                'mode' => 'exam',
                'exam_id' => $exam->id,
                'subject_id' => $subjectId,
                'pass_mark_percent' => $this->passMarkPercent(),
            ],
            'per_teacher' => $perTeacher,
            'per_subject_teacher' => $perSubjectTeacherRanked,
        ];
    }
}

