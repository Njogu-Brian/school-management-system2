<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SendScheduledCommunications::class,
        \App\Console\Commands\BackfillStudentDiaries::class,
        \App\Console\Commands\PurgeLocalStorageAndDocuments::class,
        \App\Console\Commands\RecomputeFeeClearances::class,
        \App\Console\Commands\SendTeacherClockAttendanceReminders::class,
        \App\Console\Commands\SendUpcomingLessonPlanReminders::class,
        \App\Console\Commands\RecomputeLessonPlanPace::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Laravel 11+ registers schedules from routes/console.php only.
        // Keep this method empty so tasks are not defined in two places.
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        // routes/console.php is loaded by bootstrap/app.php withRouting(commands:).
        // Do not require it here or scheduled events and closure commands register twice.
    }
}
