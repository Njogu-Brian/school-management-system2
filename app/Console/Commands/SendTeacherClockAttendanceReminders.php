<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SchoolDay;
use App\Models\StaffAttendance;
use App\Models\Term;
use App\Models\User;
use App\Services\ExpoPushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sends a 9 AM push reminder to teachers who have NOT yet clocked in OR
 * marked attendance for any of their assigned classrooms. Honors school
 * days (skips weekends/holidays/breaks) and active leave.
 */
class SendTeacherClockAttendanceReminders extends Command
{
    protected $signature = 'reminders:teacher-clock-attendance';

    protected $description = 'Daily 9am push reminder for teachers missing clock-in or attendance marking.';

    public function handle(ExpoPushService $push): int
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

        $teacherRoleNames = ['Teacher', 'Senior Teacher', 'Supervisor', 'teacher', 'senior teacher', 'supervisor'];
        $teachers = User::query()
            ->with(['staff'])
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $teacherRoleNames))
            ->get();

        if ($teachers->isEmpty()) {
            $this->info('No teachers to check.');
            return self::SUCCESS;
        }

        $staffIdsOnLeave = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $todayStr)
            ->whereDate('end_date', '>=', $todayStr)
            ->pluck('staff_id')
            ->all();

        $clockedInStaffIds = StaffAttendance::query()
            ->whereDate('date', $todayStr)
            ->whereNotNull('check_in_time')
            ->pluck('staff_id')
            ->all();

        $markedAttendanceByTeacher = Attendance::query()
            ->whereDate('date', $todayStr)
            ->whereNotNull('marked_by')
            ->pluck('marked_by')
            ->unique()
            ->all();

        $sent = 0;
        foreach ($teachers as $teacher) {
            $staffId = $teacher->staff?->id;
            if ($staffId && in_array($staffId, $staffIdsOnLeave, true)) {
                continue;
            }

            $missingClock = ! $staffId || ! in_array($staffId, $clockedInStaffIds, true);
            $missingAttendance = ! in_array($teacher->id, $markedAttendanceByTeacher, true);

            if (! $missingClock && ! $missingAttendance) {
                continue;
            }

            $tokens = $this->tokensForUser($teacher->id);
            if (empty($tokens)) {
                continue;
            }

            $parts = [];
            if ($missingClock) $parts[] = 'clock in';
            if ($missingAttendance) $parts[] = 'mark attendance';
            $action = implode(' and ', $parts);

            $push->sendToTokens(
                $tokens,
                'Reminder',
                "Good morning! Please remember to {$action} for today.",
                [
                    'type' => 'teacher_reminder',
                    'missing_clock' => $missingClock,
                    'missing_attendance' => $missingAttendance,
                    'date' => $todayStr,
                ]
            );
            $sent++;
        }

        $this->info("Sent reminders to {$sent} teacher(s).");
        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function tokensForUser(int $userId): array
    {
        return DB::table('user_device_tokens')
            ->where('user_id', $userId)
            ->pluck('token')
            ->filter(fn ($t) => is_string($t) && $t !== '')
            ->values()
            ->all();
    }
}
