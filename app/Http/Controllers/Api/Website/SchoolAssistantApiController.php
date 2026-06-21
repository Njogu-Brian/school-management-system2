<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\SchoolAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolAssistantApiController extends Controller
{
    public function chat(Request $request, SchoolAssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'session_key' => 'nullable|uuid',
            'page_path' => 'nullable|string|max:255',
        ]);

        $session = $assistant->getOrCreateSession($validated['session_key'] ?? null, $validated['page_path'] ?? null);
        $result = $assistant->chat($session, $validated['message'], $validated['page_path'] ?? null);

        return response()->json(['success' => true, 'data' => $result]);
    }
}
