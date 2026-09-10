<?php

namespace App\Console\Commands;

use App\Models\SchoolDay;
use App\Models\Term;
use App\Services\AppChannelNotifyService;
use App\Services\TeacherReminderAudienceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * 14:00 weekday in-app (+ push) reminder when class teachers still have unmarked students.
 */
class SendClassTeacherUnmarkedAttendanceReminders extends Command
{
    protected $signature = 'reminders:class-teacher-unmarked-attendance';

    protected $description = '2pm in-app reminder for class teachers with students still unmarked.';

    public function handle(AppChannelNotifyService $appChannel, TeacherReminderAudienceService $audience): int
    {
        $today = Carbon::today();
        $todayStr = $today->toDateString();

        if (! SchoolDay::isSchoolDay($todayStr)) {
            $this->info("$todayStr is not a school day — skipping.");

            return self::SUCCESS;
        }

        $term = Term::query()
            ->whereDate('opening_date', '<=', $todayStr)
            ->whereDate('closing_date', '>=', $todayStr)
            ->first();
        if (! $term) {
            $this->info("No active term for $todayStr — skipping.");

            return self::SUCCESS;
        }

        $rows = $audience->classTeachersWithUnmarkedStudents($todayStr);
        if ($rows->isEmpty()) {
            $this->info('No unmarked students remain for class teachers.');

            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            $count = (int) $row->unmarked_count;
            $total = (int) $row->total_students;
            $appChannel->notifyUsers(
                collect([$row->user]),
                'Attendance still incomplete',
                $count === 1
                    ? "1 of {$total} students in your class still has no attendance for today. Please mark them."
                    : "{$count} of {$total} students in your class still have no attendance for today. Please finish marking.",
                [
                    'type' => 'class_attendance_unmarked_reminder',
                    'category' => 'attendance',
                    'date' => $todayStr,
                    'unmarked_count' => $count,
                    'total_students' => $total,
                    'push_channel' => 'teacher-alerts',
                ]
            );
        }

        $this->info('Sent afternoon unmarked-attendance reminders to '.$rows->count().' class teacher(s).');

        return self::SUCCESS;
    }
}
