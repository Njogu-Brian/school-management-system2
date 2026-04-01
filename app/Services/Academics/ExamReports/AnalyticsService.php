<?php

namespace App\Services\Academics\ExamReports;

use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Subject;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Support\Collection;

class AnalyticsService
{
    private function passMarkPercent(): float
    {
        $v = setting('exam_pass_mark_percent', 50);
        $f = is_numeric($v) ? (float) $v : 50.0;
        return max(0.0, min(100.0, $f));
    }

    /**
     * Teacher performance uses classroom_subjects.staff_id as the source of truth for “who teaches what”.
     */
    public function teacherPerformanceForExam(Exam $exam, Classroom $classroom, ?int $streamId = null, ?int $subjectId = null): array
    {
        $subjects = $this->subjectsForContext(
            classroomId: $classroom->id,
            streamId: $streamId,
            academicYearId: $exam->academic_year_id,
            termId: $exam->term_id
        )->when($subjectId, fn ($c) => $c->where('id', $subjectId))->values();

        $students = Student::query()
            ->where('classroom_id', $classroom->id)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->pluck('id');

        $marks = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereIn('student_id', $students)
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get(['student_id', 'subject_id', 'score_raw', 'score_moderated', 'grade_label'])
            ->map(function (ExamMark $m) {
                $m->marks_obtained = $m->score_moderated ?? $m->score_raw ?? null;
                return $m;
            });

        $assignments = ClassroomSubject::query()
            ->where('classroom_id', $classroom->id)
            ->when($streamId, function ($q) use ($streamId) {
                $q->where(function ($q2) use ($streamId) {
                    $q2->whereNull('stream_id')->orWhere('stream_id', $streamId);
                });
            }, fn ($q) => $q->whereNull('stream_id'))
            ->when($exam->academic_year_id, function ($q) use ($exam) {
                $q->where(function ($q2) use ($exam) {
                    $q2->whereNull('academic_year_id')->orWhere('academic_year_id', $exam->academic_year_id);
                });
            })
            ->when($exam->term_id, function ($q) use ($exam) {
                $q->where(function ($q2) use ($exam) {
                    $q2->whereNull('term_id')->orWhere('term_id', $exam->term_id);
                });
            })
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get(['subject_id', 'staff_id']);

        $teacherBySubject = $assignments
            ->filter(fn ($a) => !empty($a->staff_id))
            ->groupBy('subject_id')
            ->map(fn ($g) => $g->first()->staff_id);

        $teacherIds = $teacherBySubject->values()->unique()->filter()->values();
        $teachers = Staff::query()
            ->whereIn('id', $teacherIds)
            ->get(['id', 'first_name', 'last_name', 'middle_name', 'phone_number', 'email'])
            ->keyBy('id');

        $perSubject = $subjects->map(function (Subject $sub) use ($marks, $teacherBySubject, $teachers, $exam) {
            $rows = $marks->where('subject_id', $sub->id)->values();
            $subMarks = $rows->pluck('marks_obtained')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();
            $gradeDistribution = $rows->pluck('grade_label')->map(fn ($g) => $g ?: 'N/A')->countBy()->all();

            $maxMarks = (float) ($exam->max_marks ?? 100);
            $passThreshold = ($this->passMarkPercent() / 100.0) * $maxMarks;
            $passCount = $subMarks->filter(fn ($m) => $m >= $passThreshold)->count();
            $passRate = $subMarks->count() ? round(($passCount / $subMarks->count()) * 100, 2) : null;

            $teacherId = $teacherBySubject->get($sub->id);
            $t = $teacherId ? $teachers->get($teacherId) : null;
            return [
                'subject_id' => $sub->id,
                'subject' => $sub->name,
                'teacher_id' => $teacherId,
                'teacher' => $t ? trim(($t->first_name ?? '') . ' ' . ($t->middle_name ?? '') . ' ' . ($t->last_name ?? '')) : null,
                'count' => $subMarks->count(),
                'mean' => $subMarks->count() ? round($subMarks->avg(), 2) : null,
                'max' => $subMarks->count() ? $subMarks->max() : null,
                'min' => $subMarks->count() ? $subMarks->min() : null,
                'pass_rate' => $passRate,
                'grade_distribution' => $gradeDistribution,
            ];
        })->sortByDesc('mean')->values();

        // Per-teacher rollup (across their assigned subjects in this context)
        $perTeacher = $perSubject
            ->filter(fn ($row) => !empty($row['teacher_id']) && $row['count'] > 0)
            ->groupBy('teacher_id')
            ->map(function ($rows, $teacherId) {
                $means = collect($rows)->pluck('mean')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();
                return [
                    'teacher_id' => (int) $teacherId,
                    'teacher' => collect($rows)->first()['teacher'],
                    'subjects_count' => collect($rows)->count(),
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
                'mode' => 'exam',
                'exam_id' => $exam->id,
                'classroom_id' => $classroom->id,
                'stream_id' => $streamId,
                'subject_id' => $subjectId,
                'pass_mark_percent' => $this->passMarkPercent(),
            ],
            'per_subject' => $perSubject,
            'per_teacher' => $perTeacher,
        ];
    }

    public function subjectPerformanceForExam(Exam $exam, Classroom $classroom, ?int $streamId = null): array
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
            ->pluck('id');

        $marks = ExamMark::query()
            ->where('exam_id', $exam->id)
            ->whereIn('student_id', $students)
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get(['subject_id', 'score_raw', 'score_moderated', 'grade_label'])
            ->map(function (ExamMark $m) {
                $m->marks_obtained = $m->score_moderated ?? $m->score_raw ?? null;
                return $m;
            });

        $perSubject = $subjects->map(function (Subject $sub) use ($marks, $exam) {
            $rows = $marks->where('subject_id', $sub->id)->values();
            $vals = $rows->pluck('marks_obtained')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();
            $gradeDistribution = $rows->pluck('grade_label')->map(fn ($g) => $g ?: 'N/A')->countBy()->all();
            $maxMarks = (float) ($exam->max_marks ?? 100);
            $passThreshold = ($this->passMarkPercent() / 100.0) * $maxMarks;
            $passCount = $vals->filter(fn ($m) => $m >= $passThreshold)->count();
            $passRate = $vals->count() ? round(($passCount / $vals->count()) * 100, 2) : null;
            return [
                'subject_id' => $sub->id,
                'subject' => $sub->name,
                'count' => $vals->count(),
                'mean' => $vals->count() ? round($vals->avg(), 2) : null,
                'max' => $vals->count() ? $vals->max() : null,
                'min' => $vals->count() ? $vals->min() : null,
                'pass_rate' => $passRate,
                'grade_distribution' => $gradeDistribution,
            ];
        })->sortByDesc('mean')->values();

        return [
            'meta' => [
                'mode' => 'exam',
                'exam_id' => $exam->id,
                'classroom_id' => $classroom->id,
                'stream_id' => $streamId,
                'pass_mark_percent' => $this->passMarkPercent(),
            ],
            'subjects' => $perSubject,
        ];
    }

    public function studentInsightsForExam(Exam $exam, Classroom $classroom, ?int $streamId = null): array
    {
        $sheet = (new ClassSheetBuilder())->buildForExam($exam, $classroom, $streamId);
        $rows = collect($sheet['rows']);

        $top = $rows->whereNotNull('total')->sortByDesc('total')->take(10)->values();

        $prevExam = Exam::query()
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('term_id', $exam->term_id)
            ->where('id', '!=', $exam->id)
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at')
            ->firstWhere('starts_on', '<=', $exam->starts_on ?? now());

        $improved = collect();
        if ($prevExam) {
            $prevSheet = (new ClassSheetBuilder())->buildForExam($prevExam, $classroom, $streamId);
            $prevTotals = collect($prevSheet['rows'])->keyBy('student_id')->map(fn ($r) => $r['total']);

            $improved = $rows->map(function ($r) use ($prevTotals) {
                $prev = $prevTotals->get($r['student_id']);
                $curr = $r['total'];
                $delta = ($curr !== null && $prev !== null) ? round(((float) $curr) - ((float) $prev), 2) : null;
                return [
                    'student_id' => $r['student_id'],
                    'admission_number' => $r['admission_number'],
                    'name' => $r['name'],
                    'prev_total' => $prev,
                    'curr_total' => $curr,
                    'improvement' => $delta,
                ];
            })->whereNotNull('improvement')->sortByDesc('improvement')->take(10)->values();
        }

        return [
            'meta' => [
                'mode' => 'exam',
                'exam_id' => $exam->id,
                'prev_exam_id' => $prevExam?->id,
                'classroom_id' => $classroom->id,
                'stream_id' => $streamId,
            ],
            'top_students' => $top,
            'most_improved' => $improved,
        ];
    }

    public function studentInsightsForTerm(int $academicYearId, int $termId, Classroom $classroom, ?int $streamId = null): array
    {
        $sheet = (new ClassSheetBuilder())->buildForTerm($academicYearId, $termId, $classroom, $streamId);
        $rows = collect($sheet['rows']);

        $top = $rows->whereNotNull('total')->sortByDesc('total')->take(10)->values();

        $prevTerm = \App\Models\Term::query()
            ->where('academic_year_id', $academicYearId)
            ->where('id', '!=', $termId)
            ->orderByDesc('id')
            ->firstWhere('id', '<', $termId);

        $improved = collect();
        if ($prevTerm) {
            $prevSheet = (new ClassSheetBuilder())->buildForTerm($academicYearId, $prevTerm->id, $classroom, $streamId);
            $prevTotals = collect($prevSheet['rows'])->keyBy('student_id')->map(fn ($r) => $r['total']);

            $improved = $rows->map(function ($r) use ($prevTotals) {
                $prev = $prevTotals->get($r['student_id']);
                $curr = $r['total'];
                $delta = ($curr !== null && $prev !== null) ? round(((float) $curr) - ((float) $prev), 2) : null;
                return [
                    'student_id' => $r['student_id'],
                    'admission_number' => $r['admission_number'],
                    'name' => $r['name'],
                    'prev_total' => $prev,
                    'curr_total' => $curr,
                    'improvement' => $delta,
                ];
            })->whereNotNull('improvement')->sortByDesc('improvement')->take(10)->values();
        }

        return [
            'meta' => [
                'mode' => 'term',
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'prev_term_id' => $prevTerm?->id,
                'classroom_id' => $classroom->id,
                'stream_id' => $streamId,
            ],
            'top_students' => $top,
            'most_improved' => $improved,
        ];
    }

    /**
     * Student mastery profile: top/bottom subjects by score for an exam or termly context.
     */
    public function masteryProfile(array $sheetPayload, int $topN = 3): array
    {
        $subjects = collect($sheetPayload['subjects'] ?? [])->keyBy('id');
        $rows = collect($sheetPayload['rows'] ?? []);

        $profiles = $rows->map(function ($r) use ($subjects, $topN) {
            $pairs = collect($r['subject_scores'] ?? [])
                ->map(function ($score, $subjectId) use ($subjects) {
                    $sub = $subjects->get((int) $subjectId);
                    return [
                        'subject_id' => (int) $subjectId,
                        'subject' => $sub['name'] ?? (string) $subjectId,
                        'code' => $sub['code'] ?? null,
                        'score' => $score !== null ? (float) $score : null,
                    ];
                })
                ->filter(fn ($x) => $x['score'] !== null)
                ->values();

            $top = $pairs->sortByDesc('score')->take($topN)->values()->all();
            $bottom = $pairs->sortBy('score')->take($topN)->values()->all();

            return [
                'student_id' => $r['student_id'],
                'admission_number' => $r['admission_number'],
                'name' => $r['name'],
                'top_subjects' => $top,
                'bottom_subjects' => $bottom,
            ];
        })->values();

        return [
            'meta' => [
                'mode' => $sheetPayload['meta']['mode'] ?? null,
                'top_n' => $topN,
            ],
            'profiles' => $profiles,
        ];
    }

    private function subjectsForContext(int $classroomId, ?int $streamId, ?int $academicYearId, ?int $termId): Collection
    {
        $assignments = ClassroomSubject::query()
            ->where('classroom_id', $classroomId)
            ->when($streamId, function ($q) use ($streamId) {
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
}

