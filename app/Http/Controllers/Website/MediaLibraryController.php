<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\MediaLibraryItem;
use App\Models\Website\MediaQualityFlag;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\MediaOptimizationService;
use App\Services\Website\WebsiteMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaLibraryController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct(
        private WebsiteMediaService $media,
        private MediaOptimizationService $optimizer,
    ) {
        $this->middleware(function ($request, $next) {
            abort_unless($this->canManageWebsite($request->user()), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $query = MediaLibraryItem::query()->with(['uploader', 'qualityFlag'])->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('hero_ready')) {
            $query->heroApproved();
        }

        if ($request->boolean('homepage_ready')) {
            $query->premiumApproved();
        }

        if ($request->boolean('approved')) {
            $query->whereHas('qualityFlag', fn ($f) => $f->where('approved', true));
        }

        $items = $query->paginate(24)->withQueryString();
        $categories = MediaLibraryItem::query()->whereNotNull('category')->distinct()->pluck('category');

        return view('website.media.index', [
            'items' => $items,
            'categories' => $categories,
            'optimizerReady' => $this->optimizer->supportsOptimization(),
        ]);
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

        $item = MediaLibraryItem::create([
            'title' => $validated['title'],
            'file_path' => $this->media->store($file, $subdir),
            'type' => $this->media->detectType($file),
            'category' => $validated['category'] ?? null,
            'alt_text' => $validated['alt_text'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'uploaded_by' => $request->user()->id,
            'optimization_status' => MediaLibraryItem::OPT_PENDING,
        ]);

        if ($item->type === 'image') {
            $this->optimizer->optimize($item);
        }

        return back()->with('success', 'Media uploaded'.($item->optimization_status === 'completed' ? ' and optimized.' : '.'));
    }

    public function destroy(MediaLibraryItem $mediaLibraryItem): RedirectResponse
    {
        if ($this->media->usages($mediaLibraryItem) !== []) {
            return redirect()
                ->route('website.media.replace', $mediaLibraryItem)
                ->withErrors(['delete' => 'This image is used on the website. Choose a replacement before it can be removed.']);
        }

        $this->media->deleteItem($mediaLibraryItem, $this->optimizer);
        $mediaLibraryItem->delete();

        return back()->with('success', 'Media deleted.');
    }

    public function replaceForm(MediaLibraryItem $mediaLibraryItem): View
    {
        return view('website.media.replace', [
            'item' => $mediaLibraryItem,
            'usages' => $this->media->usages($mediaLibraryItem),
            'library' => MediaLibraryItem::query()
                ->where('id', '!=', $mediaLibraryItem->id)
                ->where('type', 'image')
                ->latest()
                ->limit(80)
                ->get(),
        ]);
    }

    public function replace(Request $request, MediaLibraryItem $mediaLibraryItem): RedirectResponse
    {
        $validated = $request->validate([
            'replacement_id' => 'nullable|integer|exists:media_library,id',
            'replacement_file' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp',
        ]);

        $replacement = null;
        if ($request->hasFile('replacement_file')) {
            $replacement = $this->media->storeLibraryImage(
                $request->file('replacement_file'),
                (string) $request->input('replacement_title', 'Replacement image'),
                $request->user()?->id
            );
        } elseif (! empty($validated['replacement_id'])) {
            $replacement = MediaLibraryItem::query()->find($validated['replacement_id']);
        }

        if (! $replacement || $replacement->id === $mediaLibraryItem->id) {
            return back()->withErrors(['replacement_id' => 'Choose an existing image or upload a new one.']);
        }

        $this->media->replaceEverywhere($mediaLibraryItem, $replacement);
        $this->media->deleteItem($mediaLibraryItem, $this->optimizer);
        $mediaLibraryItem->delete();

        \Illuminate\Support\Facades\Cache::forget('website.api.homepage');
        \Illuminate\Support\Facades\Cache::forget('website.api.settings.v2');
        \Illuminate\Support\Facades\Cache::forget('website.api.media.hero');
        \Illuminate\Support\Facades\Cache::forget('website.api.brand');

        return redirect()->route('website.media.index')->with('success', 'Image replaced and the original was deleted.');
    }

    public function updateQuality(Request $request, MediaLibraryItem $mediaLibraryItem): RedirectResponse
    {
        $validated = $request->validate([
            'approved' => 'nullable|boolean',
            'hero_ready' => 'nullable|boolean',
            'homepage_ready' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        $approved = $request->boolean('approved');
        $heroReady = $request->boolean('hero_ready');
        $homepageReady = $request->boolean('homepage_ready');

        if (($heroReady || $homepageReady) && ! $approved) {
            return back()->withErrors(['approved' => 'Images must be approved before marking as Hero or Homepage ready.']);
        }

        MediaQualityFlag::query()->updateOrCreate(
            ['media_id' => $mediaLibraryItem->id],
            [
                'approved' => $approved,
                'hero_ready' => $heroReady,
                'homepage_ready' => $homepageReady,
                'priority' => (int) ($validated['priority'] ?? 0),
            ]
        );

        \Illuminate\Support\Facades\Cache::forget('website.api.media.hero');
        \Illuminate\Support\Facades\Cache::forget('website.api.testimonials');

        return back()->with('success', 'Photo quality flags updated.');
    }

    public function optimize(MediaLibraryItem $mediaLibraryItem): RedirectResponse
    {
        if ($mediaLibraryItem->type !== 'image') {
            return back()->withErrors(['optimize' => 'Only images can be optimized.']);
        }

        if (! $this->optimizer->supportsOptimization()) {
            return back()->withErrors(['optimize' => 'Server GD/WebP support is not available.']);
        }

        $this->optimizer->optimize($mediaLibraryItem->fresh());

        return back()->with('success', 'Image variants regenerated.');
    }
}
