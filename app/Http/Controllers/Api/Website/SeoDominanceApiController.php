<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Page;
use App\Models\Website\ServiceAreaPage;
use App\Services\Website\SeoDominanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoDominanceApiController extends Controller
{
    public function schema(Request $request, SeoDominanceService $seo): JsonResponse
    {
        $page = null;
        if ($request->filled('slug')) {
            $page = Page::where('slug', $request->slug)->where('status', Page::STATUS_PUBLISHED)->first();
        }

        return response()->json(['success' => true, 'data' => $seo->schemaBundle($page)]);
    }

    public function score(Request $request, SeoDominanceService $seo): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'keyword' => 'nullable|string',
        ]);

        return response()->json([
            'success' => true,
            'data' => $seo->scoreContent($validated['title'], $validated['body'], $validated['keyword'] ?? null),
        ]);
    }

    public function localAreas(SeoDominanceService $seo): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $seo->localAreas()]);
    }

    public function area(string $slug): JsonResponse
    {
        $area = ServiceAreaPage::where('slug', $slug)->where('published', true)->firstOrFail();

        return response()->json(['success' => true, 'data' => $area]);
    }
}
