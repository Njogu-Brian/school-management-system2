<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamSession;
use App\Models\Academics\Subject;
use App\Models\Student;
use Illuminate\Support\Collection;

class ClassSheetBuilder
{
    public function buildForExam(Exam $exam, Classroom $classroom, ?int $streamId = null): array
    {
        $subjects = $this->subjectsForContext(
            classroomId: $classroom->id,
            streamId: $streamId,
            academicYearId: $exam->academic_year_id,
            termId: $exam->term_id
        );

        $students = Student::query()
            ->where('classroom_id', $classroom->id)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get(['id', 'admission_number', 'first_name', 'middle_name', 'last_name', 'classroom_id', 'stream_id']);

        $marks = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get(['id', 'exam_id', 'student_id', 'subject_id', 'score_raw', 'score_moderated'])
            ->map(function (ExamMark $m) {
                $m->marks_obtained = $m->score_moderated ?? $m->score_raw ?? null;
                return $m;
            })
            ->keyBy(fn (ExamMark $m) => $m->student_id . ':' . $m->subject_id);

        $rows = $students->map(function (Student $s) use ($subjects, $marks) {
            $subjectScores = [];
            $total = 0.0;
            $taken = 0;

            foreach ($subjects as $subject) {
                $key = $s->id . ':' . $subject->id;
                $score = $marks->get($key)?->marks_obtained;
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
                ],
                'subjects' => [],
                'rows' => [],
            ];
        }

        $subjects = collect([$subject]);

        $students = Student::query()
            ->where('classroom_id', $classroom->id)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get(['id', 'admission_number', 'first_name', 'middle_name', 'last_name', 'classroom_id', 'stream_id']);

        $marks = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->where('subject_id', $subject->id)
            ->get(['id', 'exam_id', 'student_id', 'subject_id', 'score_raw', 'score_moderated'])
            ->map(function (ExamMark $m) {
                $m->marks_obtained = $m->score_moderated ?? $m->score_raw ?? null;

                return $m;
            })
            ->keyBy(fn (ExamMark $m) => $m->student_id.':'.$m->subject_id);

        $rows = $students->map(function (Student $s) use ($subjects, $marks) {
            $subjectScores = [];
            $total = 0.0;
            $taken = 0;

            foreach ($subjects as $subj) {
                $key = $s->id.':'.$subj->id;
                $score = $marks->get($key)?->marks_obtained;
                $subjectScores[$subj->id] = $score;
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
                ],
                'subjects' => [],
                'rows' => [],
            ];
        }

        $subjects = $papers->map(fn (Exam $e) => $e->subject)->filter()->unique('id')->values();
        $paperIds = $papers->pluck('id');

        $students = Student::query()
            ->where('classroom_id', $classroom->id)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get(['id', 'admission_number', 'first_name', 'middle_name', 'last_name', 'classroom_id', 'stream_id']);

        $marks = ExamMark::query()
            ->whereIn('exam_id', $paperIds)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get(['id', 'exam_id', 'student_id', 'subject_id', 'score_raw', 'score_moderated'])
            ->map(function (ExamMark $m) {
                $m->marks_obtained = $m->score_moderated ?? $m->score_raw ?? null;

                return $m;
            })
            ->keyBy(fn (ExamMark $m) => $m->student_id . ':' . $m->subject_id);

        $rows = $students->map(function (Student $s) use ($subjects, $marks) {
            $subjectScores = [];
            $total = 0.0;
            $taken = 0;

            foreach ($subjects as $subject) {
                $key = $s->id . ':' . $subject->id;
                $score = $marks->get($key)?->marks_obtained;
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

        $students = Student::query()
            ->where('classroom_id', $classroom->id)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get(['id', 'admission_number', 'first_name', 'middle_name', 'last_name', 'classroom_id', 'stream_id']);

        $exams = Exam::query()
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->whereNotNull('subject_id')
            ->orderBy('starts_on')
            ->orderBy('created_at')
            ->get(['id', 'name', 'weight', 'max_marks', 'starts_on']);

        $examIds = $exams->pluck('id');
        $weights = $exams->mapWithKeys(function (Exam $e) {
            $w = $e->weight;
            $w = is_numeric($w) ? (float) $w : 1.0;
            return [$e->id => ($w > 0 ? $w : 1.0)];
        });

        $marks = ExamMark::query()
            ->whereIn('exam_id', $examIds)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get(['exam_id', 'student_id', 'subject_id', 'score_raw', 'score_moderated'])
            ->map(function (ExamMark $m) {
                $m->marks_obtained = $m->score_moderated ?? $m->score_raw ?? null;
                return $m;
            });

        $byStudentSubject = $marks->groupBy(fn (ExamMark $m) => $m->student_id . ':' . $m->subject_id);

        $rows = $students->map(function (Student $s) use ($subjects, $byStudentSubject, $weights) {
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

    private function subjectsForContext(int $classroomId, ?int $streamId, ?int $academicYearId, ?int $termId): Collection
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
            ->when($termId, function ($q) use ($termId) {
                $q->where(function ($q2) use ($termId) {
                    $q2->whereNull('term_id')->orWhere('term_id', $termId);
                });
            })
            ->pluck('subject_id')
            ->unique()
            ->values();

        return Subject::query()
            ->whereIn('id', $assignments)
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
}

