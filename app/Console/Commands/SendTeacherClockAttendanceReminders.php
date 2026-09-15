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
 * 08:00 — clock-in reminder for teachers who have not clocked in.
 * 09:00 — class-attendance reminder for class/assistant teachers only.
 */
class SendTeacherClockAttendanceReminders extends Command
{
    protected $signature = 'reminders:teacher-clock-attendance {--kind=clock : clock or attendance}';

    protected $description = 'Push reminders: 8am clock-in (all teachers) and 9am class attendance (homeroom only).';

    public function handle(ExpoPushService $push): int
    {
        $kind = strtolower((string) $this->option('kind'));
        if (! in_array($kind, ['clock', 'attendance'], true)) {
            $this->error('kind must be clock or attendance.');

            return self::FAILURE;
        }

        $today = Carbon::today('Africa/Nairobi');
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
            $staff = $teacher->staff;
            $staffId = $staff?->id;
            if (! $staffId) {
                continue;
            }
            if (strtolower((string) ($staff->status ?? '')) !== 'active') {
                continue;
            }
            if (strtolower((string) ($staff->employment_status ?? '')) !== 'active') {
                continue;
            }
            if (in_array($staffId, $staffIdsOnLeave, true)) {
                continue;
            }

            $tokens = $this->tokensForUser($teacher->id);
            if ($tokens === []) {
                continue;
            }

            if ($kind === 'clock') {
                if (in_array($staffId, $clockedInStaffIds, true)) {
                    continue;
                }
                $push->sendToTokens(
                    $tokens,
                    'Clock in reminder',
                    'Good morning! Please remember to clock in for today.',
                    [
                        'type' => 'teacher_clock_reminder',
                        'date' => $todayStr,
                    ]
                );
                $sent++;

                continue;
            }

            if (! $teacher->isHomeroomTeacher()) {
                continue;
            }
            if (in_array($teacher->id, $markedAttendanceByTeacher, true)) {
                continue;
            }

            $push->sendToTokens(
                $tokens,
                'Attendance reminder',
                'Please mark class attendance for today.',
                [
                    'type' => 'teacher_attendance_reminder',
                    'date' => $todayStr,
                ]
            );
            $sent++;
        }

        $this->info("Sent {$kind} reminders to {$sent} teacher(s).");

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
