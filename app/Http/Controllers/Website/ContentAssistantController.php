<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\AiContentLog;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Website\SchoolAiContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentAssistantController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    /** @deprecated Use AiContentController::generate — kept for backward compatibility */
    public function prompt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|in:admissions,parenting,cbc,events,blog,announcement,newsletter,social',
            'subject' => 'required|string|max:255',
        ]);

        $map = [
            'admissions' => 'admissions_copy',
            'parenting' => 'parent_message',
            'cbc' => 'blog',
            'events' => 'event_recap',
            'blog' => 'blog',
            'announcement' => 'announcement',
            'newsletter' => 'newsletter',
            'social' => 'social_media_caption',
        ];

        $log = app(SchoolAiContentService::class)->generate(
            $request->user(),
            $map[$validated['topic']] ?? 'blog',
            $validated['subject']
        );

        return response()->json([
            'success' => true,
            'data' => $log,
            'message' => 'AI content generation queued. Poll log id for output.',
        ]);
    }
}
