<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\LessonPlan;
use Illuminate\Http\Request;

class ApiLessonPlansController extends Controller
{
    /**
     * Lesson plans for mobile (read-only list; create/edit remain on web).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = LessonPlan::with(['subject', 'classroom', 'creator']);

        $privileged = $user && $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);

        if ($user && $user->hasTeacherLikeRole() && ! $privileged) {
            $classIds = $user->getDashboardClassroomIds();
            $staffId = $user->staff?->id;
            if ($classIds === [] && ! $staffId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($classIds, $staffId) {
                    if ($classIds !== []) {
                        $q->whereIn('classroom_id', $classIds);
                    }
                    if ($staffId) {
                        $q->orWhere('created_by', $staffId);
                    }
                });
            }

            if ($request->filled('teacher_id') && $staffId && (int) $request->teacher_id === $staffId) {
                $query->where('created_by', $staffId);
            }
        }

        if ($request->filled('classroom_id') || $request->filled('class_id')) {
            $query->where('classroom_id', (int) ($request->classroom_id ?? $request->class_id));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', (int) $request->subject_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', (int) $request->academic_year_id);
        }
        if ($request->filled('term_id')) {
            $query->where('term_id', (int) $request->term_id);
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $paginated = $query->orderByDesc('planned_date')->orderByDesc('id')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn ($lp) => $this->formatLessonPlan($lp))->values();

        return response()->json([
            'success' => true,
            'data' => [
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

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $lp = LessonPlan::with(['subject', 'classroom', 'creator'])->findOrFail($id);

        $privileged = $user && $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        if ($user && $user->hasTeacherLikeRole() && ! $privileged) {
            $classIds = $user->getDashboardClassroomIds();
            $staffId = $user->staff?->id;
            $allowed = in_array((int) $lp->classroom_id, $classIds, true)
                || ($staffId && (int) $lp->created_by === (int) $staffId);
            if (! $allowed) {
                abort(403, 'You do not have access to this lesson plan.');
            }
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatLessonPlan($lp),
        ]);
    }

    protected function formatLessonPlan(LessonPlan $lp): array
    {
        $objectives = $lp->learning_objectives;
        if (! is_array($objectives)) {
            $objectives = $objectives ? [$objectives] : [];
        }

        return [
            'id' => $lp->id,
            'teacher_id' => $lp->created_by,
            'teacher_name' => $lp->creator->full_name ?? null,
            'subject_id' => $lp->subject_id,
            'subject_name' => $lp->subject->name ?? null,
            'class_id' => $lp->classroom_id,
            'class_name' => $lp->classroom->name ?? null,
            'topic' => $lp->title,
            'objectives' => $objectives,
            'activities' => is_array($lp->activities) ? $lp->activities : [],
            'resources' => is_array($lp->learning_resources) ? $lp->learning_resources : [],
            'assessment_methods' => [],
            'date' => $lp->planned_date?->toDateString(),
            'duration_minutes' => (int) ($lp->duration_minutes ?? 0),
            'status' => $lp->status ?? 'draft',
            'created_at' => $lp->created_at->toIso8601String(),
            'updated_at' => $lp->updated_at->toIso8601String(),
        ];
    }
}
