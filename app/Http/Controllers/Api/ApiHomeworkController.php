<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\Homework;
use Illuminate\Http\Request;

class ApiHomeworkController extends Controller
{
    /**
     * Homework list for mobile (maps to "assignments" in the app).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Homework::with(['classroom', 'stream', 'subject', 'teacher']);

        $privileged = $user && $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);

        if ($user && $user->hasTeacherLikeRole() && ! $privileged) {
            $classIds = $user->getDashboardClassroomIds();
            if ($classIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('classroom_id', $classIds);
            }

            $ownStaffId = $user->staff?->id;
            if ($request->filled('teacher_id')) {
                $tid = (int) $request->teacher_id;
                if ($ownStaffId && $tid === $ownStaffId) {
                    $query->where('teacher_id', $tid);
                } elseif ($user->isSeniorTeacherUser()) {
                    $query->where('teacher_id', $tid);
                }
            } elseif ($ownStaffId && ! $user->isSeniorTeacherUser()) {
                $query->where('teacher_id', $ownStaffId);
            }
        }

        if ($request->filled('classroom_id') || $request->filled('class_id')) {
            $query->where('classroom_id', (int) ($request->classroom_id ?? $request->class_id));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', (int) $request->subject_id);
        }
        if ($request->filled('status')) {
            $status = (string) $request->status;
            if ($status === 'active') {
                $query->whereDate('due_date', '>=', now()->toDateString());
            } elseif ($status === 'closed') {
                $query->whereDate('due_date', '<', now()->toDateString());
            }
        }
        if ($request->filled('search')) {
            $s = '%'.addcslashes($request->search, '%_\\').'%';
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', $s)->orWhere('instructions', 'like', $s);
            });
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $paginated = $query->orderByDesc('due_date')->orderByDesc('id')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn ($h) => $this->formatHomework($h))->values();

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

    protected function formatHomework(Homework $h): array
    {
        $due = $h->due_date;
        $status = 'closed';
        if ($due) {
            $status = $due->isFuture() || $due->isToday() ? 'active' : 'closed';
        }

        return [
            'id' => $h->id,
            'title' => $h->title,
            'description' => (string) ($h->instructions ?? ''),
            'subject_id' => $h->subject_id,
            'subject_name' => $h->subject?->name,
            'class_id' => $h->classroom_id,
            'class_name' => $h->classroom?->name,
            'teacher_id' => $h->teacher_id,
            'teacher_name' => $h->teacher?->full_name,
            'due_date' => $due?->toDateString(),
            'total_marks' => (int) ($h->max_score ?? 0),
            'attachments' => $h->attachment_paths ?? [],
            'status' => $status,
            'created_at' => $h->created_at->toIso8601String(),
            'updated_at' => $h->updated_at->toIso8601String(),
        ];
    }
}
