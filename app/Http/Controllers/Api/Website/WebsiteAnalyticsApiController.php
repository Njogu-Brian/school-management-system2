<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\WebsiteAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteAnalyticsApiController extends Controller
{
    public function __construct(private WebsiteAnalyticsService $analytics)
    {
    }

    public function trackView(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'required|string|max:500',
            'visitor_id' => 'nullable|string|max:64',
            'device' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:255',
            'duration' => 'nullable|integer|min:0',
        ]);

        $this->analytics->trackPageView(
            $validated['page'],
            $validated['visitor_id'] ?? null,
            $validated['device'] ?? null,
            $validated['source'] ?? null,
            (int) ($validated['duration'] ?? 0),
        );

        return response()->json(['ok' => true]);
    }

    public function trackEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:100',
            'page' => 'nullable|string|max:500',
            'visitor_id' => 'nullable|string|max:64',
            'metadata' => 'nullable|array',
        ]);

        $this->analytics->trackConversion(
            $validated['event_type'],
            $validated['page'] ?? null,
            $validated['metadata'] ?? [],
            $validated['visitor_id'] ?? null,
        );

        return response()->json(['ok' => true]);
    }
}
