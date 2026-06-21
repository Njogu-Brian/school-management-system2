<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Policies\Website\ManagesWebsiteCms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentAssistantController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct()
    {
        $this->middleware(fn ($r, $n) => $this->canManageWebsite($r->user()) ? $n($r) : abort(403));
    }

    public function prompt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|in:admissions,parenting,cbc,events',
            'subject' => 'required|string|max:255',
        ]);

        $templates = [
            'admissions' => "Write a warm, Christian-centered admissions article for Royal Kings Education Centre about: {$validated['subject']}. Highlight Creche to Grade 9, family-friendly values, and a clear call to apply.",
            'parenting' => "Write a helpful parenting tips article for Royal Kings parents about: {$validated['subject']}. Tone: supportive, faith-informed, practical.",
            'cbc' => "Explain this CBC topic for parents in simple language: {$validated['subject']}. Include how Royal Kings supports competency-based learning.",
            'events' => "Write a school event recap/promo post for Royal Kings about: {$validated['subject']}. Include date, who it's for, and how to register.",
        ];

        return response()->json([
            'prompt' => $templates[$validated['topic']],
            'note' => 'Copy this prompt into your preferred AI writing tool, then paste the result into the blog CMS.',
        ]);
    }
}
