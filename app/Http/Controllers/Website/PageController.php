<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StorePageRequest;
use App\Http\Requests\Website\UpdatePageRequest;
use App\Models\Website\Page;
use App\Models\Website\PageRevision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Page::class, 'page');
    }

    public function index(): View
    {
        $pages = Page::query()->orderByDesc('is_homepage')->orderBy('name')->paginate(20);

        return view('website.pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('website.pages.create');
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        Page::create($request->validated() + [
            'is_homepage' => $request->boolean('is_homepage'),
        ]);

        return redirect()->route('website.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('website.pages.edit', compact('page'));
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        PageRevision::create([
            'page_id' => $page->id,
            'snapshot' => $page->only(['name', 'slug', 'title', 'meta_title', 'meta_description', 'status']),
            'created_by' => auth()->id(),
        ]);

        $page->update($request->validated() + [
            'is_homepage' => $request->boolean('is_homepage'),
        ]);

        return redirect()->route('website.pages.index')->with('success', 'Page updated.');
    }

    public function clone(Page $page): RedirectResponse
    {
        $this->authorize('create', Page::class);

        $copy = $page->replicate(['is_homepage', 'preview_token']);
        $copy->name = $page->name.' (Copy)';
        $copy->slug = $page->slug.'-copy-'.Str::random(4);
        $copy->status = Page::STATUS_DRAFT;
        $copy->is_homepage = false;
        $copy->save();

        foreach ($page->sections as $section) {
            $copy->sections()->create($section->only([
                'section_type', 'section_key', 'title', 'subtitle', 'content', 'settings', 'sort_order', 'is_active',
            ]));
        }

        return redirect()->route('website.pages.edit', $copy)->with('success', 'Page cloned.');
    }

    public function preview(Page $page): View
    {
        $this->authorize('view', $page);
        $page->load('sections');

        return view('website.pages.preview', compact('page'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('website.pages.index')->with('success', 'Page deleted.');
    }
}
