<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\LeadMagnet;
use App\Services\Website\ConversionEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversionApiController extends Controller
{
    public function ctas(Request $request, ConversionEngineService $conversion): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $conversion->activeCtas($request->query('page')),
        ]);
    }

    public function trackCta(Request $request, ConversionEngineService $conversion): JsonResponse
    {
        $validated = $request->validate([
            'cta_id' => 'required|integer|exists:website_ctas,id',
            'page' => 'nullable|string|max:255',
            'visitor_id' => 'nullable|string|max:64',
        ]);

        $conversion->trackCtaClick($validated['cta_id'], $validated['page'] ?? null, $validated['visitor_id'] ?? null);

        return response()->json(['success' => true]);
    }

    public function exitIntent(Request $request, ConversionEngineService $conversion): JsonResponse
    {
        $campaign = $conversion->activeExitIntent($request->query('page'));

        return response()->json(['success' => true, 'data' => $campaign]);
    }

    public function exitConvert(Request $request, ConversionEngineService $conversion): JsonResponse
    {
        $validated = $request->validate(['campaign_id' => 'required|integer']);
        $conversion->recordExitConversion((int) $validated['campaign_id']);

        return response()->json(['success' => true]);
    }

    public function leadMagnets(ConversionEngineService $conversion): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $conversion->leadMagnets()]);
    }

    public function downloadLeadMagnet(Request $request, string $slug, ConversionEngineService $conversion): JsonResponse
    {
        $magnet = LeadMagnet::where('slug', $slug)->where('published', true)->firstOrFail();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:30',
        ]);

        $conversion->captureLeadMagnetDownload($magnet, $validated);

        return response()->json([
            'success' => true,
            'data' => ['download_url' => $magnet->file_path ? asset('website/'.$magnet->file_path) : null],
        ]);
    }
}
