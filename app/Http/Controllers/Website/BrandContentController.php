<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\WebsiteBrandItem;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class BrandContentController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(): View
    {
        $items = WebsiteBrandItem::query()->orderBy('block_type')->orderBy('sort_order')->get();
        $types = [
            WebsiteBrandItem::TYPE_TRUST_PILL => 'Trust pills (homepage)',
            WebsiteBrandItem::TYPE_SCHOOL_CARD => 'Our Schools cards',
            WebsiteBrandItem::TYPE_JOURNEY_MILESTONE => 'One Journey timeline',
            WebsiteBrandItem::TYPE_COCURRICULAR => 'Beyond the Classroom',
            WebsiteBrandItem::TYPE_FAITH_PILLAR => 'Faith pillars',
            WebsiteBrandItem::TYPE_LEADER => 'Leadership',
            WebsiteBrandItem::TYPE_SCRIPTURE => 'Weekly scripture',
            WebsiteBrandItem::TYPE_CHAPLAIN => 'Chaplain message',
            WebsiteBrandItem::TYPE_PRAYER_HIGHLIGHT => 'Prayer highlights',
        ];

        return view('website.brand.index', compact('items', 'types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'block_type' => 'required|string|max:64',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'image_url' => 'nullable|string|max:500',
            'link_url' => 'nullable|string|max:500',
            'video_url' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        WebsiteBrandItem::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        Cache::forget('website.api.brand');

        return back()->with('success', 'Brand item added.');
    }

    public function update(Request $request, WebsiteBrandItem $brandItem): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'image_url' => 'nullable|string|max:500',
            'link_url' => 'nullable|string|max:500',
            'video_url' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $brandItem->update([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        Cache::forget('website.api.brand');

        return back()->with('success', 'Brand item updated.');
    }

    public function destroy(WebsiteBrandItem $brandItem): RedirectResponse
    {
        $brandItem->delete();
        Cache::forget('website.api.brand');

        return back()->with('success', 'Brand item removed.');
    }
}
