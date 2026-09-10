<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use App\Models\SchoolDay;
use App\Models\StaffAttendance;
use App\Models\Term;
use App\Models\User;
use App\Services\AppChannelNotifyService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * 08:00 weekday in-app (+ push) reminder for teachers who have not clocked in.
 */
class SendTeacherClockInReminders extends Command
{
    protected $signature = 'reminders:teacher-clock-in';

    protected $description = '8am in-app reminder for teachers who have not signed in (clocked in).';

    public function handle(AppChannelNotifyService $appChannel): int
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

        $teachers = User::query()
            ->with(['staff'])
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [
                'Teacher', 'Senior Teacher', 'Supervisor', 'teacher', 'senior teacher', 'supervisor',
            ]))
            ->get();

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

        $targets = collect();
        foreach ($teachers as $teacher) {
            $staffId = $teacher->staff?->id;
            if ($staffId && in_array($staffId, $staffIdsOnLeave, true)) {
                continue;
            }
            if ($staffId && in_array($staffId, $clockedInStaffIds, true)) {
                continue;
            }
            $targets->push($teacher);
        }

        if ($targets->isEmpty()) {
            $this->info('All teachers have signed in (or none eligible).');

            return self::SUCCESS;
        }

        $appChannel->notifyUsers(
            $targets,
            'Sign-in reminder',
            'Good morning! Please clock in for today.',
            [
                'type' => 'teacher_clock_reminder',
                'category' => 'attendance',
                'date' => $todayStr,
                'push_channel' => 'teacher-alerts',
            ]
        );

        $this->info('Sent clock-in reminders to '.$targets->count().' teacher(s).');

        return self::SUCCESS;
    }
}
