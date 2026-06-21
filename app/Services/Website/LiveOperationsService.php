<?php

namespace App\Services\Website;

use App\Models\Academics\Homework;
use App\Models\Term;
use App\Models\Transport;
use App\Models\Website\SchoolMeal;
use Illuminate\Support\Facades\Cache;

class LiveOperationsService
{
    public function __construct(
        protected WebsiteErpIntegrationService $erp
    ) {}

    public function noticeboard(int $limit = 15): array
    {
        return $this->erp->announcements($limit);
    }

    public function schoolStatus(): array
    {
        return Cache::remember('website.live.school_status', 300, function () {
            $term = Term::query()
                ->where('is_current', true)
                ->orWhere(function ($q) {
                    $q->whereDate('opening_date', '<=', now())
                        ->whereDate('closing_date', '>=', now());
                })
                ->orderByDesc('is_current')
                ->first();

            $isOpen = true;
            $statusNote = 'School is open';

            if ($term && $term->closing_date && now()->gt($term->closing_date)) {
                $isOpen = false;
                $statusNote = 'Term ended — check announcements for reopening';
            }

            return [
                'is_open' => $isOpen,
                'status_note' => $statusNote,
                'current_term' => $term ? [
                    'id' => $term->id,
                    'name' => $term->name,
                    'start_date' => $term->opening_date?->toDateString(),
                    'end_date' => $term->closing_date?->toDateString(),
                ] : null,
                'active_timetable' => $term ? 'Term '.$term->name.' timetable in effect' : null,
                'as_of' => now()->toIso8601String(),
            ];
        });
    }

    public function weeklyMeals(): array
    {
        return Cache::remember('website.live.weekly_meals', 3600, function () {
            $start = now()->startOfWeek();
            $end = now()->endOfWeek();

            return SchoolMeal::query()
                ->whereBetween('meal_date', [$start, $end])
                ->orderBy('meal_date')
                ->get()
                ->map(fn ($m) => [
                    'date' => $m->meal_date->toDateString(),
                    'day' => $m->day_of_week,
                    'breakfast' => $m->breakfast,
                    'lunch' => $m->lunch,
                    'snack' => $m->snack,
                    'notes' => $m->notes,
                ])
                ->all();
        });
    }

    public function transportPreview(int $limit = 8): array
    {
        return Cache::remember('website.live.transport_preview', 600, function () use ($limit) {
            return Transport::query()
                ->withCount('students')
                ->limit($limit)
                ->get(['id', 'driver_name', 'vehicle_number'])
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'route_label' => 'Route '.$t->id,
                    'driver' => $t->driver_name,
                    'vehicle' => $t->vehicle_number,
                    'learners' => $t->students_count,
                    'gps_enabled' => false,
                ])
                ->all();
        });
    }

    public function homeworkTeaser(int $limit = 6): array
    {
        return Cache::remember('website.live.homework_teaser', 300, function () use ($limit) {
            return Homework::query()
                ->with(['classroom:id,name', 'subject:id,name'])
                ->whereDate('due_date', '>=', now()->subDays(1))
                ->orderBy('due_date')
                ->limit($limit)
                ->get()
                ->map(fn ($h) => [
                    'title' => $h->title,
                    'classroom' => $h->classroom?->name,
                    'subject' => $h->subject?->name,
                    'due_date' => $h->due_date?->toDateString(),
                    'teaser' => 'Log in to Parent Portal for full homework details.',
                ])
                ->all();
        });
    }

    public function dashboard(): array
    {
        return [
            'noticeboard' => $this->noticeboard(),
            'school_status' => $this->schoolStatus(),
            'meals' => $this->weeklyMeals(),
            'transport' => $this->transportPreview(),
            'homework_teaser' => $this->homeworkTeaser(),
        ];
    }
}
