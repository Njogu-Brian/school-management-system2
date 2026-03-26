<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\Homework;
use App\Models\Academics\Stream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function show(Request $request, int $id)
    {
        $homework = Homework::with(['classroom', 'stream', 'subject', 'teacher'])->findOrFail($id);
        $user = $request->user();

        if ($user && $user->hasTeacherLikeRole() && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            if ($homework->classroom_id && ! $user->canTeacherAccessClassroom((int) $homework->classroom_id)) {
                abort(403, 'You do not have access to this homework.');
            }
            $ownStaffId = $user->staff?->id;
            if ($ownStaffId && ! $user->isSeniorTeacherUser() && (int) ($homework->teacher_id ?? 0) !== $ownStaffId) {
                abort(403, 'You do not have access to this homework.');
            }
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatHomework($homework),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasTeacherLikeRole()) {
            abort(403, 'Only teaching staff can create homework.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'due_date' => 'required|date|after_or_equal:today',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'subject_id' => 'required|exists:subjects,id',
            'target_scope' => 'nullable|in:class,stream',
            'max_score' => 'nullable|integer|min:1|max:1000',
        ]);

        $priv = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        $classId = (int) $request->classroom_id;

        if (! $priv && ! $user->canTeacherAccessClassroom($classId)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this class.'], 403);
        }

        if ($request->filled('stream_id')) {
            $sid = (int) $request->stream_id;
            $stream = Stream::where('id', $sid)->where('classroom_id', $classId)->first();
            if (! $stream) {
                return response()->json(['success' => false, 'message' => 'Invalid stream for this class.'], 422);
            }
            $effectiveStreamIds = $user->getEffectiveStreamIds();
            if (! $priv && $effectiveStreamIds !== [] && ! in_array($sid, array_map('intval', $effectiveStreamIds), true)) {
                return response()->json(['success' => false, 'message' => 'You are not assigned to this stream.'], 403);
            }
        }

        $staffId = $user->staff?->id;
        if (! $priv && $staffId && ! $user->isSeniorTeacherUser()) {
            $taught = DB::table('classroom_subjects')
                ->where('classroom_id', $classId)
                ->where('staff_id', $staffId)
                ->where('subject_id', $request->subject_id)
                ->exists();
            if (! $taught) {
                return response()->json(['success' => false, 'message' => 'You do not teach this subject in this class.'], 403);
            }
        }

        $scope = $request->input('target_scope', $request->filled('stream_id') ? 'stream' : 'class');

        $homework = Homework::create([
            'assigned_by' => $user->id,
            'teacher_id' => $staffId,
            'classroom_id' => $classId,
            'stream_id' => $request->input('stream_id'),
            'subject_id' => (int) $request->subject_id,
            'title' => $request->title,
            'instructions' => $request->instructions,
            'due_date' => $request->due_date,
            'target_scope' => $scope === 'stream' ? 'stream' : 'class',
            'max_score' => $request->max_score,
            'allow_late_submission' => true,
        ]);

        $homework->load(['classroom', 'stream', 'subject', 'teacher']);

        return response()->json([
            'success' => true,
            'message' => 'Homework created.',
            'data' => $this->formatHomework($homework),
        ], 201);
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
