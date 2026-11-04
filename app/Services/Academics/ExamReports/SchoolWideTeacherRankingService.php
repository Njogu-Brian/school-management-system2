<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Classroom;
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
        return $this->rankingsForPaperIds(
            paperIds: $this->paperIdsForTerm($academicYearId, $termId),
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

    public function rankingsForTermWithImprovement(int $academicYearId, int $termId, ?int $subjectId = null): array
    {
        $current = $this->rankingsForTerm($academicYearId, $termId, $subjectId);
        $prev = (new PreviousTermResolver())->resolve($academicYearId, $termId);
        if (! $prev) {
            return $this->withEmptyImprovementMeta($current, null);
        }

        $previous = $this->rankingsForTerm(
            (int) $prev['academic_year_id'],
            (int) $prev['term_id'],
            $subjectId
        );

        return $this->mergeCategoryImprovement($current, $previous, $prev);
    }

    public function rankingsForExamTypeWithImprovement(
        int $examTypeId,
        int $academicYearId,
        int $termId,
        ?int $subjectId = null
    ): array {
        $current = $this->rankingsForExamType($examTypeId, $academicYearId, $termId, $subjectId);
        $prev = (new PreviousTermResolver())->resolve($academicYearId, $termId);
        if (! $prev) {
            return $this->withEmptyImprovementMeta($current, null);
        }

        $previous = $this->rankingsForExamType(
            $examTypeId,
            (int) $prev['academic_year_id'],
            (int) $prev['term_id'],
            $subjectId
        );

        return $this->mergeCategoryImprovement($current, $previous, $prev);
    }

    /**
     * Staff who have marks (or assignments filled onto marks) in the selected scope.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public function teachersForScope(int $academicYearId, int $termId, ?int $examTypeId = null): Collection
    {
        $paperIds = $examTypeId
            ? $this->paperIdsForExamType($examTypeId, $academicYearId, $termId)
            : $this->paperIdsForTerm($academicYearId, $termId);

        if ($paperIds === []) {
            return collect();
        }

        $payload = $this->rankingsForPaperIds($paperIds, null, 100.0, [
            'scope' => 'school',
            'mode' => $examTypeId ? 'exam_type' : 'term',
        ]);

        return collect($payload['per_teacher'] ?? [])
            ->filter(fn ($row) => ! empty($row['teacher_id']))
            ->map(fn ($row) => [
                'id' => (int) $row['teacher_id'],
                'name' => (string) ($row['teacher'] ?? ('Teacher #'.$row['teacher_id'])),
            ])
            ->unique('id')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Detailed subject×class breakdown for one teacher.
     */
    public function detailForTeacher(
        int $teacherId,
        int $academicYearId,
        int $termId,
        ?int $examTypeId = null
    ): array {
        $paperIds = $examTypeId
            ? $this->paperIdsForExamType($examTypeId, $academicYearId, $termId)
            : $this->paperIdsForTerm($academicYearId, $termId);

        $current = $this->detailRowsForTeacher($teacherId, $paperIds, [
            'scope' => 'teacher',
            'mode' => $examTypeId ? 'exam_type' : 'term',
            'teacher_id' => $teacherId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'exam_type_id' => $examTypeId,
        ]);

        $prev = (new PreviousTermResolver())->resolve($academicYearId, $termId);
        if (! $prev) {
            return $this->withEmptyImprovementMeta($current, null);
        }

        $prevPaperIds = $examTypeId
            ? $this->paperIdsForExamType($examTypeId, (int) $prev['academic_year_id'], (int) $prev['term_id'])
            : $this->paperIdsForTerm((int) $prev['academic_year_id'], (int) $prev['term_id']);

        $previous = $this->detailRowsForTeacher($teacherId, $prevPaperIds, [
            'scope' => 'teacher',
            'mode' => $examTypeId ? 'exam_type' : 'term',
            'teacher_id' => $teacherId,
        ]);

        $attacher = new TeacherImprovementAttacher();
        $rows = $attacher->attach(
            $current['rows'] ?? [],
            collect($previous['rows'] ?? [])->map(function (array $row) {
                $row['match_key'] = $row['match_key'] ?? ($row['classroom_id'].':'.$row['subject_id']);

                return $row;
            }),
            'match_key',
            'mean'
        )->map(function (array $row) {
            $row['match_key'] = $row['match_key'] ?? ($row['classroom_id'].':'.$row['subject_id']);

            return $row;
        });

        $means = $rows->pluck('mean')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();
        $prevMeans = $rows->pluck('prev_mean')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();
        $overall = $means->count() ? round($means->avg(), 2) : null;
        $prevOverall = $prevMeans->count() ? round($prevMeans->avg(), 2) : null;

        return [
            'meta' => array_merge($current['meta'] ?? [], [
                'previous_term' => $prev,
                'has_improvement' => true,
            ]),
            'teacher' => $current['teacher'] ?? null,
            'teacher_id' => $teacherId,
            'summary' => [
                'subjects_count' => $rows->count(),
                'classes_count' => $rows->pluck('classroom_id')->unique()->count(),
                'sections' => $rows->pluck('category')->unique()->values()->all(),
                'mean_of_subject_means' => $overall,
                'prev_mean' => $prevOverall,
                'improvement' => (is_numeric($overall) && is_numeric($prevOverall))
                    ? round((float) $overall - (float) $prevOverall, 2)
                    : null,
            ],
            'rows' => $rows,
            'most_improved' => [
                'teachers' => [],
                'subjects' => $attacher->topImproved($rows, 10),
            ],
            // Flat aliases for export compatibility.
            'per_teacher' => collect([[
                'teacher_id' => $teacherId,
                'teacher' => $current['teacher'] ?? null,
                'subjects_count' => $rows->count(),
                'mean_of_subject_means' => $overall,
                'prev_mean' => $prevOverall,
                'improvement' => (is_numeric($overall) && is_numeric($prevOverall))
                    ? round((float) $overall - (float) $prevOverall, 2)
                    : null,
                'rank' => 1,
            ]]),
            'per_subject_teacher' => $rows,
        ];
    }

    /**
     * @param  list<int>  $paperIds
     */
    private function detailRowsForTeacher(int $teacherId, array $paperIds, array $meta): array
    {
        $staff = Staff::query()->find($teacherId, ['id', 'first_name', 'middle_name', 'last_name']);
        $teacherName = $staff ? trim(($staff->first_name ?? '').' '.($staff->middle_name ?? '').' '.($staff->last_name ?? '')) : null;
        $teacherName = ($teacherName !== null && $teacherName !== '') ? $teacherName : null;

        if ($paperIds === []) {
            return [
                'meta' => array_merge($meta, ['pass_mark_percent' => $this->passMarkPercent()]),
                'teacher' => $teacherName,
                'teacher_id' => $teacherId,
                'rows' => collect(),
                'summary' => [
                    'subjects_count' => 0,
                    'classes_count' => 0,
                    'sections' => [],
                    'mean_of_subject_means' => null,
                    'prev_mean' => null,
                    'improvement' => null,
                ],
                'per_teacher' => collect(),
                'per_subject_teacher' => collect(),
            ];
        }

        $assignmentTeachers = $this->assignmentTeacherLookup($paperIds);
        $categoryByExam = $this->categoryLookupForPapers($paperIds);
        $exams = Exam::query()
            ->whereIn('id', $paperIds)
            ->get(['id', 'classroom_id', 'subject_id', 'stream_id', 'name'])
            ->keyBy('id');

        $marks = ExamMark::query()
            ->whereIn('exam_id', $paperIds)
            ->get(['exam_id', 'teacher_id', 'subject_id', 'score_raw', 'score_moderated', 'grade_label', 'is_absent'])
            ->map(fn (ExamMark $m) => $m->applyMarksObtained())
            ->filter(fn (ExamMark $m) => $m->marks_obtained !== null)
            ->map(function (ExamMark $m) use ($assignmentTeachers) {
                if (empty($m->teacher_id)) {
                    $key = (int) $m->exam_id.':'.(int) $m->subject_id;
                    $staffId = $assignmentTeachers[$key] ?? null;
                    if ($staffId) {
                        $m->teacher_id = $staffId;
                    }
                }

                return $m;
            })
            ->filter(fn (ExamMark $m) => (int) $m->teacher_id === $teacherId)
            ->values();

        $classroomIds = $exams->pluck('classroom_id')->filter()->unique()->values()->all();
        $classrooms = Classroom::query()
            ->whereIn('id', $classroomIds)
            ->get(['id', 'name', 'level_type'])
            ->keyBy('id');
        $subjectIds = $marks->pluck('subject_id')->unique()->values();
        $subjects = Subject::query()->whereIn('id', $subjectIds)->get(['id', 'code', 'name'])->keyBy('id');
        $passThreshold = ($this->passMarkPercent() / 100.0) * 100.0;

        $rows = $marks
            ->groupBy(function (ExamMark $m) use ($exams) {
                $exam = $exams->get($m->exam_id);
                $classroomId = (int) ($exam?->classroom_id ?? 0);

                return $classroomId.':'.(int) $m->subject_id;
            })
            ->map(function (Collection $group) use ($exams, $classrooms, $subjects, $categoryByExam, $passThreshold, $teacherId, $teacherName) {
                $first = $group->first();
                $exam = $exams->get($first->exam_id);
                $classroomId = (int) ($exam?->classroom_id ?? 0);
                $classroom = $classroomId ? $classrooms->get($classroomId) : null;
                $categoryKey = $categoryByExam[(int) $first->exam_id] ?? ClassroomLevelCategory::resolve($classroom);
                $subject = $subjects->get($first->subject_id);
                $vals = $group->pluck('marks_obtained')->map(fn ($v) => (float) $v)->values();
                $passRate = $vals->count()
                    ? round(($vals->filter(fn ($m) => $m >= $passThreshold)->count() / $vals->count()) * 100, 2)
                    : null;
                $subjectName = $subject
                    ? (($subject->code ?? '') ? (($subject->code ?? '').' - '.($subject->name ?? '')) : ($subject->name ?? ''))
                    : null;

                return [
                    'match_key' => $classroomId.':'.(int) $first->subject_id,
                    'teacher_id' => $teacherId,
                    'teacher' => $teacherName,
                    'classroom_id' => $classroomId ?: null,
                    'classroom' => $classroom?->name,
                    'subject_id' => (int) $first->subject_id,
                    'subject' => $subjectName,
                    'category_key' => $categoryKey,
                    'category' => ClassroomLevelCategory::labelFor($categoryKey),
                    'count' => $vals->count(),
                    'mean' => $vals->count() ? round($vals->avg(), 2) : null,
                    'pass_rate' => $passRate,
                ];
            })
            ->sortBy([
                ['category', 'asc'],
                ['classroom', 'asc'],
                ['subject', 'asc'],
            ])
            ->values();

        $means = $rows->pluck('mean')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();

        return [
            'meta' => array_merge($meta, ['pass_mark_percent' => $this->passMarkPercent()]),
            'teacher' => $teacherName,
            'teacher_id' => $teacherId,
            'rows' => $rows,
            'summary' => [
                'subjects_count' => $rows->count(),
                'classes_count' => $rows->pluck('classroom_id')->filter()->unique()->count(),
                'sections' => $rows->pluck('category')->unique()->values()->all(),
                'mean_of_subject_means' => $means->count() ? round($means->avg(), 2) : null,
                'prev_mean' => null,
                'improvement' => null,
            ],
            'per_teacher' => collect(),
            'per_subject_teacher' => $rows,
        ];
    }

    /**
     * @return list<int>
     */
    private function paperIdsForTerm(int $academicYearId, int $termId): array
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

        return $paperIds->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * @return list<int>
     */
    private function paperIdsForExamType(int $examTypeId, int $academicYearId, int $termId): array
    {
        return Exam::query()
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
    }

    /**
     * @param  array{academic_year_id: int, term_id: int, term_name: string, academic_year: string|int|null}|null  $prev
     */
    private function mergeCategoryImprovement(array $current, array $previous, ?array $prev): array
    {
        $attacher = new TeacherImprovementAttacher();

        $byCategory = collect($current['by_category'] ?? [])->map(function (array $cat) use ($attacher, $previous) {
            $prevCat = collect($previous['by_category'] ?? [])->firstWhere('key', $cat['key']) ?? [
                'per_teacher' => [],
                'per_subject_teacher' => [],
            ];

            $teachers = $attacher->attach(
                collect($cat['per_teacher'] ?? [])->map(function (array $row) {
                    $row['match_key'] = ($row['category_key'] ?? '').':'.($row['teacher_id'] ?? '');
                    $row['mean'] = $row['mean_of_subject_means'] ?? null;

                    return $row;
                }),
                collect($prevCat['per_teacher'] ?? [])->map(function (array $row) {
                    $row['match_key'] = ($row['category_key'] ?? '').':'.($row['teacher_id'] ?? '');
                    $row['mean'] = $row['mean_of_subject_means'] ?? null;

                    return $row;
                }),
                'match_key',
                'mean'
            )->map(function (array $row) {
                $row['mean_of_subject_means'] = $row['mean'] ?? $row['mean_of_subject_means'] ?? null;
                unset($row['mean'], $row['match_key']);

                return $row;
            });

            $subjects = $attacher->attach(
                collect($cat['per_subject_teacher'] ?? [])->map(function (array $row) {
                    $row['match_key'] = ($row['category_key'] ?? '').':'
                        .($row['classroom_id'] ?? '').':'
                        .($row['subject_id'] ?? '').':'
                        .($row['teacher_id'] ?? '');

                    return $row;
                }),
                collect($prevCat['per_subject_teacher'] ?? [])->map(function (array $row) {
                    $row['match_key'] = ($row['category_key'] ?? '').':'
                        .($row['classroom_id'] ?? '').':'
                        .($row['subject_id'] ?? '').':'
                        .($row['teacher_id'] ?? '');

                    return $row;
                }),
                'match_key',
                'mean'
            )->map(function (array $row) {
                unset($row['match_key']);

                return $row;
            });

            $cat['per_teacher'] = $teachers;
            $cat['per_subject_teacher'] = $subjects;
            $cat['most_improved'] = $attacher->topImproved($teachers, 5);

            return $cat;
        })->values();

        $perTeacher = $byCategory->flatMap(fn (array $cat) => $cat['per_teacher'])->values();
        $perSubject = $byCategory->flatMap(fn (array $cat) => $cat['per_subject_teacher'])->values();

        return [
            'meta' => array_merge($current['meta'] ?? [], [
                'previous_term' => $prev,
                'has_improvement' => $prev !== null,
            ]),
            'per_teacher' => $perTeacher,
            'per_subject_teacher' => $perSubject,
            'by_category' => $byCategory,
            'most_improved' => [
                'teachers' => $attacher->topImproved($perTeacher, 10),
                'subjects' => $attacher->topImproved($perSubject, 10),
            ],
        ];
    }

    private function withEmptyImprovementMeta(array $payload, ?array $prev): array
    {
        $payload['meta'] = array_merge($payload['meta'] ?? [], [
            'previous_term' => $prev,
            'has_improvement' => false,
        ]);
        $payload['most_improved'] = $payload['most_improved'] ?? ['teachers' => [], 'subjects' => []];

        return $payload;
    }

    /**
     * @param  list<int>  $paperIds
     */
    private function rankingsForPaperIds(array $paperIds, ?int $subjectId, float $maxMarks, array $meta): array
    {
        if ($paperIds === []) {
            return [
                'meta' => array_merge($meta, [
                    'pass_mark_percent' => $this->passMarkPercent(),
                    'grouped_by_category' => true,
                ]),
                'per_teacher' => collect(),
                'per_subject_teacher' => collect(),
                'by_category' => collect(),
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
        $categoryByExam = $this->categoryLookupForPapers($paperIds);
        $classroomByExam = $this->classroomLookupForPapers($paperIds);

        $marks = $marks->map(function (ExamMark $m) use ($assignmentTeachers, $categoryByExam, $classroomByExam) {
            if (empty($m->teacher_id)) {
                $key = (int) $m->exam_id.':'.(int) $m->subject_id;
                $staffId = $assignmentTeachers[$key] ?? null;
                if ($staffId) {
                    $m->teacher_id = $staffId;
                }
            }
            $m->setAttribute(
                'level_category',
                $categoryByExam[(int) $m->exam_id] ?? ClassroomLevelCategory::OTHER
            );
            $classroom = $classroomByExam[(int) $m->exam_id] ?? null;
            $m->setAttribute('classroom_id', $classroom?->id);
            $m->setAttribute('classroom_name', $classroom?->name);

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

        // One row per class × subject × teacher so Subject-by-Subject shows the specific class subject.
        $perSubjectTeacher = $marks
            ->groupBy(function (ExamMark $m) {
                $category = $m->getAttribute('level_category') ?? ClassroomLevelCategory::OTHER;
                $classroomId = (int) ($m->getAttribute('classroom_id') ?? 0);

                return $category.':'.$classroomId.':'.$m->subject_id.':'.$m->teacher_id;
            })
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
                $category = (string) ($first->getAttribute('level_category') ?? ClassroomLevelCategory::OTHER);
                $classroomId = (int) ($first->getAttribute('classroom_id') ?? 0);

                return [
                    'category_key' => $category,
                    'category' => ClassroomLevelCategory::labelFor($category),
                    'classroom_id' => $classroomId ?: null,
                    'classroom' => $first->getAttribute('classroom_name'),
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

        $byCategory = collect(ClassroomLevelCategory::ordered())
            ->map(function (array $metaRow) use ($perSubjectTeacher) {
                $rows = $perSubjectTeacher->where('category_key', $metaRow['key'])->values();
                if ($rows->isEmpty()) {
                    return null;
                }

                // Rank teachers within each subject across classes in this phase.
                $rankedSubjects = $rows
                    ->groupBy('subject_id')
                    ->map(function (Collection $subjectRows) {
                        return $subjectRows->sortByDesc('mean')->values()->map(function ($row, $idx) {
                            $row['rank_in_subject'] = $row['mean'] === null ? null : ($idx + 1);

                            return $row;
                        })->values();
                    })
                    ->values()
                    ->flatten(1)
                    ->sortBy([
                        ['classroom', 'asc'],
                        ['subject', 'asc'],
                        ['rank_in_subject', 'asc'],
                    ])
                    ->values();

                $perTeacher = $rows
                    ->groupBy('teacher_id')
                    ->map(function (Collection $teacherRows, $teacherId) {
                        $means = $teacherRows->pluck('mean')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();

                        return [
                            'teacher_id' => (int) $teacherId,
                            'teacher' => $teacherRows->first()['teacher'],
                            'category_key' => $teacherRows->first()['category_key'],
                            'category' => $teacherRows->first()['category'],
                            'subjects_count' => $teacherRows->count(),
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
                    'key' => $metaRow['key'],
                    'label' => $metaRow['label'],
                    'detail' => $metaRow['detail'],
                    'per_teacher' => $perTeacher,
                    'per_subject_teacher' => $rankedSubjects,
                ];
            })
            ->filter()
            ->values();

        // Flat lists keep export/UI helpers working: ranked within each category, ordered by band.
        $perTeacher = $byCategory->flatMap(fn (array $cat) => $cat['per_teacher'])->values();
        $perSubjectTeacherRanked = $byCategory->flatMap(fn (array $cat) => $cat['per_subject_teacher'])->values();

        return [
            'meta' => array_merge($meta, [
                'pass_mark_percent' => $this->passMarkPercent(),
                'grouped_by_category' => true,
            ]),
            'per_teacher' => $perTeacher,
            'per_subject_teacher' => $perSubjectTeacherRanked,
            'by_category' => $byCategory,
        ];
    }

    /**
     * @param  list<int>  $paperIds
     * @return array<int, string> exam_id => category key
     */
    private function categoryLookupForPapers(array $paperIds): array
    {
        $out = [];
        foreach ($this->classroomLookupForPapers($paperIds) as $examId => $classroom) {
            $out[(int) $examId] = ClassroomLevelCategory::resolve($classroom);
        }

        return $out;
    }

    /**
     * @param  list<int>  $paperIds
     * @return array<int, Classroom|null> exam_id => classroom
     */
    private function classroomLookupForPapers(array $paperIds): array
    {
        $exams = Exam::query()
            ->whereIn('id', $paperIds)
            ->get(['id', 'classroom_id']);

        $classroomIds = $exams->pluck('classroom_id')->filter()->unique()->values()->all();
        $classrooms = Classroom::query()
            ->whereIn('id', $classroomIds)
            ->get(['id', 'name', 'level_type'])
            ->keyBy('id');

        $out = [];
        foreach ($exams as $exam) {
            $out[(int) $exam->id] = $exam->classroom_id ? $classrooms->get($exam->classroom_id) : null;
        }

        return $out;
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


