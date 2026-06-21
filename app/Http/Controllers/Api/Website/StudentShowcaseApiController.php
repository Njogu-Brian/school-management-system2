<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\StudentSpotlight;
use App\Models\Website\WebsiteCompetition;
use App\Services\Website\WebsiteErpIntegrationService;
use Illuminate\Http\JsonResponse;

class StudentShowcaseApiController extends Controller
{
    public function index(WebsiteErpIntegrationService $erp): JsonResponse
    {
        $spotlights = StudentSpotlight::query()
            ->where('published', true)
            ->with(['student:id,first_name,last_name,classroom_id', 'student.classroom:id,name'])
            ->orderByDesc('featured')
            ->latest()
            ->limit(24)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'story' => $s->story,
                'achievement' => $s->achievement,
                'cover_image' => $s->coverImageUrl(),
                'featured' => $s->featured,
                'student' => $s->student ? trim($s->student->first_name.' '.$s->student->last_name) : null,
                'classroom' => $s->student?->classroom?->name,
            ]);

        $competitions = WebsiteCompetition::query()
            ->where('published', true)
            ->orderByDesc('date')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'spotlights' => $spotlights,
                'competitions' => $competitions,
                'erp_achievements' => $erp->achievements(12),
            ],
        ]);
    }
}
