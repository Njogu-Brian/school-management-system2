<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Subject;
use App\Models\Staff;
use Illuminate\Support\Collection;

class SchoolWideTeacherRankingService
{
    public function __construct(
        private readonly ExamScopeResolver $examScope = new ExamScopeResolver(),
    ) {}

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
        return $this->rankingsForPaperIds(
            paperIds: [(int) $exam->id],
            subjectId: $subjectId,
            maxMarks: (float) ($exam->max_marks ?? 100),
            meta: [
                'scope' => 'school',
                'mode' => 'exam',
                'exam_id' => $exam->id,
                'subject_id' => $subjectId,
            ],
        );
    }

    /**
     * School-wide rankings for every paper under an exam type / year / term sitting.
     */
    public function rankingsForExamType(
        int $examTypeId,
        int $academicYearId,
        int $termId,
        ?int $subjectId = null
    ): array {
        $paperIds = Exam::query()
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->whereNotNull('subject_id')
            ->where(function ($q) use ($examTypeId) {
                $q->where('exam_type_id', $examTypeId)
                    ->orWhereHas('examSession', fn ($s) => $s->where('exam_type_id', $examTypeId));
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $this->rankingsForPaperIds(
            paperIds: $paperIds,
            subjectId: $subjectId,
            maxMarks: 100.0,
            meta: [
                'scope' => 'school',
                'mode' => 'exam_type',
                'exam_type_id' => $examTypeId,
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'subject_id' => $subjectId,
            ],
        );
    }

    /**
     * School-wide rankings across all core papers in a term (KNEC CBA excluded).
     */
    public function rankingsForTerm(int $academicYearId, int $termId, ?int $subjectId = null): array
    {
        $classroomIds = Exam::query()
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->whereNotNull('classroom_id')
            ->distinct()
            ->pluck('classroom_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $paperIds = collect();
        foreach ($classroomIds as $classroomId) {
            $paperIds = $paperIds->merge(
                $this->examScope->papersForTerm($academicYearId, $termId, $classroomId, null)->pluck('id')
            );
        }
        $paperIds = $paperIds->map(fn ($id) => (int) $id)->unique()->values()->all();

        return $this->rankingsForPaperIds(
            paperIds: $paperIds,
            subjectId: $subjectId,
            maxMarks: 100.0,
            meta: [
                'scope' => 'school',
                'mode' => 'term',
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'subject_id' => $subjectId,
            ],
        );
    }

    /**
     * @param  list<int>  $paperIds
     */
    private function rankingsForPaperIds(array $paperIds, ?int $subjectId, float $maxMarks, array $meta): array
    {
        if ($paperIds === []) {
            return [
                'meta' => array_merge($meta, ['pass_mark_percent' => $this->passMarkPercent()]),
                'per_teacher' => collect(),
                'per_subject_teacher' => collect(),
            ];
        }

        $query = ExamMark::query()
            ->whereIn('exam_id', $paperIds);

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        $marks = $query->get(['exam_id', 'teacher_id', 'subject_id', 'score_raw', 'score_moderated', 'grade_label', 'is_absent'])
            ->map(fn (ExamMark $m) => $m->applyMarksObtained())
            ->filter(fn (ExamMark $m) => $m->marks_obtained !== null)
            ->values();

        // Fill missing mark teacher_id from classroom_subjects when possible.
        $assignmentTeachers = $this->assignmentTeacherLookup($paperIds);
        $marks = $marks->map(function (ExamMark $m) use ($assignmentTeachers) {
            if (! empty($m->teacher_id)) {
                return $m;
            }
            $key = (int) $m->exam_id.':'.(int) $m->subject_id;
            $staffId = $assignmentTeachers[$key] ?? null;
            if ($staffId) {
                $m->teacher_id = $staffId;
            }

            return $m;
        })->filter(fn (ExamMark $m) => ! empty($m->teacher_id))->values();

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

        $passThreshold = ($this->passMarkPercent() / 100.0) * $maxMarks;

        $perSubjectTeacher = $marks
            ->groupBy(fn (ExamMark $m) => $m->subject_id.':'.$m->teacher_id)
            ->map(function (Collection $rows) use ($teachers, $subjects, $passThreshold) {
                $first = $rows->first();
                $vals = $rows->pluck('marks_obtained')->map(fn ($v) => (float) $v)->values();
                $passRate = $vals->count()
                    ? round(($vals->filter(fn ($m) => $m >= $passThreshold)->count() / $vals->count()) * 100, 2)
                    : null;

                $t = $teachers->get($first->teacher_id);
                $teacherName = $t ? trim(($t->first_name ?? '').' '.($t->middle_name ?? '').' '.($t->last_name ?? '')) : null;
                $s = $subjects->get($first->subject_id);
                $subjectName = $s ? (($s->code ?? '') ? (($s->code ?? '').' - '.($s->name ?? '')) : ($s->name ?? '')) : null;

                return [
                    'subject_id' => (int) $first->subject_id,
                    'subject' => $subjectName,
                    'teacher_id' => (int) $first->teacher_id,
                    'teacher' => ($teacherName !== null && trim($teacherName) !== '') ? $teacherName : null,
                    'count' => $vals->count(),
                    'mean' => $vals->count() ? round($vals->avg(), 2) : null,
                    'pass_rate' => $passRate,
                ];
            })
            ->values();

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
            'meta' => array_merge($meta, ['pass_mark_percent' => $this->passMarkPercent()]),
            'per_teacher' => $perTeacher,
            'per_subject_teacher' => $perSubjectTeacherRanked,
        ];
    }

    /**
     * @param  list<int>  $paperIds
     * @return array<string, int> exam_id:subject_id => staff_id
     */
    private function assignmentTeacherLookup(array $paperIds): array
    {
        $exams = Exam::query()
            ->whereIn('id', $paperIds)
            ->get(['id', 'classroom_id', 'subject_id', 'stream_id', 'academic_year_id', 'term_id']);

        $out = [];
        foreach ($exams as $exam) {
            if (! $exam->classroom_id || ! $exam->subject_id) {
                continue;
            }
            $staffId = ClassroomSubject::query()
                ->where('classroom_id', $exam->classroom_id)
                ->where('subject_id', $exam->subject_id)
                ->whereNotNull('staff_id')
                ->when($exam->stream_id, function ($q) use ($exam) {
                    $q->where(function ($q2) use ($exam) {
                        $q2->whereNull('stream_id')->orWhere('stream_id', $exam->stream_id);
                    });
                })
                ->orderByRaw('CASE WHEN stream_id IS NULL THEN 1 ELSE 0 END')
                ->value('staff_id');
            if ($staffId) {
                $out[(int) $exam->id.':'.(int) $exam->subject_id] = (int) $staffId;
            }
        }

        return $out;
    }
}


