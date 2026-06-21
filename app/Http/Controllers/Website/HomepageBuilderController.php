<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Page;
use App\Models\Website\PageSection;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageBuilderController extends Controller
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
        $page = Page::query()->firstOrCreate(
            ['is_homepage' => true],
            [
                'name' => 'Homepage',
                'slug' => 'home',
                'title' => 'Royal Kings Education Centre',
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
            ]
        );

        $sections = $page->sections()->orderBy('sort_order')->get();

        return view('website.homepage.index', compact('page', 'sections'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'page_id' => 'required|exists:pages,id',
            'section_type' => 'required|string|max:100',
            'section_key' => 'required|string|max:100',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'settings' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        PageSection::create($validated + [
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return back()->with('success', 'Section added.');
    }

    public function update(Request $request, PageSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'section_type' => 'required|string|max:100',
            'section_key' => 'required|string|max:100',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'settings' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $section->update($validated + [
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? $section->sort_order),
        ]);

        return back()->with('success', 'Section updated.');
    }

    public function destroy(PageSection $section): RedirectResponse
    {
        $section->delete();

        return back()->with('success', 'Section removed.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:page_sections,id',
        ]);

        foreach ($validated['order'] as $position => $id) {
            PageSection::where('id', $id)->update(['sort_order' => $position]);
        }

        return back()->with('success', 'Section order updated.');
    }
}
