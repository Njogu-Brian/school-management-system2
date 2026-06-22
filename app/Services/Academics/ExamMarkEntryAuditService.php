<?php

namespace App\Services\Academics;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\User;
use Illuminate\Support\Collection;

class ExamMarkEntryAuditService
{
    public function recordMarkSave(ExamMark $mark, string $action, ?User $user = null, bool $submitted = false): void
    {
        $audit = is_array($mark->audit) ? $mark->audit : [];
        $history = is_array($audit['history'] ?? null) ? $audit['history'] : [];

        $entry = [
            'action' => $action,
            'at' => now()->toIso8601String(),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'status' => $mark->status,
            'is_absent' => (bool) $mark->is_absent,
            'score' => $mark->is_absent ? null : $mark->score_raw,
        ];

        $history[] = $entry;
        $audit['history'] = array_slice($history, -25);
        $audit['last'] = $entry;
        $mark->audit = $audit;

        if ($submitted || $mark->status === 'submitted') {
            $mark->submitted_at = now();
            $mark->submitted_by = $user?->id;
        }
    }

    public function recordExamSubmission(Exam $exam, ?User $user = null, bool $transitionToModeration = true): void
    {
        $exam->marking_submitted_at = now();
        $exam->marking_submitted_by = $user?->id;
        if ($transitionToModeration && $exam->canTransitionTo('moderation')) {
            $exam->status = 'moderation';
        }
        $exam->save();
    }

    /**
     * @param  Collection<int, int>|array<int, int>  $studentIds
     * @return array<string, mixed>
     */
    public function summaryForExam(Exam $exam, int $subjectId, Collection|array $studentIds): array
    {
        $studentIds = collect($studentIds)->map(fn ($id) => (int) $id)->filter()->values();

        $marks = ExamMark::query()
            ->with(['student:id,first_name,last_name,admission_number', 'teacher:id,first_name,last_name'])
            ->where('exam_id', $exam->id)
            ->where('subject_id', $subjectId)
            ->when($studentIds->isNotEmpty(), fn ($q) => $q->whereIn('student_id', $studentIds))
            ->orderByDesc('updated_at')
            ->get();

        $submitter = $exam->marking_submitted_by
            ? User::query()->find($exam->marking_submitted_by, ['id', 'name'])
            : null;

        $recent = $marks->take(20)->map(function (ExamMark $mark) {
            $last = is_array($mark->audit) ? ($mark->audit['last'] ?? null) : null;
            $actorId = $mark->submitted_by ?? $mark->updated_by ?? $mark->entered_by;
            $actorName = $last['user_name'] ?? null;

            if (! $actorName && $actorId) {
                $actorName = User::query()->where('id', $actorId)->value('name');
            }

            return [
                'student_id' => (int) $mark->student_id,
                'student_name' => $mark->student?->full_name,
                'admission_number' => $mark->student?->admission_number,
                'status' => $mark->status,
                'is_absent' => (bool) $mark->is_absent,
                'score' => $mark->is_absent ? 'ABS' : $mark->score_raw,
                'entered_at' => $mark->created_at?->toIso8601String(),
                'updated_at' => $mark->updated_at?->toIso8601String(),
                'submitted_at' => $mark->submitted_at?->toIso8601String(),
                'last_action' => $last['action'] ?? null,
                'last_action_at' => $last['at'] ?? $mark->updated_at?->toIso8601String(),
                'last_action_by' => $actorName,
            ];
        })->values();

        return [
            'exam' => [
                'id' => (int) $exam->id,
                'name' => $exam->name,
                'status' => $exam->status,
                'marking_submitted_at' => $exam->marking_submitted_at?->toIso8601String(),
                'marking_submitted_by' => $submitter?->name,
            ],
            'counts' => [
                'total_marks' => $marks->count(),
                'draft' => $marks->where('status', 'draft')->count(),
                'submitted' => $marks->where('status', 'submitted')->count(),
                'approved' => $marks->where('status', 'approved')->count(),
                'absent' => $marks->where('is_absent', true)->count(),
            ],
            'recent_activity' => $recent,
        ];
    }

    /**
     * @param  Collection<int, \App\Models\Academics\Exam>  $exams
     * @return array<int, array<string, mixed>>
     */
    public function summariesForExams(Collection $exams): array
    {
        $out = [];
        foreach ($exams as $exam) {
            if (! $exam->subject_id) {
                continue;
            }
            $summary = $this->summaryForExam($exam, (int) $exam->subject_id, []);
            $out[(int) $exam->id] = $summary['exam'];
        }

        return $out;
    }
}
