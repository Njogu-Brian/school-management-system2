<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamSession;
use App\Models\Academics\Subject;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Subject-scoped analysis for teachers: student scores, means, and prior-term deltas.
 */
final class TeacherSubjectAnalysisService
{
    public function __construct(
        private readonly ExamScopeResolver $examScope = new ExamScopeResolver(),
        private readonly PreviousTermResolver $previousTerms = new PreviousTermResolver(),
        private readonly TeacherImprovementAttacher $improvement = new TeacherImprovementAttacher(),
    ) {}

    private function passMarkPercent(): float
    {
        $v = setting('exam_pass_mark_percent', 50);
        $f = is_numeric($v) ? (float) $v : 50.0;

        return max(0.0, min(100.0, $f));
    }

    /**
     * Teaching / analysis slots.
     * Admins & seniors: all class×subject combinations (optionally narrowed by year/term exams).
     * Subject teachers: only their assigned classroom_subjects.
     *
     * @return list<array<string, mixed>>
     */
    public function teachingScope(?User $user, ?int $academicYearId = null, ?int $termId = null): array
    {
        if (! $user) {
            return [];
        }

        if (ExamReportsAccess::userHasFullAccess($user)) {
            return $this->schoolWideScope($academicYearId, $termId);
        }

        if (! $user->staff) {
            return [];
        }

        $staffId = (int) $user->staff->id;
        $query = ClassroomSubject::query()
            ->where('staff_id', $staffId)
            ->whereNotNull('subject_id')
            ->whereNotNull('classroom_id')
            ->with(['classroom:id,name,level_type', 'subject:id,name,code', 'stream:id,name']);

        if ($academicYearId) {
            $query->where(function ($q) use ($academicYearId) {
                $q->whereNull('academic_year_id')->orWhere('academic_year_id', $academicYearId);
            });
        }
        if ($termId) {
            $query->where(function ($q) use ($termId) {
                $q->whereNull('term_id')->orWhere('term_id', $termId);
            });
        }

        $rows = $query->get();
        if ($rows->isEmpty() && method_exists($user, 'getAssignedSubjectIds')) {
            $subjectIds = $user->getAssignedSubjectIds();
            $classroomIds = ExamReportsAccess::classroomsQueryFor($user)->pluck('id');
            if ($subjectIds !== [] && $classroomIds->isNotEmpty()) {
                $rows = ClassroomSubject::query()
                    ->whereIn('classroom_id', $classroomIds)
                    ->whereIn('subject_id', $subjectIds)
                    ->with(['classroom:id,name,level_type', 'subject:id,name,code', 'stream:id,name'])
                    ->get();
            }
        }

        return $this->mapScopeRows($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function schoolWideScope(?int $academicYearId, ?int $termId): array
    {
        $query = ClassroomSubject::query()
            ->whereNotNull('subject_id')
            ->whereNotNull('classroom_id')
            ->with(['classroom:id,name,level_type', 'subject:id,name,code', 'stream:id,name', 'teacher:id,first_name,middle_name,last_name']);

        if ($academicYearId) {
            $query->where(function ($q) use ($academicYearId) {
                $q->whereNull('academic_year_id')->orWhere('academic_year_id', $academicYearId);
            });
        }
        if ($termId) {
            $query->where(function ($q) use ($termId) {
                $q->whereNull('term_id')->orWhere('term_id', $termId);
            });
        }

        $rows = $query->get();

        // Also include class×subject pairs that have exam papers even if unassigned.
        if ($academicYearId && $termId) {
            $examPairs = Exam::query()
                ->where('academic_year_id', $academicYearId)
                ->where('term_id', $termId)
                ->whereNotNull('classroom_id')
                ->whereNotNull('subject_id')
                ->get(['classroom_id', 'subject_id', 'stream_id']);

            $existing = $rows->map(fn ($r) => $r->classroom_id.':'.($r->stream_id ?? 0).':'.$r->subject_id)->all();
            $missingClassroomIds = [];
            $missingSubjectIds = [];
            foreach ($examPairs as $pair) {
                $key = $pair->classroom_id.':'.($pair->stream_id ?? 0).':'.$pair->subject_id;
                if (! in_array($key, $existing, true)) {
                    $missingClassroomIds[] = (int) $pair->classroom_id;
                    $missingSubjectIds[] = (int) $pair->subject_id;
                }
            }
            if ($missingClassroomIds !== []) {
                $classrooms = Classroom::query()->whereIn('id', array_unique($missingClassroomIds))->get(['id', 'name', 'level_type'])->keyBy('id');
                $subjects = Subject::query()->whereIn('id', array_unique($missingSubjectIds))->get(['id', 'name', 'code'])->keyBy('id');
                foreach ($examPairs as $pair) {
                    $key = $pair->classroom_id.':'.($pair->stream_id ?? 0).':'.$pair->subject_id;
                    if (in_array($key, $existing, true)) {
                        continue;
                    }
                    $existing[] = $key;
                    $synthetic = new ClassroomSubject([
                        'classroom_id' => $pair->classroom_id,
                        'subject_id' => $pair->subject_id,
                        'stream_id' => $pair->stream_id,
                    ]);
                    $synthetic->setRelation('classroom', $classrooms->get($pair->classroom_id));
                    $synthetic->setRelation('subject', $subjects->get($pair->subject_id));
                    $rows->push($synthetic);
                }
            }
        }

        return $this->mapScopeRows($rows, includeTeacher: true);
    }

    /**
     * @param  Collection<int, ClassroomSubject>  $rows
     * @return list<array<string, mixed>>
     */
    private function mapScopeRows(Collection $rows, bool $includeTeacher = false): array
    {
        return $rows
            ->map(function (ClassroomSubject $row) use ($includeTeacher) {
                $classroom = $row->classroom;
                $category = ClassroomLevelCategory::resolve($classroom);
                $out = [
                    'classroom_id' => (int) $row->classroom_id,
                    'classroom_name' => $classroom?->name,
                    'stream_id' => $row->stream_id ? (int) $row->stream_id : null,
                    'stream_name' => $row->stream?->name,
                    'subject_id' => (int) $row->subject_id,
                    'subject_name' => $row->subject?->name,
                    'subject_code' => $row->subject?->code,
                    'category_key' => $category,
                    'category' => ClassroomLevelCategory::labelFor($category),
                ];
                if ($includeTeacher) {
                    $staff = $row->teacher;
                    $teacherName = $staff
                        ? trim(($staff->first_name ?? '').' '.($staff->middle_name ?? '').' '.($staff->last_name ?? ''))
                        : null;
                    $out['teacher_id'] = $row->staff_id ? (int) $row->staff_id : null;
                    $out['teacher_name'] = ($teacherName !== null && trim($teacherName) !== '') ? $teacherName : null;
                }

                return $out;
            })
            ->unique(fn ($r) => $r['classroom_id'].':'.($r['stream_id'] ?? 0).':'.$r['subject_id'])
            ->sortBy([
                ['classroom_name', 'asc'],
                ['subject_name', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * Detailed student-level analysis for one teaching slot.
     */
    public function analyze(
        User $user,
        int $classroomId,
        int $subjectId,
        int $academicYearId,
        int $termId,
        string $mode = 'term',
        ?int $examTypeId = null,
        ?int $streamId = null,
    ): array {
        ExamReportsAccess::assertClassroomAccess($user, $classroomId);

        $allowedSubjects = ExamReportsAccess::subjectIdsForUserInClass($user, $classroomId, $streamId);
        if (is_array($allowedSubjects) && ! in_array($subjectId, $allowedSubjects, true)) {
            abort(403, 'You are not assigned to this subject in this class.');
        }

        $classroom = Classroom::query()->findOrFail($classroomId, ['id', 'name', 'level_type']);
        $subject = Subject::query()->findOrFail($subjectId, ['id', 'name', 'code']);

        if ($mode === 'exam_type' || $mode === 'by_exam_type') {
            if (! $examTypeId) {
                abort(422, 'exam_type_id is required for single-exam analysis.');
            }
            $session = $this->examScope->findExamSession(
                $examTypeId,
                $academicYearId,
                $termId,
                $classroomId,
                $streamId
            );
            if (! $session) {
                return $this->emptyPayload($classroom, $subject, $streamId, [
                    'mode' => 'exam_type',
                    'academic_year_id' => $academicYearId,
                    'term_id' => $termId,
                    'exam_type_id' => $examTypeId,
                ], 'No exam sitting found for this exam type, class, year, and term.');
            }
            $session->loadMissing('examType');
            if (ExamScopeResolver::isSideExamType($session->examType?->name, $session->examType?->code)) {
                return $this->emptyPayload($classroom, $subject, $streamId, [
                    'mode' => 'exam_type',
                ], 'KNEC CBA is excluded from exam report analysis.');
            }

            $current = $this->buildForSession($session, $classroom, $subject, $streamId);
            $prev = $this->previousTerms->resolve($academicYearId, $termId);
            if ($prev) {
                $prevSession = $this->examScope->findExamSession(
                    $examTypeId,
                    (int) $prev['academic_year_id'],
                    (int) $prev['term_id'],
                    $classroomId,
                    $streamId
                );
                $previous = $prevSession
                    ? $this->buildForSession($prevSession, $classroom, $subject, $streamId)
                    : null;
                $current = $this->attachImprovement($current, $previous, $prev);
            }

            return $current;
        }

        $current = $this->buildForTerm($academicYearId, $termId, $classroom, $subject, $streamId);
        $prev = $this->previousTerms->resolve($academicYearId, $termId);
        if ($prev) {
            $previous = $this->buildForTerm(
                (int) $prev['academic_year_id'],
                (int) $prev['term_id'],
                $classroom,
                $subject,
                $streamId
            );
            $current = $this->attachImprovement($current, $previous, $prev);
        }

        return $current;
    }

    private function buildForSession(
        ExamSession $session,
        Classroom $classroom,
        Subject $subject,
        ?int $streamId
    ): array {
        $paperIds = Exam::query()
            ->where('exam_session_id', $session->id)
            ->where('subject_id', $subject->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($paperIds === []) {
            $paperIds = Exam::query()
                ->where('academic_year_id', $session->academic_year_id)
                ->where('term_id', $session->term_id)
                ->where('classroom_id', $classroom->id)
                ->where('subject_id', $subject->id)
                ->where(function ($q) use ($session) {
                    $q->where('exam_type_id', $session->exam_type_id)
                        ->orWhere('exam_session_id', $session->id);
                })
                ->when($streamId, fn ($q) => $q->where(function ($q2) use ($streamId) {
                    $q2->whereNull('stream_id')->orWhere('stream_id', $streamId);
                }), fn ($q) => $q->whereNull('stream_id'))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $students = $this->students($classroom, $streamId, $paperIds);
        $marks = $this->marksForPapers($paperIds, $students->pluck('id')->all(), $subject->id);
        $byStudent = $marks->groupBy('student_id');
        $maxMarks = (float) (Exam::query()->whereIn('id', $paperIds)->avg('max_marks') ?: 100);

        $studentRows = $students->map(function (Student $student) use ($byStudent, $maxMarks) {
            $vals = ($byStudent->get($student->id) ?? collect())
                ->pluck('marks_obtained')
                ->filter(fn ($v) => $v !== null)
                ->map(fn ($v) => (float) $v)
                ->values();
            $score = $vals->count() ? round($vals->avg(), 2) : null;
            $percentage = ($score !== null && $maxMarks > 0) ? round(($score / $maxMarks) * 100, 2) : null;

            return [
                'student_id' => (int) $student->id,
                'admission_number' => $student->admission_number,
                'name' => $student->name,
                'stream_id' => $student->stream_id ? (int) $student->stream_id : null,
                'score' => $score,
                'percentage' => $percentage,
                'grade_label' => ($byStudent->get($student->id) ?? collect())->pluck('grade_label')->filter()->last(),
                'sitting_scores' => [],
            ];
        });

        $studentRows = $this->rankStudents($studentRows);

        return $this->payload(
            classroom: $classroom,
            subject: $subject,
            streamId: $streamId,
            meta: [
                'mode' => 'exam_type',
                'exam_session_id' => (int) $session->id,
                'exam_type_id' => (int) $session->exam_type_id,
                'exam_session_name' => $session->examType?->name ?? $session->name,
                'academic_year_id' => (int) $session->academic_year_id,
                'term_id' => (int) $session->term_id,
                'max_marks' => $maxMarks,
            ],
            students: $studentRows,
            sittings: [],
        );
    }

    private function buildForTerm(
        int $academicYearId,
        int $termId,
        Classroom $classroom,
        Subject $subject,
        ?int $streamId
    ): array {
        $sessions = $this->examScope->coreExamSessionsForTerm($academicYearId, $termId, $classroom->id, $streamId);
        $papers = $this->examScope->papersForTerm($academicYearId, $termId, $classroom->id, $streamId)
            ->where('subject_id', $subject->id)
            ->values();
        $paperIds = $papers->pluck('id')->map(fn ($id) => (int) $id)->all();
        $students = $this->students($classroom, $streamId, $paperIds);
        $marks = $this->marksForPapers($paperIds, $students->pluck('id')->all(), $subject->id);

        $sittings = [];
        $sittingScoresByStudent = [];
        foreach ($sessions as $session) {
            $session->loadMissing('examType');
            $sessionPaperIds = Exam::query()
                ->where('exam_session_id', $session->id)
                ->where('subject_id', $subject->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if ($sessionPaperIds === []) {
                continue;
            }
            $key = 'et_'.(int) $session->exam_type_id;
            $label = trim((string) ($session->examType?->name ?? $session->name ?? 'Exam'));
            $sittings[] = [
                'key' => $key,
                'label' => $label,
                'exam_type_id' => (int) $session->exam_type_id,
                'session_id' => (int) $session->id,
            ];
            $sessionMarks = $marks->whereIn('exam_id', $sessionPaperIds);
            foreach ($sessionMarks->groupBy('student_id') as $studentId => $rows) {
                $vals = $rows->pluck('marks_obtained')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
                $sittingScoresByStudent[(int) $studentId][$key] = [
                    'score' => $vals->count() ? round($vals->avg(), 2) : null,
                    'count' => $vals->count(),
                ];
            }
        }

        $weights = $papers->mapWithKeys(function (Exam $e) {
            $w = is_numeric($e->weight) ? (float) $e->weight : 1.0;

            return [$e->id => ($w > 0 ? $w : 1.0)];
        });

        $studentRows = $students->map(function (Student $student) use ($marks, $weights, $sittings, $sittingScoresByStudent) {
            $items = $marks->where('student_id', $student->id);
            $sumW = 0.0;
            $sum = 0.0;
            foreach ($items as $m) {
                if ($m->marks_obtained === null) {
                    continue;
                }
                $w = (float) ($weights->get($m->exam_id) ?? 1.0);
                $sumW += $w;
                $sum += ((float) $m->marks_obtained) * $w;
            }
            $score = $sumW > 0 ? round($sum / $sumW, 2) : null;
            $sittingScores = [];
            foreach ($sittings as $sitting) {
                $sittingScores[$sitting['key']] = $sittingScoresByStudent[(int) $student->id][$sitting['key']] ?? [
                    'score' => null,
                    'count' => 0,
                ];
            }

            return [
                'student_id' => (int) $student->id,
                'admission_number' => $student->admission_number,
                'name' => $student->name,
                'stream_id' => $student->stream_id ? (int) $student->stream_id : null,
                'score' => $score,
                'percentage' => $score,
                'grade_label' => $items->pluck('grade_label')->filter()->last(),
                'sitting_scores' => $sittingScores,
            ];
        });

        $studentRows = $this->rankStudents($studentRows);

        return $this->payload(
            classroom: $classroom,
            subject: $subject,
            streamId: $streamId,
            meta: [
                'mode' => 'term',
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'max_marks' => 100.0,
            ],
            students: $studentRows,
            sittings: $sittings,
        );
    }

    /**
     * @param  list<int>  $paperIds
     * @return Collection<int, Student>
     */
    private function students(Classroom $classroom, ?int $streamId, array $paperIds): Collection
    {
        $students = Student::withAlumni()
            ->where('classroom_id', $classroom->id)
            ->where('archive', 0)
            ->whereNull('archived_at')
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->orderBy('name')
            ->get(['id', 'name', 'admission_number', 'stream_id']);

        if ($students->isEmpty() && $paperIds !== []) {
            $ids = ExamMark::query()->whereIn('exam_id', $paperIds)->distinct()->pluck('student_id');
            $students = Student::withAlumni()
                ->whereIn('id', $ids)
                ->orderBy('name')
                ->get(['id', 'name', 'admission_number', 'stream_id']);
        }

        return $students;
    }

    /**
     * @param  list<int>  $paperIds
     * @param  list<int>  $studentIds
     * @return Collection<int, ExamMark>
     */
    private function marksForPapers(array $paperIds, array $studentIds, int $subjectId): Collection
    {
        if ($paperIds === [] || $studentIds === []) {
            return collect();
        }

        return ExamMark::query()
            ->whereIn('exam_id', $paperIds)
            ->whereIn('student_id', $studentIds)
            ->where('subject_id', $subjectId)
            ->get(['exam_id', 'student_id', 'subject_id', 'score_raw', 'score_moderated', 'grade_label', 'is_absent'])
            ->map(fn (ExamMark $m) => $m->applyMarksObtained());
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function rankStudents(Collection $rows): Collection
    {
        $sorted = $rows->sortByDesc(fn ($r) => $r['score'] ?? -INF)->values();
        $rank = 0;
        $last = null;
        return $sorted->map(function (array $row, int $idx) use (&$rank, &$last) {
            $score = $row['score'];
            if ($score === null) {
                $row['position'] = null;
            } else {
                if ($last === null || (float) $score !== (float) $last) {
                    $rank = $idx + 1;
                    $last = $score;
                }
                $row['position'] = $rank;
            }

            return $row;
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $students
     * @param  list<array<string, mixed>>  $sittings
     */
    private function payload(
        Classroom $classroom,
        Subject $subject,
        ?int $streamId,
        array $meta,
        Collection $students,
        array $sittings,
        ?string $notice = null,
    ): array {
        $scores = $students->pluck('score')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();
        $passThreshold = ($this->passMarkPercent() / 100.0) * (float) ($meta['max_marks'] ?? 100);
        $passRate = $scores->count()
            ? round(($scores->filter(fn ($s) => $s >= $passThreshold)->count() / $scores->count()) * 100, 2)
            : null;
        $category = ClassroomLevelCategory::resolve($classroom);

        return [
            'meta' => array_merge($meta, [
                'pass_mark_percent' => $this->passMarkPercent(),
                'classroom' => ['id' => (int) $classroom->id, 'name' => $classroom->name],
                'subject' => [
                    'id' => (int) $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                ],
                'stream_id' => $streamId,
                'category_key' => $category,
                'category' => ClassroomLevelCategory::labelFor($category),
                'notice' => $notice,
            ]),
            'summary' => [
                'count' => $scores->count(),
                'mean' => $scores->count() ? round($scores->avg(), 2) : null,
                'max' => $scores->count() ? round($scores->max(), 2) : null,
                'min' => $scores->count() ? round($scores->min(), 2) : null,
                'pass_rate' => $passRate,
                'prev_mean' => null,
                'improvement' => null,
            ],
            'sittings' => $sittings,
            'students' => $students,
        ];
    }

    private function emptyPayload(
        Classroom $classroom,
        Subject $subject,
        ?int $streamId,
        array $meta,
        string $notice
    ): array {
        return $this->payload($classroom, $subject, $streamId, $meta, collect(), [], $notice);
    }

    private function attachImprovement(array $current, ?array $previous, array $prevMeta): array
    {
        $currMean = $current['summary']['mean'] ?? null;
        $prevMean = $previous['summary']['mean'] ?? null;
        $current['summary']['prev_mean'] = is_numeric($prevMean) ? round((float) $prevMean, 2) : null;
        $current['summary']['improvement'] = (is_numeric($currMean) && is_numeric($prevMean))
            ? round((float) $currMean - (float) $prevMean, 2)
            : null;
        $current['meta']['previous_term'] = $prevMeta;
        $current['meta']['has_improvement'] = is_numeric($prevMean);

        $prevByStudent = collect($previous['students'] ?? [])->keyBy('student_id');
        $current['students'] = collect($current['students'] ?? [])->map(function (array $row) use ($prevByStudent) {
            $prev = $prevByStudent->get($row['student_id']);
            $prevScore = is_array($prev) ? ($prev['score'] ?? null) : null;
            $row['prev_score'] = is_numeric($prevScore) ? round((float) $prevScore, 2) : null;
            $row['improvement'] = (is_numeric($row['score'] ?? null) && is_numeric($prevScore))
                ? round((float) $row['score'] - (float) $prevScore, 2)
                : null;

            return $row;
        })->values();

        $current['most_improved_students'] = $this->improvement->topImproved($current['students'], 10);

        return $current;
    }
}
