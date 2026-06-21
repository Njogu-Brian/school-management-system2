<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\CampaignLog;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\CampaignAutomationService;
use App\Services\Website\NewsletterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct(
        private NewsletterService $newsletter,
        private CampaignAutomationService $automation,
    ) {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(): View
    {
        $campaigns = CampaignLog::query()->latest()->paginate(20);

        return view('website.campaigns.index', compact('campaigns'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campaign_name' => 'required|string|max:255',
            'type' => 'required|in:newsletter,abandoned_admissions,event_reminder',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
        ]);

        if ($validated['type'] === 'abandoned_admissions') {
            $sent = $this->automation->sendAbandonedAdmissionReminders();

            return back()->with('success', "Abandoned admission reminders sent: {$sent}");
        }

        $this->newsletter->sendCampaign(
            $validated['campaign_name'],
            $validated['subject'] ?? $validated['campaign_name'],
            $validated['body'] ?? 'Royal Kings Education Centre update.',
        );

        return back()->with('success', 'Campaign queued/sent.');
    }
}
