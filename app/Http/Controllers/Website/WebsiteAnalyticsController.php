<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\WebsiteAnalyticsService;
use Illuminate\View\View;

class WebsiteAnalyticsController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct(private WebsiteAnalyticsService $analytics)
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(): View
    {
        $summary = $this->analytics->dashboardSummary(30);

        return view('website.analytics.index', compact('summary'));
    }
}
