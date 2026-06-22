<?php

namespace App\Services\Academics;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExamMarkEntryService
{
    public function examAcceptsTeacherEntry(Exam $exam, ?User $user = null): bool
    {
        if (in_array($exam->status, ['open', 'marking'], true)) {
            return true;
        }

        if ($exam->status === 'moderation' && $user && $this->userCanOverrideLockedEntry($user)) {
            return true;
        }

        return false;
    }

    public function userCanOverrideLockedEntry(User $user): bool
    {
        return $user->hasAnyRole([
            'Super Admin', 'super admin', 'Super admin',
            'Admin', 'System Admin',
            'Senior Teacher', 'senior teacher', 'Senior teacher',
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

                $hasScore = ! is_null($scoreInput) && $scoreInput !== '';
                $hasRemark = ! is_null($remarkInput) && trim((string) $remarkInput) !== '';

                if (! $hasScore && ! $hasRemark) {
                    continue;
                }

                $mark = ExamMark::firstOrNew([
                    'exam_id' => $exam->id,
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                ]);

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

                $mark->fill($fill)->save();
                $saved++;
            }

            if ($finalize && $saved > 0 && $exam->canTransitionTo('moderation')) {
                $exam->update(['status' => 'moderation']);
            }
        });

        return ['saved' => $saved, 'skipped' => $skipped];
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

        DB::transaction(function () use ($exam) {
            ExamMark::query()
                ->where('exam_id', $exam->id)
                ->where('status', 'draft')
                ->update(['status' => 'submitted']);

            if ($exam->canTransitionTo('moderation')) {
                $exam->update(['status' => 'moderation']);
            }
        });

        return $exam->fresh();
    }
}
