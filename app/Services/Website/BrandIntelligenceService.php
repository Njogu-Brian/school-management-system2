<?php

namespace App\Services\Website;

use App\Models\Website\AiChatSession;
use App\Models\Website\Blog;
use App\Models\Website\ConversionEvent;
use App\Models\Website\LeadMagnet;
use App\Models\Website\PageView;
use App\Models\Website\Referral;
use App\Models\Website\WebsiteCta;
use App\Models\Website\WebsiteEventRegistration;
use Illuminate\Support\Facades\DB;

class BrandIntelligenceService
{
    public function dashboard(int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'top_pages' => PageView::where('viewed_at', '>=', $since)
                ->select('page', DB::raw('COUNT(*) as views'))
                ->groupBy('page')
                ->orderByDesc('views')
                ->limit(10)
                ->get(),
            'best_ctas' => WebsiteCta::orderByDesc('click_count')->limit(5)->get(['name', 'label', 'click_count']),
            'best_blogs' => Blog::where('published', true)->orderByDesc('views_count')->limit(5)->get(['title', 'slug', 'views_count']),
            'chat_sessions' => AiChatSession::where('created_at', '>=', $since)->count(),
            'event_registrations' => WebsiteEventRegistration::where('created_at', '>=', $since)->count(),
            'referrals' => Referral::where('created_at', '>=', $since)->count(),
            'enquiry_sources' => ConversionEvent::where('event_type', 'enquiry')
                ->where('occurred_at', '>=', $since)
                ->get()
                ->groupBy(fn ($e) => data_get($e->metadata, 'source', 'unknown'))
                ->map(fn ($group, $source) => (object) ['source' => $source, 'total' => $group->count()])
                ->values(),
        ];
    }

    public function recommendations(): array
    {
        $recs = [];
        $weakPages = PageView::select('page', DB::raw('COUNT(*) as views'))
            ->where('viewed_at', '>=', now()->subDays(30))
            ->groupBy('page')
            ->having('views', '<', 10)
            ->limit(5)
            ->pluck('page');

        foreach ($weakPages as $page) {
            $recs[] = ['type' => 'optimize_page', 'message' => "Low traffic on {$page} — refresh content or SEO meta."];
        }

        $lowCtas = WebsiteCta::where('is_active', true)->where('click_count', '<', 5)->limit(3)->get();
        foreach ($lowCtas as $cta) {
            $recs[] = ['type' => 'cta', 'message' => "CTA \"{$cta->label}\" underperforming — test placement or copy."];
        }

        $keywords = \App\Models\Website\SeoKeyword::where('priority', 'high')->whereNull('position')->limit(3)->get();
        foreach ($keywords as $kw) {
            $recs[] = ['type' => 'seo', 'message' => "Target keyword \"{$kw->keyword}\" — assign to a page and publish supporting content."];
        }

        if (Blog::where('published', true)->where('published_at', '<', now()->subDays(45))->exists()) {
            $recs[] = ['type' => 'content', 'message' => 'Publish a fresh blog post — last content may be aging.'];
        }

        return $recs;
    }
}
