<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Jobs\Website\ComputeExecutiveAlertsJob;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\ExecutiveIntelligenceService;
use Illuminate\Http\Request;

class ExecutiveDashboardController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(ExecutiveIntelligenceService $service)
    {
        return view('website.executive.index', [
            'kpis' => $service->kpis(),
            'alerts' => \App\Models\Website\ExecutiveAlert::where('acknowledged', false)->latest()->limit(20)->get(),
        ]);
    }

    public function refreshAlerts()
    {
        ComputeExecutiveAlertsJob::dispatch();

        return back()->with('success', 'Predictive alerts queued for computation.');
    }
}
