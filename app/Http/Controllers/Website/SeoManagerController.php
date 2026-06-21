<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Page;
use App\Models\Website\WebsiteSetting;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoManagerController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->canManageWebsite($request->user()), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $settings = WebsiteSetting::current();
        $pages = Page::query()->orderBy('name')->get(['id', 'name', 'slug', 'meta_title', 'meta_description', 'status']);

        return view('website.seo.index', compact('settings', 'pages'));
    }

    public function updateDefaults(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'seo_defaults' => 'required|array',
            'seo_defaults.title' => 'nullable|string|max:255',
            'seo_defaults.description' => 'nullable|string|max:2000',
            'seo_defaults.keywords' => 'nullable|string|max:500',
            'seo_defaults.og_image' => 'nullable|string|max:500',
        ]);

        WebsiteSetting::current()->update(['seo_defaults' => $validated['seo_defaults']]);

        return back()->with('success', 'Default SEO settings updated.');
    }

    public function updatePage(Request $request, Page $page): RedirectResponse
    {
        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:2000',
        ]);

        $page->update($validated);

        return back()->with('success', 'Page SEO updated.');
    }
}
