<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\ExitIntentCampaign;
use App\Models\Website\LeadMagnet;
use App\Models\Website\WebsiteCta;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\ConversionEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversionManagerController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(ConversionEngineService $conversion): View
    {
        return view('website.conversion.index', [
            'ctas' => WebsiteCta::orderBy('name')->get(),
            'campaigns' => ExitIntentCampaign::latest()->get(),
            'magnets' => LeadMagnet::orderBy('title')->get(),
            'stats' => $conversion->conversionStats(),
        ]);
    }

    public function storeCta(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cta_type' => 'required|in:apply_now,book_visit,call_now,whatsapp,custom',
            'label' => 'required|string|max:255',
            'url' => 'nullable|string|max:500',
            'placement' => 'required|in:global,page',
            'pages' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        WebsiteCta::create([
            ...$validated,
            'pages' => $validated['placement'] === 'page' && $validated['pages']
                ? array_map('trim', explode(',', $validated['pages']))
                : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'CTA created.');
    }

    public function storeExitIntent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'nullable|string',
            'button_label' => 'required|string|max:100',
            'button_url' => 'nullable|string|max:500',
            'pages' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        ExitIntentCampaign::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Exit intent campaign created.');
    }

    public function storeLeadMagnet(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:lead_magnets,slug',
            'description' => 'nullable|string',
            'file_path' => 'nullable|string|max:500',
            'published' => 'nullable|boolean',
        ]);

        LeadMagnet::create([
            ...$validated,
            'published' => $request->boolean('published', false),
        ]);

        return back()->with('success', 'Lead magnet created.');
    }
}
