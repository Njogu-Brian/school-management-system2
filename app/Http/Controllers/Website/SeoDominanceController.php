<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Page;
use App\Models\Website\SeoKeyword;
use App\Models\Website\ServiceAreaPage;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\SeoDominanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoDominanceController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(SeoDominanceService $seo): View
    {
        return view('website.seo.engine', [
            'keywords' => SeoKeyword::with('page')->orderByDesc('priority')->get(),
            'areas' => ServiceAreaPage::orderBy('area_name')->get(),
            'pages' => Page::orderBy('name')->get(['id', 'name', 'slug']),
            'duplicates' => $seo->detectDuplicateTitles(),
            'nap' => $seo->napConsistency(),
        ]);
    }

    public function storeKeyword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'page_id' => 'nullable|exists:pages,id',
            'target_url' => 'nullable|string|max:500',
            'search_volume' => 'nullable|integer|min:0',
            'priority' => 'required|in:low,medium,high',
        ]);

        SeoKeyword::create($validated);

        return back()->with('success', 'Keyword added.');
    }

    public function updateArea(Request $request, ServiceAreaPage $area): RedirectResponse
    {
        $validated = $request->validate([
            'headline' => 'required|string|max:255',
            'description' => 'nullable|string',
            'map_embed' => 'nullable|string',
            'published' => 'nullable|boolean',
        ]);

        $area->update([
            ...$validated,
            'published' => $request->boolean('published', $area->published),
        ]);

        return back()->with('success', 'Service area updated.');
    }

    public function scorePage(Request $request, Page $page, SeoDominanceService $seo): RedirectResponse
    {
        $keyword = SeoKeyword::where('page_id', $page->id)->value('keyword');
        $score = $seo->scoreContent($page->title ?? $page->name, $page->meta_description ?? '', $keyword);

        return back()->with('success', "SEO score for {$page->name}: {$score['seo_score']}/100");
    }
}
