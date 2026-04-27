<?php

namespace App\Console\Commands;

use App\Models\Academics\LessonPlan;
use App\Models\Academics\Timetable;
use App\Models\Staff;
use App\Notifications\LessonPlanReviewNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendUpcomingLessonPlanReminders extends Command
{
    protected $signature = 'reminders:lesson-plans-upcoming {--window=60 : Minutes before class start to remind}';
    protected $description = 'Notify teachers when upcoming timetable slots have no submitted/approved lesson plan.';

    public function handle(): int
    {
        $today = now()->toDateString();
        $dayName = now()->dayName; // e.g. Monday
        $windowMinutes = max(5, min(240, (int) $this->option('window')));

        $rows = Timetable::query()
            ->where('day', $dayName)
            ->where('is_break', false)
            ->whereNotNull('staff_id')
            ->get(['id', 'staff_id', 'start_time', 'end_time', 'classroom_id', 'subject_id', 'term_id', 'academic_year_id']);

        $now = now();
        $count = 0;

        foreach ($rows as $tt) {
            if (! $tt->start_time) {
                continue;
            }

            try {
                $start = \Carbon\Carbon::parse($today.' '.$tt->start_time);
            } catch (\Throwable $e) {
                continue;
            }

            $diffMinutes = $now->diffInMinutes($start, false);
            if ($diffMinutes < 0 || $diffMinutes > $windowMinutes) {
                continue;
            }

            $cacheKey = 'lp_reminder:'.$today.':tt:'.$tt->id;
            if (Cache::has($cacheKey)) {
                continue;
            }

            $hasPlan = LessonPlan::query()
                ->whereDate('planned_date', $today)
                ->where('timetable_id', $tt->id)
                ->whereIn('submission_status', ['submitted', 'approved'])
                ->exists();

            if ($hasPlan) {
                Cache::put($cacheKey, true, now()->addHours(6));
                continue;
            }

            $staff = Staff::with('user')->find((int) $tt->staff_id);
            $user = $staff?->user;
            if (! $user) {
                continue;
            }

            $user->notify(new LessonPlanReviewNotification(
                'Lesson plan reminder',
                'You have a class starting soon and no lesson plan has been submitted yet.',
                [
                    'timetable_id' => (int) $tt->id,
                    'planned_date' => $today,
                    'classroom_id' => (int) $tt->classroom_id,
                    'subject_id' => (int) $tt->subject_id,
                    'category' => 'lesson_plans',
                ]
            ));

            Cache::put($cacheKey, true, now()->addHours(6));
            $count++;
        }

        $this->info('Sent '.$count.' upcoming lesson plan reminders.');

        return self::SUCCESS;
    }
}

