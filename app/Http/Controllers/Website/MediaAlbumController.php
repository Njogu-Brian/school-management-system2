<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\MediaAlbum;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaAlbumController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index(): View
    {
        $albums = MediaAlbum::query()->withCount('items')->latest()->paginate(20);

        return view('website.albums.index', compact('albums'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        MediaAlbum::create($validated);

        return back()->with('success', 'Album created.');
    }
}
