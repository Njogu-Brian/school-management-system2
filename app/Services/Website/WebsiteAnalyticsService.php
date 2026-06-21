<?php

namespace App\Services\Website;

use App\Models\Website\ConversionEvent;
use App\Models\Website\PageView;

class WebsiteAnalyticsService
{
    public function trackPageView(string $page, ?string $visitorId = null, ?string $device = null, ?string $source = null, int $duration = 0): void
    {
        PageView::create([
            'page' => $page,
            'visitor_id' => $visitorId,
            'device' => $device,
            'source' => $source,
            'duration' => $duration,
        ]);
    }

    public function trackConversion(string $eventType, ?string $page = null, array $metadata = [], ?string $visitorId = null): void
    {
        ConversionEvent::create([
            'event_type' => $eventType,
            'page' => $page,
            'metadata' => $metadata,
            'visitor_id' => $visitorId,
        ]);
    }

    public function dashboardSummary(int $days = 30): array
    {
        $since = now()->subDays($days);

        $pageViews = PageView::query()->where('viewed_at', '>=', $since);
        $conversions = ConversionEvent::query()->where('occurred_at', '>=', $since);

        return [
            'total_page_views' => (clone $pageViews)->count(),
            'top_pages' => (clone $pageViews)->selectRaw('page, COUNT(*) as views')->groupBy('page')->orderByDesc('views')->limit(10)->get(),
            'conversion_totals' => (clone $conversions)->selectRaw('event_type, COUNT(*) as total')->groupBy('event_type')->pluck('total', 'event_type'),
            'top_converting_pages' => ConversionEvent::query()
                ->where('occurred_at', '>=', $since)
                ->selectRaw('page, COUNT(*) as conversions')
                ->groupBy('page')
                ->orderByDesc('conversions')
                ->limit(10)
                ->get(),
            'abandoned_admissions' => \App\Models\Admissions\AdmissionApplication::query()
                ->where('created_at', '>=', $since)
                ->where('current_step', '<', 4)
                ->where('status', 'pending')
                ->count(),
        ];
    }
}
