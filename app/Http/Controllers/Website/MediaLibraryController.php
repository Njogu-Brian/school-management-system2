<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\MediaLibraryItem;
use App\Models\Website\MediaQualityFlag;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\WebsiteMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaLibraryController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct(private WebsiteMediaService $media)
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->canManageWebsite($request->user()), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $query = MediaLibraryItem::query()->with('uploader')->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $items = $query->with('qualityFlag')->paginate(24);
        $categories = MediaLibraryItem::query()->whereNotNull('category')->distinct()->pluck('category');

        return view('website.media.index', compact('items', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|max:10240',
            'category' => 'nullable|string|max:100',
            'alt_text' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $subdir = $validated['category'] ?? 'general';

        MediaLibraryItem::create([
            'title' => $validated['title'],
            'file_path' => $this->media->store($file, $subdir),
            'type' => $this->media->detectType($file),
            'category' => $validated['category'] ?? null,
            'alt_text' => $validated['alt_text'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Media uploaded.');
    }

    public function destroy(MediaLibraryItem $mediaLibraryItem): RedirectResponse
    {
        $this->media->delete($mediaLibraryItem->file_path);
        $mediaLibraryItem->delete();

        return back()->with('success', 'Media deleted.');
    }

    public function updateQuality(Request $request, MediaLibraryItem $mediaLibraryItem): RedirectResponse
    {
        $validated = $request->validate([
            'approved' => 'nullable|boolean',
            'hero_ready' => 'nullable|boolean',
            'homepage_ready' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        MediaQualityFlag::query()->updateOrCreate(
            ['media_id' => $mediaLibraryItem->id],
            [
                'approved' => $request->boolean('approved'),
                'hero_ready' => $request->boolean('hero_ready'),
                'homepage_ready' => $request->boolean('homepage_ready'),
                'priority' => (int) ($validated['priority'] ?? 0),
            ]
        );

        return back()->with('success', 'Photo quality flags updated.');
    }
}
