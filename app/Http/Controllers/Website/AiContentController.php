<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\AiContentLog;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\SchoolAiContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiContentController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function index()
    {
        $logs = AiContentLog::with('user')->latest()->paginate(30);

        return view('website.ai.index', compact('logs'));
    }

    public function generate(Request $request, SchoolAiContentService $service): JsonResponse
    {
        $validated = $request->validate([
            'content_type' => 'required|in:'.implode(',', AiContentLog::TYPES),
            'subject' => 'required|string|max:500',
        ]);

        $log = $service->generate($request->user(), $validated['content_type'], $validated['subject']);

        return response()->json(['success' => true, 'data' => $log]);
    }
}
