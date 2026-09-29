<?php

namespace App\Services\Academics;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExamMarkEntryService
{
    /** Exam statuses where regular teachers may enter or revise marks. */
    public function teacherEditableStatuses(): array
    {
        return ['marking'];
    }

    /**
     * Exam statuses shown in mark-entry pickers.
     * Published closes teacher entry; seniors/admins may still correct published exams.
     * Locked is closed for everyone except Super Admin / Admin.
     */
    public function entryVisibleStatuses(?User $user = null): array
    {
        $statuses = $this->teacherEditableStatuses();

        if ($user && $this->userCanOverridePublishedEntry($user)) {
            $statuses[] = 'published';
        }

        if ($user && $this->userCanOverrideLockedEntry($user)) {
            $statuses[] = 'locked';
        }

        return array_values(array_unique($statuses));
    }

    public function examAcceptsTeacherEntry(Exam $exam, ?User $user = null): bool
    {
        if (in_array($exam->status, $this->teacherEditableStatuses(), true)) {
            return true;
        }

        if ($user && $exam->status === 'published' && $this->userCanOverridePublishedEntry($user)) {
            return true;
        }

        if ($user && $exam->status === 'locked' && $this->userCanOverrideLockedEntry($user)) {
            return true;
        }

        return false;
    }

    public function userCanOverridePublishedEntry(User $user): bool
    {
        return $user->hasAnyRole([
            'Super Admin', 'super admin', 'Super admin',
            'Admin', 'System Admin',
            'Senior Teacher', 'senior teacher', 'Senior teacher',
            'Deputy Senior Teacher', 'deputy senior teacher', 'Deputy senior teacher',
        ]);
    }

    public function userCanOverrideLockedEntry(User $user): bool
    {
        return $user->hasAnyRole([
            'Super Admin', 'super admin', 'Super admin',
            'Admin', 'System Admin',
        ]);
    }

    /**
     * @param  array<int, array{student_id:int, score?:mixed, subject_remark?:?string, remarks?:?string, marks?:mixed}>  $rows
     * @return array{saved:int, skipped:int}
     */
    public function saveDraftForExam(
        Exam $exam,
        int $classroomId,
        array $rows,
        ?User $user = null,
        bool $finalize = false,
    ): array {
        if (! $this->examAcceptsTeacherEntry($exam, $user)) {
            throw new \RuntimeException('This exam is not open for mark entry.');
        }

        $exam->loadMissing('examType');
        $subjectId = (int) $exam->subject_id;
        if ($subjectId <= 0) {
            throw new \RuntimeException('This exam has no subject configured.');
        }

        $maxMarks = (float) ($exam->examType?->default_max_mark ?? $exam->max_marks ?? 100);
        $minMarks = (float) ($exam->examType?->default_min_mark ?? 0);
        $grading = app(ClassroomGradingService::class);
        $staffId = $user?->staff?->id;

        $saved = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $exam, $classroomId, $subjectId, $rows, $user, $finalize,
            $maxMarks, $minMarks, $grading, $staffId, &$saved, &$skipped
        ) {
            foreach ($rows as $row) {
                $studentId = (int) ($row['student_id'] ?? 0);
                if ($studentId <= 0) {
                    $skipped++;
                    continue;
                }

                $student = Student::query()->find($studentId);
                if (! $student || $student->archive || $student->is_alumni) {
                    $skipped++;
                    continue;
                }
                if ((int) $student->classroom_id !== $classroomId) {
                    $skipped++;
                    continue;
                }
                if ($exam->stream_id && (int) $student->stream_id !== (int) $exam->stream_id) {
                    $skipped++;
                    continue;
                }
                if ($user && $user->hasTeacherLikeRole()) {
                    $scope = Student::query()->where('id', $studentId)->where('archive', 0)->where('is_alumni', false);
                    $user->applyTeacherStudentFilter($scope);
                    if (! $scope->exists()) {
                        $skipped++;
                        continue;
                    }
                }

                $scoreInput = $row['score'] ?? $row['marks'] ?? null;
                $remarkInput = $row['subject_remark'] ?? $row['remarks'] ?? null;
                $isAbsent = filter_var($row['is_absent'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $hasScore = ! is_null($scoreInput) && $scoreInput !== '';
                $hasRemark = ! is_null($remarkInput) && trim((string) $remarkInput) !== '';

                if (! $hasScore && ! $hasRemark && ! $isAbsent) {
                    continue;
                }

                $mark = ExamMark::firstOrNew([
                    'exam_id' => $exam->id,
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                ]);

                if ($isAbsent) {
                    $fill = [
                        'teacher_id' => $staffId ?? $mark->teacher_id,
                        'status' => $finalize ? 'submitted' : 'draft',
                        'is_absent' => true,
                        'score_raw' => null,
                        'final_score' => null,
                        'score_moderated' => null,
                        'grade_label' => 'ABS',
                        'pl_level' => null,
                    ];

                    if ($hasRemark) {
                        $fill['subject_remark'] = trim((string) $remarkInput);
                    }

                    $mark->fill($fill);
                    app(ExamMarkEntryAuditService::class)->recordMarkSave(
                        $mark,
                        $finalize ? 'submitted_absent' : 'draft_absent',
                        $user,
                        $finalize
                    );
                    $mark->save();
                    $saved++;
                    continue;
                }

                $score = null;
                if ($hasScore) {
                    if (! is_numeric($scoreInput)) {
                        $skipped++;
                        continue;
                    }
                    $score = (float) $scoreInput;
                    if ($score < $minMarks || $score > $maxMarks) {
                        $skipped++;
                        continue;
                    }
                }

                $fill = [
                    'teacher_id' => $staffId ?? $mark->teacher_id,
                    'status' => $finalize ? 'submitted' : 'draft',
                    'is_absent' => false,
                ];

                if ($hasScore) {
                    $g = $grading->gradeForRawScore($score, $maxMarks, $classroomId);
                    $fill['score_raw'] = $score;
                    $fill['final_score'] = $score;
                    $fill['grade_label'] = $g['label'] ?? null;
                    $fill['pl_level'] = $g['points'] ?? null;
                }

                if ($hasRemark) {
                    $fill['subject_remark'] = trim((string) $remarkInput);
                }

                $previousScore = $mark->exists && ! $mark->is_absent && $mark->score_raw !== null
                    ? (float) $mark->score_raw
                    : null;

                $mark->fill($fill);
                app(ExamMarkEntryAuditService::class)->recordMarkSave(
                    $mark,
                    $finalize ? 'submitted' : 'draft_saved',
                    $user,
                    $finalize
                );
                $mark->save();
                $saved++;

                if ($hasScore && $previousScore !== null && abs($previousScore - (float) $score) > 0.0001) {
                    $this->notifyMarkChange(
                        $exam,
                        $student,
                        $previousScore,
                        (float) $score,
                        $user
                    );
                }
            }

            if ($finalize && $saved > 0) {
                app(ExamMarkEntryAuditService::class)->recordExamSubmission($exam, $user);
            }
        });

        return ['saved' => $saved, 'skipped' => $skipped];
    }

    protected function notifyMarkChange(
        Exam $exam,
        Student $student,
        float $from,
        float $to,
        ?User $user = null,
    ): void {
        try {
            $exam->loadMissing(['subject', 'classroom']);
            $editor = $user?->name ?? 'A teacher';
            $subject = $exam->subject?->name ?? 'Subject';
            $class = $exam->classroom?->name ?? 'Class';
            $studentName = person_display_name($student, 'full');
            $title = 'Exam mark changed';
            $message = "{$editor} changed {$studentName}'s {$subject} mark ({$exam->name}, {$class}) from {$from} to {$to}.";

            app(\App\Services\SystemAlertService::class)->raiseForRoles(
                [
                    'Super Admin', 'Admin', 'System Admin',
                    'Senior Teacher', 'senior teacher', 'Senior teacher',
                    'Deputy Senior Teacher', 'deputy senior teacher',
                ],
                $title,
                $message,
                'academics',
                'warning',
                'mark_change_'.sha1($exam->id.'|'.$student->id.'|'.$from.'|'.$to.'|'.now()->format('YmdHi')),
                '/academics/exams/'.$exam->id,
                [
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'from' => $from,
                    'to' => $to,
                    'edited_by' => $user?->id,
                ],
                true,
                false
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to raise mark-change alert', [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<array{student_id:int, exam_id:int, marks?:mixed, remarks?:?string, score?:mixed, subject_remark?:?string}>  $entries
     */
    public function saveDraftMatrixEntries(
        int $examTypeId,
        int $classroomId,
        ?int $streamId,
        array $entries,
        ?User $user = null,
        array $finalizeExamIds = [],
    ): array {
        $grouped = [];
        foreach ($entries as $entry) {
            $examId = (int) ($entry['exam_id'] ?? 0);
            if ($examId <= 0) {
                continue;
            }
            $grouped[$examId][] = [
                'student_id' => (int) $entry['student_id'],
                'score' => $entry['marks'] ?? $entry['score'] ?? null,
                'subject_remark' => $entry['remarks'] ?? $entry['subject_remark'] ?? null,
                'is_absent' => $entry['is_absent'] ?? false,
            ];
        }

        $totalSaved = 0;
        $totalSkipped = 0;
        $submittedExams = [];

        foreach ($grouped as $examId => $rows) {
            $exam = Exam::query()->find($examId);
            if (! $exam || (int) $exam->exam_type_id !== $examTypeId || (int) $exam->classroom_id !== $classroomId) {
                $totalSkipped += count($rows);
                continue;
            }
            if ($streamId && $exam->stream_id && (int) $exam->stream_id !== $streamId) {
                $totalSkipped += count($rows);
                continue;
            }

            $finalize = in_array((int) $examId, array_map('intval', (array) $finalizeExamIds), true);
            $result = $this->saveDraftForExam($exam, $classroomId, $rows, $user, $finalize);
            $totalSaved += $result['saved'];
            $totalSkipped += $result['skipped'];
            if ($finalize) {
                $submittedExams[] = (int) $examId;
            }
        }

        foreach (array_map('intval', (array) $finalizeExamIds) as $examId) {
            if (in_array($examId, $submittedExams, true)) {
                continue;
            }
            $exam = Exam::query()->find($examId);
            if (! $exam || (int) $exam->exam_type_id !== $examTypeId || (int) $exam->classroom_id !== $classroomId) {
                continue;
            }
            if ($streamId && $exam->stream_id && (int) $exam->stream_id !== $streamId) {
                continue;
            }
            if (! $this->examAcceptsTeacherEntry($exam, $user)) {
                continue;
            }
            $this->submitExam($exam, $user);
            $submittedExams[] = $examId;
        }

        return [
            'saved' => $totalSaved,
            'skipped' => $totalSkipped,
            'submitted_exam_ids' => $submittedExams,
        ];
    }

    public function submitExam(Exam $exam, ?User $user = null): Exam
    {
        if (! $this->examAcceptsTeacherEntry($exam, $user)) {
            throw new \RuntimeException('This exam cannot be submitted for review.');
        }

        DB::transaction(function () use ($exam, $user) {
            ExamMark::query()
                ->where('exam_id', $exam->id)
                ->where('status', 'draft')
                ->get()
                ->each(function (ExamMark $mark) use ($user) {
                    $mark->status = 'submitted';
                    app(ExamMarkEntryAuditService::class)->recordMarkSave($mark, 'exam_submitted', $user, true);
                    $mark->save();
                });

            app(ExamMarkEntryAuditService::class)->recordExamSubmission($exam, $user);
        });

        return $exam->fresh();
    }
}
