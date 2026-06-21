<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\ContentCalendarItem;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentCalendarController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(): View
    {
        return view('website.calendar.index', [
            'items' => ContentCalendarItem::orderBy('publish_date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:blog,event_recap,social,devotional,newsletter,holiday',
            'publish_date' => 'nullable|date',
            'status' => 'required|in:idea,draft,scheduled,published',
            'notes' => 'nullable|string',
        ]);

        ContentCalendarItem::create($validated);

        return back()->with('success', 'Calendar item added.');
    }

    public function update(Request $request, ContentCalendarItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:idea,draft,scheduled,published',
            'publish_date' => 'nullable|date',
        ]);

        $item->update($validated);

        return back()->with('success', 'Calendar item updated.');
    }
}
