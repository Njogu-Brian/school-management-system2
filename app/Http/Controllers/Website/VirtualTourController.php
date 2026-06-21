<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\VirtualTourStop;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VirtualTourController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(): View
    {
        $stops = VirtualTourStop::query()->orderBy('sort_order')->get();

        return view('website.virtual_tour.index', compact('stops'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        VirtualTourStop::create($validated + ['sort_order' => $validated['sort_order'] ?? 0]);

        return back()->with('success', 'Tour stop added.');
    }
}
