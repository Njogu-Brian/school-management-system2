<?php

namespace App\Services\Website;

use App\Models\Website\SchoolMeal;
use App\Models\Website\WebsiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Public website "live" panel — CMS data + existing website ERP read-only hooks (announcements).
 */
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
            $settings = WebsiteSetting::current();

            return [
                'is_open' => (bool) ($settings->admissions_open ?? true),
                'status_note' => $settings->current_term
                    ? 'Current term: '.$settings->current_term
                    : 'Welcome to Royal Kings Education Centre',
                'current_term' => $settings->current_term ? ['name' => $settings->current_term] : null,
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

    public function dashboard(): array
    {
        return [
            'noticeboard' => $this->noticeboard(),
            'school_status' => $this->schoolStatus(),
            'meals' => $this->weeklyMeals(),
        ];
    }
}
