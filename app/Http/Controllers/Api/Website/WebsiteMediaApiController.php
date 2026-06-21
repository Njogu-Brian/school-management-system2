<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Resources\Website\MediaLibraryResource;
use App\Models\Website\MediaAlbum;
use App\Models\Website\VirtualTourStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteMediaApiController extends Controller
{
    public function albums(Request $request): JsonResponse
    {
        $albums = MediaAlbum::query()
            ->with(['items' => fn ($q) => $q->latest()->limit(12)])
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->orderByDesc('is_featured')
            ->get()
            ->map(fn ($album) => [
                'id' => $album->id,
                'title' => $album->title,
                'slug' => $album->slug,
                'category' => $album->category,
                'cover_image' => $album->coverImageUrl(),
                'items' => MediaLibraryResource::collection($album->items),
            ]);

        return response()->json(['data' => $albums]);
    }

    public function virtualTour(): JsonResponse
    {
        $stops = VirtualTourStop::query()->where('is_active', true)->orderBy('sort_order')->get();

        return response()->json(['data' => $stops]);
    }
}
