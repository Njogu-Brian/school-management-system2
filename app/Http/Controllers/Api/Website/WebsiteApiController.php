<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreEnquiryRequest;
use App\Http\Resources\Website\BlogResource;
use App\Http\Resources\Website\FaqResource;
use App\Http\Resources\Website\MediaLibraryResource;
use App\Http\Resources\Website\PageResource;
use App\Http\Resources\Website\TestimonialResource;
use App\Http\Resources\Website\WebsiteEventResource;
use App\Http\Resources\Website\WebsiteSettingResource;
use App\Models\Website\Blog;
use App\Models\Website\Enquiry;
use App\Models\Website\Faq;
use App\Models\Website\MediaLibraryItem;
use App\Models\Website\Page;
use App\Models\Website\Testimonial;
use App\Models\Website\WebsiteEvent;
use App\Models\Website\WebsiteSetting;
use App\Services\Website\WebsiteErpIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WebsiteApiController extends Controller
{
    public function __construct(private WebsiteErpIntegrationService $erp)
    {
    }

    public function settings(): JsonResponse
    {
        $data = Cache::remember('website.api.settings', 600, fn () => WebsiteSettingResource::make(WebsiteSetting::current())->resolve());

        return response()->json(['data' => $data]);
    }

    public function homepage(): JsonResponse
    {
        $payload = Cache::remember('website.api.homepage', 300, function () {
            $page = Page::query()
                ->where('is_homepage', true)
                ->where('status', Page::STATUS_PUBLISHED)
                ->with(['activeSections'])
                ->first();

            if (! $page) {
                return null;
            }

            $erp = $this->erp;

            return [
                'page' => PageResource::make($page)->resolve(),
                'live_stats' => $erp->liveStats(),
                'announcements' => $erp->announcements(8),
                'achievements' => $erp->achievements(8),
            ];
        });

        abort_if($payload === null, 404, 'Homepage not configured');

        return response()->json(['data' => $payload]);
    }

    public function page(string $slug): JsonResponse
    {
        $page = Cache::remember("website.api.page.{$slug}", 300, function () use ($slug) {
            return Page::query()
                ->where('slug', $slug)
                ->where('status', Page::STATUS_PUBLISHED)
                ->with(['activeSections'])
                ->first();
        });

        abort_if(! $page, 404);

        return response()->json(['data' => PageResource::make($page)]);
    }

    public function blogs(Request $request): JsonResponse
    {
        $blogs = Blog::query()
            ->where('published', true)
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->latest('published_at')
            ->paginate((int) $request->get('per_page', 12));

        return BlogResource::collection($blogs)->response();
    }

    public function blog(string $slug): JsonResponse
    {
        $blog = Blog::query()->where('slug', $slug)->where('published', true)->firstOrFail();
        $blog->increment('views_count');

        return response()->json(['data' => BlogResource::make($blog)]);
    }

    public function searchBlogs(Request $request): JsonResponse
    {
        $blogs = Blog::query()
            ->where('published', true)
            ->when($request->q, fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('title', 'like', "%{$term}%")
                    ->orWhere('excerpt', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%");
            }))
            ->when($request->category, fn ($q, $slug) => $q->whereHas('categories', fn ($c) => $c->where('slug', $slug)))
            ->when($request->tag, fn ($q, $slug) => $q->whereHas('tags', fn ($t) => $t->where('slug', $slug)))
            ->latest('published_at')
            ->paginate((int) $request->get('per_page', 12));

        return BlogResource::collection($blogs)->response();
    }

    public function events(Request $request): JsonResponse
    {
        $cmsEvents = WebsiteEvent::query()
            ->when($request->upcoming, fn ($q) => $q->where('start_date', '>=', now()->toDateString()))
            ->orderBy('start_date')
            ->get()
            ->map(function ($e) {
                $item = WebsiteEventResource::make($e)->resolve();
                $item['source'] = 'cms';

                return $item;
            });

        $erpEvents = collect($this->erp->upcomingErpEvents(20));

        $merged = $cmsEvents->concat($erpEvents)->sortBy('start_date')->values();

        return response()->json(['data' => $merged]);
    }

    public function event(string $slug): JsonResponse
    {
        $event = WebsiteEvent::query()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => WebsiteEventResource::make($event)]);
    }

    public function testimonials(Request $request): JsonResponse
    {
        $premium = $request->boolean('premium');

        $items = Cache::remember('website.api.testimonials', 600, fn () => Testimonial::query()
            ->with(['mediaItem.qualityFlag'])
            ->where('approved', true)
            ->orderByDesc('featured')
            ->latest()
            ->get());

        $request->merge(['premium' => $premium]);

        return response()->json(['data' => TestimonialResource::collection($items)]);
    }

    public function gallery(Request $request): JsonResponse
    {
        $premium = $request->boolean('premium');
        $hero = $request->boolean('hero');
        $usePriority = $premium || $hero;

        $cacheKey = 'website.api.gallery.'
            .($request->category ?? 'all').'.'
            .($premium ? 'premium' : 'std').'.'
            .($hero ? 'hero' : 'nohero').'.'
            .($request->get('per_page', 24));

        $items = Cache::remember($cacheKey, 300, function () use ($request, $premium, $hero, $usePriority) {
            $query = MediaLibraryItem::query()
                ->with('qualityFlag')
                ->where('type', 'image')
                ->when($request->category, fn ($q, $c) => $q->where('category', $c))
                ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
                ->when($premium, fn ($q) => $q->premiumApproved())
                ->when($hero, fn ($q) => $q->heroApproved());

            if ($usePriority) {
                $query->orderByMediaPriority();
            } else {
                $query->latest();
            }

            return $query->paginate((int) $request->get('per_page', 24));
        });

        return MediaLibraryResource::collection($items)->response();
    }

    public function heroMedia(): JsonResponse
    {
        $item = Cache::remember('website.api.media.hero', 300, fn () => MediaLibraryItem::query()
            ->with('qualityFlag')
            ->where('type', 'image')
            ->heroApproved()
            ->orderByMediaPriority()
            ->first());

        return response()->json([
            'data' => $item ? MediaLibraryResource::make($item)->resolve() : null,
        ]);
    }

    public function faqs(Request $request): JsonResponse
    {
        $faqs = Cache::remember('website.api.faqs.'.($request->category ?? 'all'), 600, fn () => Faq::query()
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->orderBy('order')
            ->get());

        return response()->json(['data' => FaqResource::collection($faqs)]);
    }

    public function enquiry(StoreEnquiryRequest $request): JsonResponse
    {
        $enquiry = Enquiry::create($request->validated() + [
            'source' => $request->input('source', 'website'),
            'status' => Enquiry::STATUS_NEW,
        ]);

        Cache::forget('website.api.settings');

        return response()->json([
            'message' => 'Thank you! Our admissions team will contact you shortly.',
            'data' => ['id' => $enquiry->id],
        ], 201);
    }
}
