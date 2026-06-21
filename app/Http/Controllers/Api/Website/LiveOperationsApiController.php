<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\LiveOperationsService;
use Illuminate\Http\JsonResponse;

class LiveOperationsApiController extends Controller
{
    public function dashboard(LiveOperationsService $live): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $live->dashboard()]);
    }

    public function noticeboard(LiveOperationsService $live): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $live->noticeboard()]);
    }

    public function schoolStatus(LiveOperationsService $live): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $live->schoolStatus()]);
    }

    public function meals(LiveOperationsService $live): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $live->weeklyMeals()]);
    }
}
