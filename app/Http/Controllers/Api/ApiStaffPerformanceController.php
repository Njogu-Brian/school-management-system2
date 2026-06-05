<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReview;
use App\Models\Staff;
use Illuminate\Http\Request;

class ApiStaffPerformanceController extends Controller
{
    public function index(Request $request, int $staffId)
    {
        Staff::findOrFail($staffId);
        $perPage = min((int) $request->input('per_page', 20), 100);

        $paginated = PerformanceReview::query()
            ->where('staff_id', $staffId)
            ->with(['reviewer'])
            ->orderByDesc('review_date')
            ->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (PerformanceReview $r) => $this->serialize($r))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'staff_id' => $staffId,
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function show(int $staffId, int $id)
    {
        $review = PerformanceReview::query()
            ->where('staff_id', $staffId)
            ->with(['reviewer', 'staff'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($review, true),
        ]);
    }

    protected function serialize(PerformanceReview $r, bool $detailed = false): array
    {
        $payload = [
            'id' => $r->id,
            'staff_id' => $r->staff_id,
            'review_type' => $r->review_type,
            'review_period_start' => $r->review_period_start?->format('Y-m-d'),
            'review_period_end' => $r->review_period_end?->format('Y-m-d'),
            'review_date' => $r->review_date?->format('Y-m-d'),
            'overall_rating' => $r->overall_rating !== null ? (float) $r->overall_rating : null,
            'status' => $r->status,
            'reviewer_name' => $r->reviewer?->full_name,
            'acknowledged_at' => $r->acknowledged_at?->toIso8601String(),
        ];

        if ($detailed) {
            $payload += [
                'category_ratings' => $r->category_ratings,
                'strengths' => $r->strengths,
                'areas_for_improvement' => $r->areas_for_improvement,
                'achievements' => $r->achievements,
                'goals_met' => $r->goals_met,
                'comments' => $r->comments,
                'reviewer_comments' => $r->reviewer_comments,
            ];
        }

        return $payload;
    }
}
