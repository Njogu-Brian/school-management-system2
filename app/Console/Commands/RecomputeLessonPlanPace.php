<?php

namespace App\Console\Commands;

use App\Models\Academics\LessonPlan;
use App\Models\Academics\Timetable;
use App\Models\Staff;
use App\Notifications\LessonPlanReviewNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RecomputeLessonPlanPace extends Command
{
    protected $signature = 'lesson-plans:recompute-pace {--days=7 : Lookback days for consistency} {--threshold=0.6 : Consistency threshold (0-1)}';
    protected $description = 'Compute simple weekly lesson-plan submission consistency per teacher and notify those falling behind.';

    public function handle(): int
    {
        $days = max(3, min(30, (int) $this->option('days')));
        $threshold = (float) $this->option('threshold');
        $threshold = max(0.0, min(1.0, $threshold));

        $end = now()->startOfDay();
        $start = $end->copy()->subDays($days - 1);

        $teacherIds = Timetable::query()
            ->whereNotNull('staff_id')
            ->where('is_break', false)
            ->distinct()
            ->pluck('staff_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $notified = 0;

        foreach ($teacherIds as $staffId) {
            $expected = $this->expectedSessionsForTeacher($staffId, $start, $end);
            if ($expected <= 0) {
                continue;
            }

            $submitted = LessonPlan::query()
                ->where('created_by', $staffId)
                ->whereBetween('planned_date', [$start->toDateString(), $end->toDateString()])
                ->whereIn('submission_status', ['submitted', 'approved'])
                ->count();

            $ratio = $submitted / $expected;

            $cacheKey = 'lp_pace_notice:'.$end->toDateString().':staff:'.$staffId;
            if ($ratio < $threshold && ! Cache::has($cacheKey)) {
                $staff = Staff::with('user')->find($staffId);
                $user = $staff?->user;
                if ($user) {
                    $user->notify(new LessonPlanReviewNotification(
                        'Lesson plan pace alert',
                        'Your lesson plan submission consistency is below the expected threshold for the past week.',
                        [
                            'staff_id' => $staffId,
                            'window_days' => $days,
                            'expected_sessions' => $expected,
                            'submitted_sessions' => $submitted,
                            'consistency' => round($ratio, 3),
                            'threshold' => $threshold,
                            'category' => 'lesson_plans',
                        ]
                    ));
                    Cache::put($cacheKey, true, now()->addDays(2));
                    $notified++;
                }
            }
        }

        $this->info('Pace recompute complete. Notified '.$notified.' teachers.');

        return self::SUCCESS;
    }

    protected function expectedSessionsForTeacher(int $staffId, \Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->dayName;
            $cursor->addDay();
        }

        if ($days === []) {
            return 0;
        }

        $weeklyCount = Timetable::query()
            ->where('staff_id', $staffId)
            ->where('is_break', false)
            ->whereIn('day', array_values(array_unique($days)))
            ->count();

        // Approximation: if range includes duplicates of same weekday, multiply by occurrences.
        $occurrences = array_count_values($days);
        $expected = 0;

        $byDay = Timetable::query()
            ->where('staff_id', $staffId)
            ->where('is_break', false)
            ->whereIn('day', array_keys($occurrences))
            ->selectRaw('day, count(*) as c')
            ->groupBy('day')
            ->get();

        foreach ($byDay as $row) {
            $dayName = (string) $row->day;
            $c = (int) $row->c;
            $expected += $c * (int) ($occurrences[$dayName] ?? 0);
        }

        return $expected > 0 ? $expected : $weeklyCount;
    }
}

