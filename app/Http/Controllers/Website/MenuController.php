<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\WebsiteMenu;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(): View
    {
        $menus = WebsiteMenu::query()->with('items')->get();

        return view('website.menus.index', compact('menus'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:100',
            'items' => 'nullable|array',
            'items.*.title' => 'required_with:items|string|max:255',
            'items.*.url' => 'required_with:items|string|max:500',
        ]);

        $menu = WebsiteMenu::create($validated);
        foreach ($validated['items'] ?? [] as $i => $item) {
            $menu->items()->create(['title' => $item['title'], 'url' => $item['url'], 'sort_order' => $i]);
        }

        return back()->with('success', 'Menu saved.');
    }
}
