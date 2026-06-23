<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Page;
use App\Models\Website\PageBuilderSnapshot;
use App\Models\Website\PageSection;
use App\Models\Website\SectionTemplate;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\PageBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisualPageBuilderController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function show(Page $page, PageBuilderService $builder): View
    {
        $sections = $page->sections()->orderBy('sort_order')->get();
        $templates = SectionTemplate::where('is_active', true)->orderBy('name')->get();
        $snapshots = $page->builderSnapshots()->latest()->limit(10)->get();

        return view('website.builder.show', compact('page', 'sections', 'templates', 'snapshots'));
    }

    public function addSection(Request $request, Page $page, PageBuilderService $builder): RedirectResponse
    {
        $validated = $request->validate(['template_type' => 'required|string']);
        $order = (int) $page->sections()->max('sort_order') + 1;
        $builder->addSectionFromTemplate($page, $validated['template_type'], $order);
        $builder->snapshot($page, 'Before add', $request->user()?->id);

        return back()->with('success', 'Section added.');
    }

    public function updateSection(Request $request, PageSection $section, PageBuilderService $builder): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'settings' => 'nullable',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $settings = $validated['settings'] ?? $section->settings;
        if (is_string($settings) && $settings !== '') {
            $settings = json_decode($settings, true) ?? [];
        }

        $section->update(collect($validated)->except('settings')->all() + [
            'settings' => is_array($settings) ? $settings : [],
            'is_active' => $request->boolean('is_active', $section->is_active),
            'sort_order' => (int) ($validated['sort_order'] ?? $section->sort_order),
        ]);

        return back()->with('success', 'Section saved.');
    }

    public function cloneSection(PageSection $section, PageBuilderService $builder): RedirectResponse
    {
        $builder->cloneSection($section);

        return back()->with('success', 'Section cloned.');
    }

    public function toggleSection(PageSection $section): RedirectResponse
    {
        $section->update(['is_active' => ! $section->is_active]);

        return back()->with('success', 'Section toggled.');
    }

    public function destroySection(PageSection $section): RedirectResponse
    {
        $section->delete();

        return back()->with('success', 'Section removed.');
    }

    public function reorder(Request $request, Page $page, PageBuilderService $builder): JsonResponse|RedirectResponse
    {
        $validated = $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        $builder->reorder($page, $validated['order']);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Order updated.');
    }

    public function autosave(Request $request, Page $page, PageBuilderService $builder): JsonResponse
    {
        $validated = $request->validate(['sections' => 'required|array']);
        $builder->autosave($page, $validated['sections'], $request->user()?->id);

        return response()->json(['success' => true, 'saved_at' => now()->toIso8601String()]);
    }

    public function snapshot(Request $request, Page $page, PageBuilderService $builder): RedirectResponse
    {
        $builder->snapshot($page, $request->input('label'), $request->user()?->id);

        return back()->with('success', 'Version snapshot saved.');
    }

    public function restoreSnapshot(PageBuilderSnapshot $snapshot, PageBuilderService $builder): RedirectResponse
    {
        $builder->restoreSnapshot($snapshot);

        return redirect()->route('website.builder.show', $snapshot->page_id)->with('success', 'Snapshot restored.');
    }
}
