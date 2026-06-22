<?php

namespace App\Services\Academics;

use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\Stream;
use App\Models\Academics\TimetableStreamActivityTeacher;
use App\Models\Academics\TimetableStreamSubjectTeacher;
use App\Models\AssistantClassTeacherAssignment;
use App\Models\ClassTeacherAssignment;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

/**
 * Clears or transfers teaching assignments when staff leave or are archived.
 */
class StaffTeachingAssignmentReleaseService
{
    public function __construct(
        private readonly TeacherAssignmentService $assignmentService,
    ) {}

    /**
     * Human-readable summary of assignments held by a staff member.
     *
     * @return array{
     *   counts: array{class_teacher: int, assistant_teacher: int, subject_slots: int, stream_pivot: int, legacy_classroom: int, legacy_subject: int, timetable_subject: int, timetable_activity: int, total: int},
     *   items: list<array{type: string, label: string}>,
     *   has_assignments: bool,
     * }
     */
    public function summarize(int $staffId): array
    {
        $staff = Staff::with('user')->findOrFail($staffId);
        $items = [];

        $classTeacherRows = ClassTeacherAssignment::query()
            ->where('staff_id', $staffId)
            ->get();
        foreach ($classTeacherRows as $row) {
            $items[] = [
                'type' => 'class_teacher',
                'label' => $this->slotLabel($row->classroom_id, $row->stream_id) . ' — Class Teacher',
            ];
        }

        $assistantRows = AssistantClassTeacherAssignment::query()
            ->where('staff_id', $staffId)
            ->get();
        foreach ($assistantRows as $row) {
            $items[] = [
                'type' => 'assistant_teacher',
                'label' => $this->slotLabel($row->classroom_id, $row->stream_id) . ' — Assistant Teacher',
            ];
        }

        $subjectRows = ClassroomSubject::query()
            ->with(['classroom', 'stream', 'subject'])
            ->where('staff_id', $staffId)
            ->get();
        foreach ($subjectRows as $row) {
            $class = $row->classroom?->name ?? 'Class #' . $row->classroom_id;
            $stream = $row->stream?->name;
            $subject = $row->subject?->name ?? 'Subject #' . $row->subject_id;
            $label = $stream ? "{$class} {$stream} — {$subject}" : "{$class} — {$subject}";
            $items[] = ['type' => 'subject', 'label' => $label];
        }

        $streamPivot = 0;
        $legacyClassroom = 0;
        $legacySubject = 0;
        if ($staff->user) {
            $streamPivot = (int) DB::table('stream_teacher')
                ->where('teacher_id', $staff->user->id)
                ->count();
            if ($streamPivot > 0) {
                $items[] = [
                    'type' => 'stream_pivot',
                    'label' => "{$streamPivot} stream teacher link(s) (legacy)",
                ];
            }

            $legacyClassroom = (int) DB::table('classroom_teacher')
                ->where('teacher_id', $staff->user->id)
                ->count();
            if ($legacyClassroom > 0) {
                $items[] = [
                    'type' => 'legacy_classroom',
                    'label' => "{$legacyClassroom} classroom link(s) (legacy)",
                ];
            }

            $legacySubject = (int) DB::table('subject_teacher')
                ->where('teacher_id', $staff->user->id)
                ->count();
            if ($legacySubject > 0) {
                $items[] = [
                    'type' => 'legacy_subject',
                    'label' => "{$legacySubject} subject link(s) (legacy)",
                ];
            }
        }

        $timetableSubject = TimetableStreamSubjectTeacher::query()
            ->where('staff_id', $staffId)
            ->count();
        if ($timetableSubject > 0) {
            $items[] = [
                'type' => 'timetable_subject',
                'label' => "{$timetableSubject} timetable subject slot(s)",
            ];
        }

        $timetableActivity = TimetableStreamActivityTeacher::query()
            ->where('staff_id', $staffId)
            ->count();
        if ($timetableActivity > 0) {
            $items[] = [
                'type' => 'timetable_activity',
                'label' => "{$timetableActivity} timetable activity slot(s)",
            ];
        }

        $counts = [
            'class_teacher' => $classTeacherRows->count(),
            'assistant_teacher' => $assistantRows->count(),
            'subject_slots' => $subjectRows->count(),
            'stream_pivot' => $streamPivot,
            'legacy_classroom' => $legacyClassroom,
            'legacy_subject' => $legacySubject,
            'timetable_subject' => $timetableSubject,
            'timetable_activity' => $timetableActivity,
            'total' => count($items),
        ];

        return [
            'counts' => $counts,
            'items' => $items,
            'has_assignments' => count($items) > 0,
        ];
    }

    /**
     * Clear all teaching assignments or transfer them to another staff member.
     *
     * @return array{transferred: bool, summary: array<string, int>}
     */
    public function release(int $staffId, ?int $replacementStaffId = null): array
    {
        if ($replacementStaffId !== null && $replacementStaffId === $staffId) {
            throw new \InvalidArgumentException('Replacement staff must be a different person.');
        }

        if ($replacementStaffId !== null) {
            Staff::query()
                ->where('id', $replacementStaffId)
                ->where('status', 'active')
                ->firstOrFail();
        }

        $staff = Staff::with('user')->findOrFail($staffId);
        $summary = [
            'class_teacher' => 0,
            'assistant_teacher' => 0,
            'subject_slots' => 0,
            'stream_pivot' => 0,
            'legacy_classroom' => 0,
            'legacy_subject' => 0,
            'timetable_subject' => 0,
            'timetable_activity' => 0,
        ];

        DB::transaction(function () use ($staff, $staffId, $replacementStaffId, &$summary) {
            $summary['class_teacher'] = $this->releaseClassTeacherSlots($staffId, $replacementStaffId);
            $summary['assistant_teacher'] = $this->releaseAssistantSlots($staffId, $replacementStaffId);
            $summary['subject_slots'] = $this->releaseSubjectSlots($staffId, $replacementStaffId);

            if ($staff->user) {
                $legacyStreamRows = DB::table('stream_teacher')
                    ->where('teacher_id', $staff->user->id)
                    ->get();
                $legacyClassroomIds = DB::table('classroom_teacher')
                    ->where('teacher_id', $staff->user->id)
                    ->pluck('classroom_id');
                $legacySubjectIds = DB::table('subject_teacher')
                    ->where('teacher_id', $staff->user->id)
                    ->pluck('subject_id');

                $summary['stream_pivot'] = DB::table('stream_teacher')
                    ->where('teacher_id', $staff->user->id)
                    ->delete();
                $summary['legacy_classroom'] = DB::table('classroom_teacher')
                    ->where('teacher_id', $staff->user->id)
                    ->delete();
                $summary['legacy_subject'] = DB::table('subject_teacher')
                    ->where('teacher_id', $staff->user->id)
                    ->delete();

                if ($replacementStaffId !== null) {
                    $this->transferLegacyPivots(
                        $replacementStaffId,
                        $legacyStreamRows,
                        $legacyClassroomIds,
                        $legacySubjectIds
                    );
                }
            }

            $summary['timetable_subject'] = $this->releaseTimetableSubjectSlots($staffId, $replacementStaffId);
            $summary['timetable_activity'] = $this->releaseTimetableActivitySlots($staffId, $replacementStaffId);
        });

        return [
            'transferred' => $replacementStaffId !== null,
            'summary' => $summary,
        ];
    }

    private function releaseClassTeacherSlots(int $staffId, ?int $replacementStaffId): int
    {
        $rows = ClassTeacherAssignment::query()->where('staff_id', $staffId)->get();
        $count = 0;

        foreach ($rows as $row) {
            if ($replacementStaffId !== null) {
                ClassTeacherAssignment::query()
                    ->where('classroom_id', $row->classroom_id)
                    ->when(
                        $row->stream_id === null,
                        fn ($q) => $q->whereNull('stream_id'),
                        fn ($q) => $q->where('stream_id', $row->stream_id)
                    )
                    ->update(['staff_id' => $replacementStaffId]);
            } else {
                $row->delete();
            }
            $count++;
        }

        return $count;
    }

    private function releaseAssistantSlots(int $staffId, ?int $replacementStaffId): int
    {
        $rows = AssistantClassTeacherAssignment::query()->where('staff_id', $staffId)->get();
        $count = 0;

        foreach ($rows as $row) {
            if ($replacementStaffId !== null) {
                AssistantClassTeacherAssignment::query()
                    ->where('classroom_id', $row->classroom_id)
                    ->when(
                        $row->stream_id === null,
                        fn ($q) => $q->whereNull('stream_id'),
                        fn ($q) => $q->where('stream_id', $row->stream_id)
                    )
                    ->update(['staff_id' => $replacementStaffId]);
            } else {
                $row->delete();
            }
            $count++;
        }

        return $count;
    }

    private function releaseSubjectSlots(int $staffId, ?int $replacementStaffId): int
    {
        $rows = ClassroomSubject::query()->where('staff_id', $staffId)->get();
        $count = 0;

        foreach ($rows as $row) {
            if ($replacementStaffId !== null) {
                $row->update(['staff_id' => $replacementStaffId]);
            } else {
                $row->update(['staff_id' => null]);
            }
            $count++;
        }

        return $count;
    }

    private function releaseTimetableSubjectSlots(int $staffId, ?int $replacementStaffId): int
    {
        if ($replacementStaffId !== null) {
            return TimetableStreamSubjectTeacher::query()
                ->where('staff_id', $staffId)
                ->update(['staff_id' => $replacementStaffId]);
        }

        return TimetableStreamSubjectTeacher::query()
            ->where('staff_id', $staffId)
            ->delete();
    }

    private function releaseTimetableActivitySlots(int $staffId, ?int $replacementStaffId): int
    {
        if ($replacementStaffId !== null) {
            return TimetableStreamActivityTeacher::query()
                ->where('staff_id', $staffId)
                ->update(['staff_id' => $replacementStaffId]);
        }

        return TimetableStreamActivityTeacher::query()
            ->where('staff_id', $staffId)
            ->delete();
    }

    /**
     * Re-apply legacy pivot assignments to the replacement user's account.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $streamRows
     * @param  \Illuminate\Support\Collection<int, mixed>  $classroomIds
     * @param  \Illuminate\Support\Collection<int, mixed>  $subjectIds
     */
    private function transferLegacyPivots(
        int $replacementStaffId,
        $streamRows,
        $classroomIds,
        $subjectIds,
    ): void {
        $replacement = Staff::with('user')->findOrFail($replacementStaffId);
        if (! $replacement->user) {
            return;
        }

        foreach ($streamRows as $row) {
            DB::table('stream_teacher')->updateOrInsert(
                [
                    'stream_id' => $row->stream_id,
                    'teacher_id' => $replacement->user->id,
                    'classroom_id' => $row->classroom_id,
                ],
                ['updated_at' => now(), 'created_at' => $row->created_at ?? now()]
            );
        }

        foreach ($classroomIds as $classroomId) {
            DB::table('classroom_teacher')->updateOrInsert(
                ['classroom_id' => $classroomId, 'teacher_id' => $replacement->user->id],
                []
            );
        }

        foreach ($subjectIds as $subjectId) {
            DB::table('subject_teacher')->updateOrInsert(
                ['subject_id' => $subjectId, 'teacher_id' => $replacement->user->id],
                []
            );
        }
    }

    private function slotLabel(int $classroomId, ?int $streamId): string
    {
        $classroom = Classroom::find($classroomId);
        $name = $classroom?->name ?? 'Class #' . $classroomId;

        if ($streamId === null) {
            return $name;
        }

        $stream = Stream::find($streamId);

        return $name . ' ' . ($stream?->name ?? 'Stream #' . $streamId);
    }
}
