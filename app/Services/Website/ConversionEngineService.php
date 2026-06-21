<?php

namespace App\Services\Website;

use App\Models\Website\ConversionEvent;
use App\Models\Website\ExitIntentCampaign;
use App\Models\Website\LeadMagnet;
use App\Models\Website\LeadMagnetDownload;
use App\Models\Website\WebsiteCta;

class ConversionEngineService
{
    public function activeCtas(?string $page = null): array
    {
        return WebsiteCta::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (WebsiteCta $cta) use ($page) {
                if ($cta->placement === 'global') {
                    return true;
                }
                if (! $page || ! $cta->pages) {
                    return false;
                }

                return in_array($page, $cta->pages, true);
            })
            ->values()
            ->map(fn (WebsiteCta $c) => [
                'id' => $c->id,
                'type' => $c->cta_type,
                'label' => $c->label,
                'url' => $c->url,
            ])
            ->all();
    }

    public function trackCtaClick(int $ctaId, ?string $page = null, ?string $visitorId = null): void
    {
        $cta = WebsiteCta::find($ctaId);
        if (! $cta) {
            return;
        }

        $cta->increment('click_count');

        app(WebsiteAnalyticsService::class)->trackConversion('cta_click', $page, [
            'cta_id' => $ctaId,
            'cta_type' => $cta->cta_type,
            'label' => $cta->label,
        ], $visitorId);
    }

    public function activeExitIntent(?string $page = null): ?array
    {
        $campaign = ExitIntentCampaign::query()
            ->where('is_active', true)
            ->latest()
            ->first();

        if (! $campaign) {
            return null;
        }

        if ($campaign->pages && $page && ! str_contains($campaign->pages, $page)) {
            return null;
        }

        $campaign->increment('impressions');

        return [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'message' => $campaign->message,
            'button_label' => $campaign->button_label,
            'button_url' => $campaign->button_url,
        ];
    }

    public function recordExitConversion(int $campaignId): void
    {
        ExitIntentCampaign::where('id', $campaignId)->increment('conversions');
        app(WebsiteAnalyticsService::class)->trackConversion('exit_intent', null, ['campaign_id' => $campaignId]);
    }

    public function leadMagnets(): array
    {
        return LeadMagnet::query()
            ->where('published', true)
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'description', 'cover_image'])
            ->all();
    }

    public function captureLeadMagnetDownload(LeadMagnet $magnet, array $data): LeadMagnetDownload
    {
        $magnet->increment('download_count');

        $download = LeadMagnetDownload::create([
            'lead_magnet_id' => $magnet->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        app(WebsiteAnalyticsService::class)->trackConversion('lead_magnet', '/lead-magnets/'.$magnet->slug, [
            'lead_magnet_id' => $magnet->id,
            'email' => $data['email'],
        ]);

        return $download;
    }

    public function conversionStats(int $days = 30): array
    {
        $since = now()->subDays($days);

        $ctaClicks = ConversionEvent::where('event_type', 'cta_click')->where('occurred_at', '>=', $since)->count();
        $enquiries = ConversionEvent::where('event_type', 'enquiry')->where('occurred_at', '>=', $since)->count();
        $applications = ConversionEvent::where('event_type', 'admission_start')->where('occurred_at', '>=', $since)->count();

        return [
            'cta_clicks' => $ctaClicks,
            'enquiries' => $enquiries,
            'admission_starts' => $applications,
            'top_ctas' => WebsiteCta::orderByDesc('click_count')->limit(5)->get(['name', 'label', 'click_count']),
        ];
    }
}
