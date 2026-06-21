<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\ExecutiveIntelligenceService;
use App\Models\Website\ExecutiveAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecutiveIntelligenceApiController extends Controller
{
    public function kpis(ExecutiveIntelligenceService $service): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $service->kpis()]);
    }

    public function trends(Request $request, ExecutiveIntelligenceService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->erpExecutiveTrends($request),
        ]);
    }

    public function alerts(): JsonResponse
    {
        $alerts = ExecutiveAlert::query()
            ->where('acknowledged', false)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json(['success' => true, 'data' => $alerts]);
    }

    public function acknowledge(Request $request, int $id): JsonResponse
    {
        $alert = ExecutiveAlert::findOrFail($id);
        $alert->update([
            'acknowledged' => true,
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => $alert]);
    }
}
