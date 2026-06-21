<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\AiContentLog;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\SchoolAiContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiContentApiController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($this->canManageWebsite($request->user()), 403);

            return $next($request);
        });
    }

    public function generate(Request $request, SchoolAiContentService $service): JsonResponse
    {
        $validated = $request->validate([
            'content_type' => 'required|in:'.implode(',', AiContentLog::TYPES),
            'subject' => 'required|string|max:500',
            'sync' => 'nullable|boolean',
        ]);

        $log = $service->generate(
            $request->user(),
            $validated['content_type'],
            $validated['subject'],
            queue: ! $request->boolean('sync')
        );

        return response()->json(['success' => true, 'data' => $log]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $log = AiContentLog::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json(['success' => true, 'data' => $log]);
    }

    public function index(Request $request): JsonResponse
    {
        $logs = AiContentLog::where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json(['success' => true, 'data' => $logs]);
    }
}
