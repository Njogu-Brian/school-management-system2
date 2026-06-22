<?php

namespace App\Services\Academics;

use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\AssistantClassTeacherAssignment;
use App\Models\ClassTeacherAssignment;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherAssignmentService
{
    /** @var list<string> */
    public const TEACHER_ROLE_NAMES = [
        'Teacher', 'teacher', 'Senior Teacher', 'senior teacher',
        'Supervisor', 'supervisor', 'Academic Administrator', 'academic administrator',
    ];

    /**
     * All classroom+stream slots available for assignment.
     *
     * @return Collection<int, object{key: string, classroom_id: int, stream_id: ?int, label: string}>
     */
    public function getStreamSlots(): Collection
    {
        $classrooms = Classroom::with(['primaryStreams', 'streams'])->orderBy('name')->get();
        $slots = collect();

        foreach ($classrooms as $classroom) {
            $streams = $classroom->allStreams();
            if ($streams->isEmpty()) {
                $slots->push((object) [
                    'key' => $classroom->id . ':null',
                    'classroom_id' => (int) $classroom->id,
                    'stream_id' => null,
                    'label' => $classroom->name,
                ]);
                continue;
            }

            foreach ($streams as $stream) {
                $slots->push((object) [
                    'key' => $classroom->id . ':' . $stream->id,
                    'classroom_id' => (int) $classroom->id,
                    'stream_id' => (int) $stream->id,
                    'label' => $classroom->name . ' ' . $stream->name,
                ]);
            }
        }

        return $slots;
    }

    /**
     * Subjects available for a classroom+stream slot (from classroom_subjects).
     *
     * @return Collection<int, object{id: int, subject_id: int, name: string, code: ?string}>
     */
    public function getSubjectsForSlot(int $classroomId, ?int $streamId): Collection
    {
        $q = ClassroomSubject::query()
            ->with('subject')
            ->where('classroom_id', $classroomId);

        if ($streamId === null) {
            $q->whereNull('stream_id');
        } else {
            $q->where('stream_id', $streamId);
        }

        return $q->get()
            ->map(fn (ClassroomSubject $cs) => (object) [
                'id' => (int) $cs->id,
                'subject_id' => (int) $cs->subject_id,
                'name' => $cs->subject?->name ?? 'Subject #' . $cs->subject_id,
                'code' => $cs->subject?->code,
            ])
            ->sortBy('name')
            ->values();
    }

    /**
     * Current teaching assignments for a staff member.
     *
     * @return array{
     *   slots: list<array{key: string, classroom_id: int, stream_id: ?int, is_class_teacher: bool, is_assistant_teacher: bool, subject_ids: int[]}>,
     *   class_teacher_slots: list<string>,
     *   assistant_teacher_slots: list<string>,
     * }
     */
    public function getAssignmentsForStaff(int $staffId): array
    {
        $classTeacherKeys = ClassTeacherAssignment::query()
            ->where('staff_id', $staffId)
            ->get()
            ->map(fn ($a) => $a->classroom_id . ':' . ($a->stream_id === null ? 'null' : $a->stream_id))
            ->all();

        $assistantKeys = AssistantClassTeacherAssignment::query()
            ->where('staff_id', $staffId)
            ->get()
            ->map(fn ($a) => $a->classroom_id . ':' . ($a->stream_id === null ? 'null' : $a->stream_id))
            ->all();

        $subjectRows = ClassroomSubject::query()
            ->where('staff_id', $staffId)
            ->get(['classroom_id', 'stream_id', 'subject_id']);

        $slotSubjects = [];
        foreach ($subjectRows as $row) {
            $key = $row->classroom_id . ':' . ($row->stream_id === null ? 'null' : $row->stream_id);
            $slotSubjects[$key] ??= [];
            $slotSubjects[$key][] = (int) $row->subject_id;
        }

        $allKeys = array_values(array_unique(array_merge(
            $classTeacherKeys,
            $assistantKeys,
            array_keys($slotSubjects)
        )));

        $slots = [];
        foreach ($allKeys as $key) {
            [$classroomId, $streamPart] = explode(':', $key, 2);
            $streamId = $streamPart === 'null' ? null : (int) $streamPart;

            $slots[] = [
                'key' => $key,
                'classroom_id' => (int) $classroomId,
                'stream_id' => $streamId,
                'is_class_teacher' => in_array($key, $classTeacherKeys, true),
                'is_assistant_teacher' => in_array($key, $assistantKeys, true),
                'subject_ids' => $slotSubjects[$key] ?? [],
            ];
        }

        return [
            'slots' => $slots,
            'class_teacher_slots' => $classTeacherKeys,
            'assistant_teacher_slots' => $assistantKeys,
        ];
    }

    /**
     * @param  array<int, array{
     *   classroom_id: int,
     *   stream_id?: int|null,
     *   is_class_teacher?: bool,
     *   is_assistant_teacher?: bool,
     *   subject_ids?: int[]
     * }>  $slotPayloads
     */
    public function saveAssignmentsForStaff(int $staffId, array $slotPayloads): void
    {
        DB::transaction(function () use ($staffId, $slotPayloads) {
            $desiredClassTeacher = [];
            $desiredAssistant = [];
            $desiredSubjects = [];

            foreach ($slotPayloads as $slot) {
                $classroomId = (int) $slot['classroom_id'];
                $streamId = array_key_exists('stream_id', $slot) && $slot['stream_id'] !== null && $slot['stream_id'] !== ''
                    ? (int) $slot['stream_id']
                    : null;
                $key = $classroomId . ':' . ($streamId === null ? 'null' : $streamId);

                if (! empty($slot['is_class_teacher'])) {
                    $desiredClassTeacher[$key] = ['classroom_id' => $classroomId, 'stream_id' => $streamId];
                }
                if (! empty($slot['is_assistant_teacher'])) {
                    $desiredAssistant[$key] = ['classroom_id' => $classroomId, 'stream_id' => $streamId];
                }

                foreach ($slot['subject_ids'] ?? [] as $subjectId) {
                    $desiredSubjects[] = [
                        'classroom_id' => $classroomId,
                        'stream_id' => $streamId,
                        'subject_id' => (int) $subjectId,
                    ];
                }
            }

            $this->syncClassTeacherAssignments($staffId, $desiredClassTeacher);
            $this->syncAssistantAssignments($staffId, $desiredAssistant);
            $this->syncSubjectAssignments($staffId, $desiredSubjects);
        });
    }

    /**
     * @param  array<string, array{classroom_id: int, stream_id: ?int}>  $desired
     */
    private function syncClassTeacherAssignments(int $staffId, array $desired): void
    {
        $existing = ClassTeacherAssignment::query()->where('staff_id', $staffId)->get();

        foreach ($existing as $row) {
            $key = $row->classroom_id . ':' . ($row->stream_id === null ? 'null' : $row->stream_id);
            if (! isset($desired[$key])) {
                $row->delete();
            }
        }

        foreach ($desired as $slot) {
            ClassTeacherAssignment::updateOrCreate(
                [
                    'classroom_id' => $slot['classroom_id'],
                    'stream_id' => $slot['stream_id'],
                ],
                ['staff_id' => $staffId]
            );
        }
    }

    /**
     * @param  array<string, array{classroom_id: int, stream_id: ?int}>  $desired
     */
    private function syncAssistantAssignments(int $staffId, array $desired): void
    {
        $existing = AssistantClassTeacherAssignment::query()->where('staff_id', $staffId)->get();

        foreach ($existing as $row) {
            $key = $row->classroom_id . ':' . ($row->stream_id === null ? 'null' : $row->stream_id);
            if (! isset($desired[$key])) {
                $row->delete();
            }
        }

        foreach ($desired as $slot) {
            AssistantClassTeacherAssignment::updateOrCreate(
                [
                    'classroom_id' => $slot['classroom_id'],
                    'stream_id' => $slot['stream_id'],
                ],
                ['staff_id' => $staffId]
            );
        }
    }

    /**
     * @param  list<array{classroom_id: int, stream_id: ?int, subject_id: int}>  $desired
     */
    private function syncSubjectAssignments(int $staffId, array $desired): void
    {
        $current = ClassroomSubject::query()->where('staff_id', $staffId)->get();

        $desiredKeys = [];
        foreach ($desired as $d) {
            $desiredKeys[] = $d['classroom_id'] . ':' . ($d['stream_id'] === null ? 'null' : $d['stream_id']) . ':' . $d['subject_id'];
        }

        foreach ($current as $row) {
            $key = $row->classroom_id . ':' . ($row->stream_id === null ? 'null' : $row->stream_id) . ':' . $row->subject_id;
            if (! in_array($key, $desiredKeys, true)) {
                $row->update(['staff_id' => null]);
            }
        }

        foreach ($desired as $d) {
            $q = ClassroomSubject::query()
                ->where('classroom_id', $d['classroom_id'])
                ->where('subject_id', $d['subject_id']);

            if ($d['stream_id'] === null) {
                $q->whereNull('stream_id');
            } else {
                $q->where('stream_id', $d['stream_id']);
            }

            $slot = $q->first();
            if ($slot) {
                $slot->update(['staff_id' => $staffId]);
            }
        }
    }

    public function staffHasTeachingRole(Staff $staff): bool
    {
        if (! $staff->user) {
            return false;
        }

        return $staff->user->roles()
            ->whereIn('name', self::TEACHER_ROLE_NAMES)
            ->exists();
    }

    /**
     * @return Collection<int, Staff>
     */
    public function getTeachingStaff(): Collection
    {
        return Staff::with('user')
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', self::TEACHER_ROLE_NAMES))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }
}
