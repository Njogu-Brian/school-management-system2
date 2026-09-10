<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassTeacherAssignment;
use App\Models\LeaveRequest;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolve class teachers who still have unmarked students for a school day.
 */
class TeacherReminderAudienceService
{
    /**
     * @return Collection<int, object{user: User, unmarked_count: int, total_students: int}>
     */
    public function classTeachersWithUnmarkedStudents(string $date): Collection
    {
        $staffIdsOnLeave = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->pluck('staff_id')
            ->all();

        $homeroomStaffIds = ClassTeacherAssignment::query()
            ->pluck('staff_id')
            ->unique()
            ->filter()
            ->values();

        $staffRows = Staff::query()
            ->whereIn('id', $homeroomStaffIds)
            ->whereNotNull('user_id')
            ->get();

        $out = collect();

        foreach ($staffRows as $staff) {
            if (in_array($staff->id, $staffIdsOnLeave, true)) {
                continue;
            }

            $user = User::query()->find($staff->user_id);
            if (! $user) {
                continue;
            }

            $stats = $this->homeroomAttendanceStats($staff->id, $date);
            if ($stats['total_students'] === 0) {
                continue;
            }
            if ($stats['unmarked_count'] === 0) {
                continue;
            }

            $out->push((object) [
                'user' => $user,
                'unmarked_count' => $stats['unmarked_count'],
                'total_students' => $stats['total_students'],
            ]);
        }

        return $out->unique(fn ($row) => $row->user->id)->values();
    }

    /**
     * @return array{total_students: int, marked_count: int, unmarked_count: int}
     */
    public function homeroomAttendanceStats(int $staffId, string $date): array
    {
        $assignments = ClassTeacherAssignment::query()
            ->where('staff_id', $staffId)
            ->get(['classroom_id', 'stream_id']);

        $studentIds = collect();
        foreach ($assignments as $assignment) {
            $q = Student::query()
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->where('classroom_id', (int) $assignment->classroom_id);
            if ($assignment->stream_id !== null) {
                $q->where('stream_id', (int) $assignment->stream_id);
            }
            $studentIds = $studentIds->merge($q->pluck('id'));
        }

        $studentIds = $studentIds->unique()->values();
        $total = $studentIds->count();
        if ($total === 0) {
            return ['total_students' => 0, 'marked_count' => 0, 'unmarked_count' => 0];
        }

        $marked = Attendance::query()
            ->whereDate('date', $date)
            ->whereIn('student_id', $studentIds->all())
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct('student_id')
            ->count('student_id');

        return [
            'total_students' => $total,
            'marked_count' => $marked,
            'unmarked_count' => max(0, $total - $marked),
        ];
    }
}
