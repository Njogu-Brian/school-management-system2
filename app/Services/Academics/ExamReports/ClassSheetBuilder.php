<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamSession;
use App\Models\Academics\Stream;
use App\Models\Academics\Subject;
use App\Models\Student;
use App\Models\StudentAcademicHistory;
use App\Services\Academics\StudentPlacementService;
use Illuminate\Support\Collection;

class ClassSheetBuilder
{
    public function __construct(
        private readonly TermScopeResolver $terms = new TermScopeResolver(),
        private readonly ExamScopeResolver $examScope = new ExamScopeResolver(),
    ) {}

    public function buildForExam(Exam $exam, Classroom $classroom, ?int $streamId = null): array
    {
        $subjects = $this->subjectsForContext(
            classroomId: $classroom->id,
            streamId: $streamId,
            academicYearId: $exam->academic_year_id,
            termId: $exam->term_id
        );

        $students = $this->studentsForExamContext(
            $classroom,
            $streamId,
            academicYearId: $exam->academic_year_id ? (int) $exam->academic_year_id : null,
            termId: $exam->term_id ? (int) $exam->term_id : null,
            examIds: [$exam->id],
        );
        $streamNames = $this->streamNameMapForClassroom($classroom->id);

        $marks = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get(['id', 'exam_id', 'student_id', 'subject_id', 'score_raw', 'score_moderated', 'is_absent'])
            ->map(fn (ExamMark $m) => $m->applyMarksObtained())
            ->keyBy(fn (ExamMark $m) => $m->student_id . ':' . $m->subject_id);

        $rows = $students->map(function (Student $s) use ($subjects, $marks, $streamNames) {
            $subjectScores = [];
            $total = 0.0;
            $taken = 0;

            foreach ($subjects as $subject) {
                $key = $s->id . ':' . $subject->id;
                $mark = $marks->get($key);
                $averageScore = $mark?->scoreForAverage();
                $subjectScores[$subject->id] = $mark?->displayScore();
                if ($averageScore !== null) {
                    $total += $averageScore;
                    $taken++;
                }
            }

            $avg = $taken > 0 ? ($total / $taken) : null;

            return [
                'student_id' => $s->id,
                'admission_number' => $s->admission_number,
                'name' => $s->name,
                'stream_id' => $s->stream_id,
                'stream_name' => $this->studentStreamName($s, $streamNames),
                'subject_scores' => $subjectScores,
                'total' => $taken > 0 ? round($total, 2) : null,
                'average' => $avg !== null ? round($avg, 2) : null,
                'subjects_taken' => $taken,
            ];
        });

        $subjectPositions = $this->subjectPositions($rows, $subjects->pluck('id')->all());
        $rows = $rows->map(function (array $row) use ($subjectPositions) {
            $row['subject_positions'] = $subjectPositions[$row['student_id']] ?? [];
            return $row;
        });

        $rows = (new RowRanker())->rankByTotal($rows);

        // If stream filter is applied, also compute class-wide position for context.
        if ($streamId) {
            $classwide = $this->buildForExam($exam, $classroom, null);
            $classPos = collect($classwide['rows'])->keyBy('student_id')->map(fn ($r) => $r['position']);
            $rows = $rows->map(function ($row) use ($classPos) {
                $row['stream_position'] = $row['position'];
                $row['class_position'] = $classPos->get($row['student_id']);
                return $row;
            });
        } else {
            $rows = $rows->map(function ($row) {
                $row['class_position'] = $row['position'];
                $row['stream_position'] = null;
                return $row;
            });
        }

        return [
            'meta' => [
                'mode' => 'exam',
                'exam' => [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'academic_year_id' => $exam->academic_year_id,
                    'term_id' => $exam->term_id,
                ],
                'classroom' => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                ],
                'stream_id' => $streamId,
                'stream_name' => $this->streamName($streamId),
                'stream_names_by_id' => $streamNames,
            ],
            'subjects' => $subjects->map(fn (Subject $sub) => [
                'id' => $sub->id,
                'name' => $sub->name,
                'code' => $sub->code,
            ])->values(),
            'rows' => $rows->values(),
        ];
    }

    /**
     * Mark sheet for one subject paper (single exam row), same layout as multi-subject but one column.
     */
    public function buildForSingleSubjectExam(Exam $exam, Classroom $classroom, ?int $streamId = null): array
    {
        $exam->loadMissing('subject');
        $subject = $exam->subject;
        if (! $subject) {
            return [
                'meta' => [
                    'mode' => 'subject_paper',
                    'exam' => [
                        'id' => $exam->id,
                        'name' => $exam->name,
                        'academic_year_id' => $exam->academic_year_id,
                        'term_id' => $exam->term_id,
                    ],
                    'classroom' => [
                        'id' => $classroom->id,
                        'name' => $classroom->name,
                    ],
                    'stream_id' => $streamId,
                    'stream_name' => $this->streamName($streamId),
                    'stream_names_by_id' => $this->streamNameMapForClassroom($classroom->id),
                ],
                'subjects' => [],
                'rows' => [],
            ];
        }

        $subjects = collect([$subject]);

        $students = $this->studentsForExamContext(
            $classroom,
            $streamId,
            academicYearId: $exam->academic_year_id ? (int) $exam->academic_year_id : null,
            termId: $exam->term_id ? (int) $exam->term_id : null,
            examIds: [$exam->id],
        );
        $streamNames = $this->streamNameMapForClassroom($classroom->id);

        $marks = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->where('subject_id', $subject->id)
            ->get(['id', 'exam_id', 'student_id', 'subject_id', 'score_raw', 'score_moderated', 'is_absent'])
            ->map(fn (ExamMark $m) => $m->applyMarksObtained())
            ->keyBy(fn (ExamMark $m) => $m->student_id.':'.$m->subject_id);

        $rows = $students->map(function (Student $s) use ($subjects, $marks, $streamNames) {
            $subjectScores = [];
            $total = 0.0;
            $taken = 0;

            foreach ($subjects as $subj) {
                $key = $s->id.':'.$subj->id;
                $mark = $marks->get($key);
                $averageScore = $mark?->scoreForAverage();
                $subjectScores[$subj->id] = $mark?->displayScore();
                if ($averageScore !== null) {
                    $total += $averageScore;
                    $taken++;
                }
            }

            $avg = $taken > 0 ? ($total / $taken) : null;

            return [
                'student_id' => $s->id,
                'admission_number' => $s->admission_number,
                'name' => $s->name,
                'stream_id' => $s->stream_id,
                'stream_name' => $this->studentStreamName($s, $streamNames),
                'subject_scores' => $subjectScores,
                'total' => $taken > 0 ? round($total, 2) : null,
                'average' => $avg !== null ? round($avg, 2) : null,
                'subjects_taken' => $taken,
            ];
        });

        $subjectPositions = $this->subjectPositions($rows, $subjects->pluck('id')->all());
        $rows = $rows->map(function (array $row) use ($subjectPositions) {
            $row['subject_positions'] = $subjectPositions[$row['student_id']] ?? [];

            return $row;
        });

        $rows = (new RowRanker())->rankByTotal($rows);

        if ($streamId) {
            $classwide = $this->buildForSingleSubjectExam($exam, $classroom, null);
            $classPos = collect($classwide['rows'])->keyBy('student_id')->map(fn ($r) => $r['position']);
            $rows = $rows->map(function ($row) use ($classPos) {
                $row['stream_position'] = $row['position'];
                $row['class_position'] = $classPos->get($row['student_id']);

                return $row;
            });
        } else {
            $rows = $rows->map(function ($row) {
                $row['class_position'] = $row['position'];
                $row['stream_position'] = null;

                return $row;
            });
        }

        return [
            'meta' => [
                'mode' => 'subject_paper',
                'exam' => [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'academic_year_id' => $exam->academic_year_id,
                    'term_id' => $exam->term_id,
                ],
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                ],
                'classroom' => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                ],
                'stream_id' => $streamId,
                'stream_name' => $this->streamName($streamId),
                'stream_names_by_id' => $streamNames,
            ],
            'subjects' => $subjects->map(fn (Subject $sub) => [
                'id' => $sub->id,
                'name' => $sub->name,
                'code' => $sub->code,
            ])->values(),
            'rows' => $rows->values(),
        ];
    }

    /**
     * Full class mark sheet for one exam sitting (all subject papers under the same exam type / class / stream).
     */
    public function buildForExamSession(ExamSession $session, Classroom $classroom, ?int $streamId = null): array
    {
        if ((int) $session->classroom_id !== (int) $classroom->id) {
            throw new \InvalidArgumentException('Classroom does not match this exam session.');
        }

        $papers = Exam::query()
            ->where('exam_session_id', $session->id)
            ->whereNotNull('subject_id')
            ->with('subject')
            ->orderBy('id')
            ->get();

        if ($papers->isEmpty()) {
            return [
                'meta' => [
                    'mode' => 'exam_session',
                    'exam_session' => [
                        'id' => $session->id,
                        'name' => $session->name,
                    ],
                    'classroom' => ['id' => $classroom->id, 'name' => $classroom->name],
                    'stream_id' => $streamId,
                    'stream_name' => $this->streamName($streamId),
                    'stream_names_by_id' => $this->streamNameMapForClassroom($classroom->id),
                ],
                'subjects' => [],
                'rows' => [],
            ];
        }

        $subjects = $papers->map(fn (Exam $e) => $e->subject)->filter()->unique('id')->values();
        $paperIds = $papers->pluck('id');

        $students = $this->studentsForExamContext(
            $classroom,
            $streamId,
            academicYearId: $session->academic_year_id ? (int) $session->academic_year_id : null,
            termId: $session->term_id ? (int) $session->term_id : null,
            examIds: $paperIds->all(),
        );
        $streamNames = $this->streamNameMapForClassroom($classroom->id);

        $marks = ExamMark::query()
            ->whereIn('exam_id', $paperIds)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get(['id', 'exam_id', 'student_id', 'subject_id', 'score_raw', 'score_moderated', 'is_absent'])
            ->map(fn (ExamMark $m) => $m->applyMarksObtained())
            ->keyBy(fn (ExamMark $m) => $m->student_id . ':' . $m->subject_id);

        $rows = $students->map(function (Student $s) use ($subjects, $marks, $streamNames) {
            $subjectScores = [];
            $total = 0.0;
            $taken = 0;

            foreach ($subjects as $subject) {
                $key = $s->id . ':' . $subject->id;
                $mark = $marks->get($key);
                $averageScore = $mark?->scoreForAverage();
                $subjectScores[$subject->id] = $mark?->displayScore();
                if ($averageScore !== null) {
                    $total += $averageScore;
                    $taken++;
                }
            }

            $avg = $taken > 0 ? ($total / $taken) : null;

            return [
                'student_id' => $s->id,
                'admission_number' => $s->admission_number,
                'name' => $s->name,
                'stream_id' => $s->stream_id,
                'stream_name' => $this->studentStreamName($s, $streamNames),
                'subject_scores' => $subjectScores,
                'total' => $taken > 0 ? round($total, 2) : null,
                'average' => $avg !== null ? round($avg, 2) : null,
                'subjects_taken' => $taken,
            ];
        });

        $subjectPositions = $this->subjectPositions($rows, $subjects->pluck('id')->all());
        $rows = $rows->map(function (array $row) use ($subjectPositions) {
            $row['subject_positions'] = $subjectPositions[$row['student_id']] ?? [];

            return $row;
        });

        $rows = (new RowRanker())->rankByTotal($rows);

        if ($streamId) {
            $classwide = $this->buildForExamSession($session, $classroom, null);
            $classPos = collect($classwide['rows'])->keyBy('student_id')->map(fn ($r) => $r['position']);
            $rows = $rows->map(function ($row) use ($classPos) {
                $row['stream_position'] = $row['position'];
                $row['class_position'] = $classPos->get($row['student_id']);

                return $row;
            });
        } else {
            $rows = $rows->map(function ($row) {
                $row['class_position'] = $row['position'];
                $row['stream_position'] = null;

                return $row;
            });
        }

        return [
            'meta' => [
                'mode' => 'exam_session',
                'exam_session' => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'exam_type_id' => $session->exam_type_id,
                    'academic_year_id' => $session->academic_year_id,
                    'term_id' => $session->term_id,
                ],
                'classroom' => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                ],
                'stream_id' => $streamId,
                'stream_name' => $this->streamName($streamId),
                'stream_names_by_id' => $streamNames,
                'paper_exam_ids' => $paperIds->values(),
            ],
            'subjects' => $subjects->map(fn (Subject $sub) => [
                'id' => $sub->id,
                'name' => $sub->name,
                'code' => $sub->code,
            ])->values(),
            'rows' => $rows->values(),
        ];
    }

    /**
     * Termly sheet = simple average across exams in term (equal weight), per subject.
     */
    public function buildForTerm(int $academicYearId, int $termId, Classroom $classroom, ?int $streamId = null): array
    {
        $subjects = $this->subjectsForContext(
            classroomId: $classroom->id,
            streamId: $streamId,
            academicYearId: $academicYearId,
            termId: $termId
        );

        $exams = $this->examScope->papersForTerm($academicYearId, $termId, $classroom->id, $streamId);

        $examIds = $exams->pluck('id');
        $students = $this->studentsForExamContext(
            $classroom,
            $streamId,
            academicYearId: $academicYearId,
            termId: $termId,
            examIds: $examIds->all(),
        );
        $streamNames = $this->streamNameMapForClassroom($classroom->id);

        $weights = $exams->mapWithKeys(function (Exam $e) {
            $w = $e->weight;
            $w = is_numeric($w) ? (float) $w : 1.0;
            return [$e->id => ($w > 0 ? $w : 1.0)];
        });

        $marks = ExamMark::query()
            ->whereIn('exam_id', $examIds)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get(['exam_id', 'student_id', 'subject_id', 'score_raw', 'score_moderated', 'is_absent'])
            ->map(fn (ExamMark $m) => $m->applyMarksObtained());

        $byStudentSubject = $marks->groupBy(fn (ExamMark $m) => $m->student_id . ':' . $m->subject_id);

        $rows = $students->map(function (Student $s) use ($subjects, $byStudentSubject, $weights, $streamNames) {
            $subjectScores = [];
            $total = 0.0;
            $taken = 0;

            foreach ($subjects as $subject) {
                $key = $s->id . ':' . $subject->id;
                $items = $byStudentSubject->get($key, collect());

                // Weighted average across exams (falls back to equal when all weights = 1)
                $sumW = 0.0;
                $sum = 0.0;
                foreach ($items as $m) {
                    $v = $m->marks_obtained;
                    if ($v === null) continue;
                    $w = (float) ($weights->get($m->exam_id) ?? 1.0);
                    $sumW += $w;
                    $sum += ((float) $v) * $w;
                }
                $score = $sumW > 0 ? round($sum / $sumW, 2) : null;

                $subjectScores[$subject->id] = $score;
                if ($score !== null) {
                    $total += (float) $score;
                    $taken++;
                }
            }

            $avg = $taken > 0 ? ($total / $taken) : null;

            return [
                'student_id' => $s->id,
                'admission_number' => $s->admission_number,
                'name' => $s->name,
                'stream_id' => $s->stream_id,
                'stream_name' => $this->studentStreamName($s, $streamNames),
                'subject_scores' => $subjectScores,
                'total' => $taken > 0 ? round($total, 2) : null,
                'average' => $avg !== null ? round($avg, 2) : null,
                'subjects_taken' => $taken,
            ];
        });

        $subjectPositions = $this->subjectPositions($rows, $subjects->pluck('id')->all());
        $rows = $rows->map(function (array $row) use ($subjectPositions) {
            $row['subject_positions'] = $subjectPositions[$row['student_id']] ?? [];
            return $row;
        });

        $rows = (new RowRanker())->rankByTotal($rows);

        if ($streamId) {
            $classwide = $this->buildForTerm($academicYearId, $termId, $classroom, null);
            $classPos = collect($classwide['rows'])->keyBy('student_id')->map(fn ($r) => $r['position']);
            $rows = $rows->map(function ($row) use ($classPos) {
                $row['stream_position'] = $row['position'];
                $row['class_position'] = $classPos->get($row['student_id']);
                return $row;
            });
        } else {
            $rows = $rows->map(function ($row) {
                $row['class_position'] = $row['position'];
                $row['stream_position'] = null;
                return $row;
            });
        }

        return [
            'meta' => [
                'mode' => 'term',
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'classroom' => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                ],
                'stream_id' => $streamId,
                'stream_name' => $this->streamName($streamId),
                'stream_names_by_id' => $streamNames,
                'exam_ids' => $examIds->values(),
                'exam_weights' => $weights->all(),
            ],
            'subjects' => $subjects->map(fn (Subject $sub) => [
                'id' => $sub->id,
                'name' => $sub->name,
                'code' => $sub->code,
            ])->values(),
            'rows' => $rows->values(),
        ];
    }

    private function subjectsForContext(int $classroomId, ?int $streamId, ?int $academicYearId, ?int $termId, ?array $limitSubjectIds = null): Collection
    {
        $assignments = ClassroomSubject::query()
            ->where('classroom_id', $classroomId)
            ->when($streamId, function ($q) use ($streamId) {
                // include both general (null) + stream-specific assignments
                $q->where(function ($q2) use ($streamId) {
                    $q2->whereNull('stream_id')->orWhere('stream_id', $streamId);
                });
            }, fn ($q) => $q->whereNull('stream_id'))
            ->when($academicYearId, function ($q) use ($academicYearId) {
                $q->where(function ($q2) use ($academicYearId) {
                    $q2->whereNull('academic_year_id')->orWhere('academic_year_id', $academicYearId);
                });
            })
            ->when($termId, function ($q) use ($termId, $academicYearId, $classroomId, $streamId) {
                if ($academicYearId) {
                    $termIds = $this->terms->termIdsForScope($termId, $academicYearId, null, $classroomId, $streamId);
                    $q->where(function ($q2) use ($termIds) {
                        $q2->whereNull('term_id')->orWhereIn('term_id', $termIds);
                    });
                } else {
                    $q->where(function ($q2) use ($termId) {
                        $q2->whereNull('term_id')->orWhere('term_id', $termId);
                    });
                }
            })
            ->when($limitSubjectIds !== null, fn ($q) => $q->whereIn('subject_id', $limitSubjectIds))
            ->pluck('subject_id')
            ->unique()
            ->values();

        $fromPapers = collect();
        if ($academicYearId && $termId) {
            $fromPapers = $this->examScope->papersForTerm($academicYearId, $termId, $classroomId, $streamId)
                ->pluck('subject_id')
                ->filter()
                ->unique()
                ->values();
        }

        $subjectIds = $assignments->merge($fromPapers)->unique()->values();
        if ($limitSubjectIds !== null) {
            $subjectIds = $subjectIds->intersect($limitSubjectIds)->values();
        }

        if ($subjectIds->isEmpty()) {
            return collect();
        }

        return Subject::query()
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function subjectPositions(Collection $rows, array $subjectIds): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[$row['student_id']] = [];
        }

        foreach ($subjectIds as $subjectId) {
            $rankable = $rows
                ->map(function ($r) use ($subjectId) {
                    return [
                        'student_id' => $r['student_id'],
                        'admission_number' => $r['admission_number'] ?? '',
                        'score' => $r['subject_scores'][$subjectId] ?? null,
                    ];
                })
                ->filter(fn ($r) => $r['score'] !== null)
                ->sort(function ($a, $b) {
                    if ($a['score'] === $b['score']) {
                        return strcmp((string) $a['admission_number'], (string) $b['admission_number']);
                    }
                    return $a['score'] < $b['score'] ? 1 : -1;
                })
                ->values();

            $pos = 0;
            $prevScore = null;
            $prevPos = null;
            foreach ($rankable as $item) {
                $pos++;
                $score = $item['score'];
                $studentId = $item['student_id'];
                $thisPos = ($prevScore !== null && $score === $prevScore) ? $prevPos : $pos;
                $out[$studentId][$subjectId] = $thisPos;
                $prevScore = $score;
                $prevPos = $thisPos;
            }
        }

        return $out;
    }

    // ranking delegated to RowRanker

    /**
     * Resolve the roster for a class sheet.
     *
     * After promotion, marks still belong to the students who sat the papers even
     * though students.classroom_id has moved on. Prefer that historical cohort
     * when marks exist outside the current class; otherwise use the live roster.
     *
     * @param  list<int|string>|Collection<int, int|string>  $examIds
     */
    private function studentsForExamContext(
        Classroom $classroom,
        ?int $streamId,
        ?int $academicYearId = null,
        ?int $termId = null,
        iterable $examIds = [],
    ): Collection {
        $examIdList = collect($examIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $placement = app(StudentPlacementService::class);
        $year = null;
        if ($academicYearId) {
            $year = (int) (\App\Models\AcademicYear::find($academicYearId)?->year ?? 0) ?: null;
        }
        $year = $year ?: $placement->calendarYear();

        $currentIds = $placement->studentsInClassroom((int) $classroom->id, $year, $streamId)->pluck('id');

        $markIds = $examIdList->isEmpty()
            ? collect()
            : ExamMark::query()
                ->whereIn('exam_id', $examIdList)
                ->distinct()
                ->pluck('student_id');

        $historyIds = collect();
        if ($academicYearId) {
            $historyIds = StudentAcademicHistory::query()
                ->where('classroom_id', $classroom->id)
                ->where('academic_year_id', $academicYearId)
                ->when($termId, function ($q) use ($termId) {
                    $q->where(function ($inner) use ($termId) {
                        $inner->where('term_id', $termId)->orWhereNull('term_id');
                    });
                })
                ->pluck('student_id');
        }

        // Marks for students no longer in this class => historical sitting.
        if ($markIds->diff($currentIds)->isNotEmpty()) {
            $ids = $markIds->merge($historyIds)->unique()->filter()->values();
        } else {
            $ids = $currentIds->merge($markIds)->merge($historyIds)->unique()->filter()->values();
        }

        if ($ids->isEmpty()) {
            $ids = $currentIds;
        }

        if ($streamId) {
            $historyStreamIds = StudentAcademicHistory::query()
                ->where('classroom_id', $classroom->id)
                ->where('stream_id', $streamId)
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->when($termId, function ($q) use ($termId) {
                    $q->where(function ($inner) use ($termId) {
                        $inner->where('term_id', $termId)->orWhereNull('term_id');
                    });
                })
                ->pluck('student_id');

            $ids = Student::query()
                ->whereIn('id', $ids)
                ->where(function ($q) use ($classroom, $streamId, $historyStreamIds) {
                    $q->where(function ($cur) use ($classroom, $streamId) {
                        $cur->where('classroom_id', $classroom->id)
                            ->where('stream_id', $streamId);
                    })->orWhereIn('id', $historyStreamIds);
                })
                ->pluck('id');
        }

        return Student::query()
            ->with('stream')
            ->whereIn('id', $ids)
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get(['id', 'admission_number', 'first_name', 'middle_name', 'last_name', 'classroom_id', 'stream_id']);
    }

    private function studentStreamName(Student $s, array $streamNames): ?string
    {
        $name = $s->stream?->name;

        if ($name) {
            return $name;
        }

        return $streamNames[$s->stream_id] ?? null;
    }

    /** @return array<int, string> */
    private function streamNameMapForClassroom(int $classroomId): array
    {
        return Stream::query()
            ->where('classroom_id', $classroomId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function streamName(?int $streamId): ?string
    {
        if (! $streamId) {
            return null;
        }

        return Stream::query()->whereKey($streamId)->value('name');
    }
}

