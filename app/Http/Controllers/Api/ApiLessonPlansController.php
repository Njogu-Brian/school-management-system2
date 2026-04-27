<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\LessonPlan;
use App\Models\Academics\Timetable;
use App\Models\Staff;
use App\Notifications\LessonPlanReviewNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiLessonPlansController extends Controller
{
    /**
     * Lesson plans for mobile (read-only list; create/edit remain on web).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = LessonPlan::with(['subject', 'classroom', 'creator', 'approver', 'rejector', 'timetable']);

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
            $status = (string) $request->status;
            if (in_array($status, ['draft', 'submitted', 'approved', 'rejected'], true)) {
                $query->where('submission_status', $status);
            } else {
                $query->where('status', $status);
            }
        }
        if ($request->filled('submission_status')) {
            $query->where('submission_status', (string) $request->submission_status);
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
        $lp = LessonPlan::with(['subject', 'classroom', 'creator', 'approver', 'rejector', 'timetable'])->findOrFail($id);

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

    /**
     * Create a lesson plan draft from mobile.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasTeacherLikeRole()) {
            abort(403, 'Only teaching staff can create lesson plans.');
        }

        $request->validate([
            'scheme_of_work_id' => 'nullable|exists:schemes_of_work,id',
            'timetable_id' => 'nullable|exists:timetables,id',
            'substrand_id' => 'nullable|exists:cbc_substrands,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'title' => 'required|string|max:255',
            'lesson_number' => 'nullable|string|max:50',
            'planned_date' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'learning_objectives' => 'nullable|array',
            'learning_outcomes' => 'nullable|string',
            'core_competencies' => 'nullable|array',
            'values' => 'nullable|array',
            'pclc' => 'nullable|array',
            'learning_resources' => 'nullable|array',
            'introduction' => 'nullable|string',
            'lesson_development' => 'nullable|string',
            'activities' => 'nullable|array',
            'assessment' => 'nullable|string',
            'conclusion' => 'nullable|string',
            'reflection' => 'nullable|string',
        ]);

        $privileged = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        $timetableId = $request->filled('timetable_id') ? (int) $request->timetable_id : null;
        $tt = null;
        if ($timetableId) {
            $tt = Timetable::find($timetableId);
            if (! $tt) {
                return response()->json(['success' => false, 'message' => 'Invalid timetable slot.'], 422);
            }
        }

        $classId = (int) ($request->input('classroom_id') ?? $tt?->classroom_id ?? 0);
        $subjectId = (int) ($request->input('subject_id') ?? $tt?->subject_id ?? 0);
        $termId = (int) ($request->input('term_id') ?? $tt?->term_id ?? 0);
        $yearId = (int) ($request->input('academic_year_id') ?? $tt?->academic_year_id ?? 0);

        if (! $classId || ! $subjectId || ! $termId || ! $yearId) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required context. Provide timetable_id or classroom_id, subject_id, term_id, academic_year_id.',
            ], 422);
        }

        if (! $privileged && ! $user->canTeacherAccessClassroom($classId)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this class.'], 403);
        }

        $staffId = $user->staff?->id;
        if (! $privileged && $staffId && ! $user->isSeniorTeacherUser()) {
            $taught = DB::table('classroom_subjects')
                ->where('classroom_id', $classId)
                ->where('staff_id', $staffId)
                ->where('subject_id', $subjectId)
                ->exists();
            if (! $taught) {
                return response()->json(['success' => false, 'message' => 'You do not teach this subject in this class.'], 403);
            }
        }

        $planned = \Carbon\Carbon::parse($request->planned_date)->startOfDay();
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        if ($planned->lt($today) || $planned->gt($tomorrow)) {
            return response()->json(['success' => false, 'message' => 'Lesson plans can only be created for today or tomorrow.'], 422);
        }

        if ($timetableId) {
            if ((int) $tt->classroom_id !== $classId || (int) $tt->term_id !== $termId) {
                return response()->json(['success' => false, 'message' => 'Invalid timetable slot for this class/term.'], 422);
            }
            if ((int) $tt->subject_id !== $subjectId) {
                return response()->json(['success' => false, 'message' => 'Timetable slot subject does not match selected subject.'], 422);
            }
        }

        $lp = LessonPlan::create([
            'scheme_of_work_id' => $request->input('scheme_of_work_id'),
            'subject_id' => $subjectId,
            'classroom_id' => $classId,
            'timetable_id' => $timetableId,
            'substrand_id' => $request->input('substrand_id'),
            'academic_year_id' => $yearId,
            'term_id' => $termId,
            'created_by' => $staffId,
            'submission_status' => 'draft',
            'submitted_at' => null,
            'is_late' => false,
            'title' => $request->title,
            'lesson_number' => $request->input('lesson_number'),
            'planned_date' => $planned->toDateString(),
            'duration_minutes' => (int) ($request->input('duration_minutes') ?? 40),
            'learning_objectives' => $request->input('learning_objectives'),
            'learning_outcomes' => $request->input('learning_outcomes'),
            'core_competencies' => $request->input('core_competencies'),
            'values' => $request->input('values'),
            'pclc' => $request->input('pclc'),
            'learning_resources' => $request->input('learning_resources'),
            'introduction' => $request->input('introduction'),
            'lesson_development' => $request->input('lesson_development'),
            'activities' => $request->input('activities'),
            'assessment' => $request->input('assessment'),
            'conclusion' => $request->input('conclusion'),
            'reflection' => $request->input('reflection'),
            'status' => 'planned',
        ]);

        $lp->load(['subject', 'classroom', 'creator', 'approver', 'rejector', 'timetable']);

        return response()->json([
            'success' => true,
            'message' => 'Lesson plan draft created.',
            'data' => $this->formatLessonPlan($lp),
        ], 201);
    }

    /**
     * Update a lesson plan draft from mobile.
     */
    public function update(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user || ! $user->hasTeacherLikeRole()) {
            abort(403, 'Only teaching staff can update lesson plans.');
        }

        $lp = LessonPlan::findOrFail($id);
        $privileged = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        $staffId = $user->staff?->id;

        if (! $privileged) {
            if (! $staffId || (int) $lp->created_by !== (int) $staffId) {
                abort(403, 'You can only edit your own lesson plans.');
            }
            if (($lp->submission_status ?? 'draft') !== 'draft') {
                return response()->json(['success' => false, 'message' => 'This lesson plan is no longer editable.'], 422);
            }
        }

        $request->validate([
            'timetable_id' => 'nullable|exists:timetables,id',
            'substrand_id' => 'nullable|exists:cbc_substrands,id',
            'title' => 'required|string|max:255',
            'lesson_number' => 'nullable|string|max:50',
            'planned_date' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'learning_objectives' => 'nullable|array',
            'learning_outcomes' => 'nullable|string',
            'core_competencies' => 'nullable|array',
            'values' => 'nullable|array',
            'pclc' => 'nullable|array',
            'learning_resources' => 'nullable|array',
            'introduction' => 'nullable|string',
            'lesson_development' => 'nullable|string',
            'activities' => 'nullable|array',
            'assessment' => 'nullable|string',
            'conclusion' => 'nullable|string',
            'reflection' => 'nullable|string',
        ]);

        $planned = \Carbon\Carbon::parse($request->planned_date)->startOfDay();
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        if ($planned->lt($today) || $planned->gt($tomorrow)) {
            return response()->json(['success' => false, 'message' => 'Lesson plans can only be edited for today or tomorrow.'], 422);
        }

        $timetableId = $request->filled('timetable_id') ? (int) $request->timetable_id : null;
        if ($timetableId) {
            $tt = Timetable::find($timetableId);
            if (! $tt || (int) $tt->classroom_id !== (int) $lp->classroom_id || (int) $tt->term_id !== (int) $lp->term_id) {
                return response()->json(['success' => false, 'message' => 'Invalid timetable slot for this lesson plan.'], 422);
            }
            if ((int) $tt->subject_id !== (int) $lp->subject_id) {
                return response()->json(['success' => false, 'message' => 'Timetable slot subject does not match lesson plan subject.'], 422);
            }
        }

        $lp->update([
            'timetable_id' => $timetableId,
            'substrand_id' => $request->input('substrand_id'),
            'title' => $request->title,
            'lesson_number' => $request->input('lesson_number'),
            'planned_date' => $planned->toDateString(),
            'duration_minutes' => (int) ($request->input('duration_minutes') ?? $lp->duration_minutes ?? 40),
            'learning_objectives' => $request->input('learning_objectives'),
            'learning_outcomes' => $request->input('learning_outcomes'),
            'core_competencies' => $request->input('core_competencies'),
            'values' => $request->input('values'),
            'pclc' => $request->input('pclc'),
            'learning_resources' => $request->input('learning_resources'),
            'introduction' => $request->input('introduction'),
            'lesson_development' => $request->input('lesson_development'),
            'activities' => $request->input('activities'),
            'assessment' => $request->input('assessment'),
            'conclusion' => $request->input('conclusion'),
            'reflection' => $request->input('reflection'),
        ]);

        $lp->load(['subject', 'classroom', 'creator', 'approver', 'rejector', 'timetable']);

        return response()->json([
            'success' => true,
            'message' => 'Lesson plan updated.',
            'data' => $this->formatLessonPlan($lp),
        ]);
    }

    /**
     * Submit a draft lesson plan (locks editing; sets late flag).
     */
    public function submit(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user || ! $user->hasTeacherLikeRole()) {
            abort(403, 'Only teaching staff can submit lesson plans.');
        }

        $lp = LessonPlan::with(['timetable'])->findOrFail($id);
        $privileged = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        $staffId = $user->staff?->id;

        if (! $privileged) {
            if (! $staffId || (int) $lp->created_by !== (int) $staffId) {
                abort(403, 'You can only submit your own lesson plans.');
            }
        }

        if (($lp->submission_status ?? 'draft') !== 'draft') {
            return response()->json(['success' => false, 'message' => 'This lesson plan is already submitted.'], 422);
        }

        $plannedDate = $lp->planned_date?->toDateString() ?? (string) $lp->planned_date;
        $planned = \Carbon\Carbon::parse($plannedDate)->startOfDay();
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        if ($planned->lt($today) || $planned->gt($tomorrow)) {
            return response()->json(['success' => false, 'message' => 'Lesson plans can only be submitted for today or tomorrow.'], 422);
        }

        $isLate = false;
        if ($planned->isSameDay($today) && $lp->timetable && $lp->timetable->end_time) {
            try {
                $end = \Carbon\Carbon::parse($plannedDate.' '.$lp->timetable->end_time);
                $deadline = $end->copy()->addHour();
                $isLate = now()->greaterThan($deadline);
            } catch (\Throwable $e) {
                $isLate = false;
            }
        }

        $lp->update([
            'submission_status' => 'submitted',
            'submitted_at' => now(),
            'is_late' => $isLate,
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_notes' => null,
        ]);

        $lp->load(['subject', 'classroom', 'creator', 'approver', 'rejector', 'timetable']);

        return response()->json([
            'success' => true,
            'message' => 'Lesson plan submitted.',
            'data' => $this->formatLessonPlan($lp),
        ]);
    }

    /**
     * Review queue for supervisors (Senior Teacher scope) and academic admins.
     */
    public function reviewQueue(Request $request)
    {
        $user = $request->user();
        if (! $this->canReviewLessonPlans($user)) {
            abort(403, 'You do not have permission to review lesson plans.');
        }

        $query = LessonPlan::with(['subject', 'classroom', 'creator', 'approver', 'rejector', 'timetable'])
            ->where('submission_status', 'submitted');

        $scopeClassroomIds = $this->getReviewerScopedClassroomIds($user);
        if ($scopeClassroomIds !== null) {
            if ($scopeClassroomIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('classroom_id', $scopeClassroomIds);
            }
        }

        if ($request->filled('classroom_id') || $request->filled('class_id')) {
            $cid = (int) ($request->classroom_id ?? $request->class_id);
            $query->where('classroom_id', $cid);
        }
        if ($request->filled('teacher_id')) {
            $query->where('created_by', (int) $request->teacher_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('planned_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('planned_date', '<=', $request->date_to);
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $paginated = $query->orderBy('planned_date')->orderBy('id')->paginate($perPage);

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

    public function approve(Request $request, int $id)
    {
        $user = $request->user();
        if (! $this->canReviewLessonPlans($user)) {
            abort(403, 'You do not have permission to approve lesson plans.');
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:2000',
        ]);

        $lp = LessonPlan::with(['classroom', 'subject', 'creator'])->findOrFail($id);
        if (! $this->canReviewerAccessLessonPlan($user, $lp)) {
            abort(403, 'You do not have access to this lesson plan.');
        }

        if (($lp->submission_status ?? 'draft') !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Only submitted lesson plans can be approved.'], 422);
        }

        $staffId = $user?->staff?->id;
        if (! $staffId) {
            abort(422, 'Your account is missing a staff profile.');
        }

        $lp->update([
            'submission_status' => 'approved',
            'approved_by' => $staffId,
            'approved_at' => now(),
            'approval_notes' => $request->input('approval_notes'),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_notes' => null,
        ]);

        $this->notifyLessonPlanOwner($lp, 'Lesson plan approved', 'Your lesson plan was approved.', [
            'lesson_plan_id' => $lp->id,
            'status' => 'approved',
        ]);

        $lp->load(['subject', 'classroom', 'creator', 'approver', 'rejector', 'timetable']);

        return response()->json([
            'success' => true,
            'message' => 'Lesson plan approved.',
            'data' => $this->formatLessonPlan($lp),
        ]);
    }

    public function reject(Request $request, int $id)
    {
        $user = $request->user();
        if (! $this->canReviewLessonPlans($user)) {
            abort(403, 'You do not have permission to reject lesson plans.');
        }

        $request->validate([
            'rejection_notes' => 'required|string|max:2000',
        ]);

        $lp = LessonPlan::with(['classroom', 'subject', 'creator'])->findOrFail($id);
        if (! $this->canReviewerAccessLessonPlan($user, $lp)) {
            abort(403, 'You do not have access to this lesson plan.');
        }

        if (($lp->submission_status ?? 'draft') !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Only submitted lesson plans can be rejected.'], 422);
        }

        $staffId = $user?->staff?->id;
        if (! $staffId) {
            abort(422, 'Your account is missing a staff profile.');
        }

        $lp->update([
            'submission_status' => 'rejected',
            'rejected_by' => $staffId,
            'rejected_at' => now(),
            'rejection_notes' => $request->input('rejection_notes'),
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
        ]);

        $this->notifyLessonPlanOwner($lp, 'Lesson plan rejected', 'Your lesson plan was rejected. Please review the notes and resubmit.', [
            'lesson_plan_id' => $lp->id,
            'status' => 'rejected',
        ]);

        $lp->load(['subject', 'classroom', 'creator', 'approver', 'rejector', 'timetable']);

        return response()->json([
            'success' => true,
            'message' => 'Lesson plan rejected.',
            'data' => $this->formatLessonPlan($lp),
        ]);
    }

    protected function canReviewLessonPlans($user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Director', 'Secretary'])) {
            return true;
        }

        return $user->isSeniorTeacherUser() || $user->hasAnyRole(['Academic Administrator']);
    }

    /**
     * @return int[]|null null means global scope (no restriction).
     */
    protected function getReviewerScopedClassroomIds($user): ?array
    {
        if (! $user) {
            return [];
        }
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Director', 'Secretary', 'Academic Administrator'])) {
            return null;
        }
        if ($user->isSeniorTeacherUser()) {
            return array_map('intval', $user->getSupervisedClassroomIds());
        }

        return [];
    }

    protected function canReviewerAccessLessonPlan($user, LessonPlan $lp): bool
    {
        $scope = $this->getReviewerScopedClassroomIds($user);
        if ($scope === null) {
            return true;
        }
        return in_array((int) $lp->classroom_id, $scope, true);
    }

    protected function notifyLessonPlanOwner(LessonPlan $lp, string $title, string $body, array $data = []): void
    {
        $staffId = (int) ($lp->created_by ?? 0);
        if (! $staffId) {
            return;
        }

        $staff = Staff::with('user')->find($staffId);
        $u = $staff?->user;
        if (! $u) {
            return;
        }

        $u->notify(new LessonPlanReviewNotification($title, $body, $data));
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
            'status' => $lp->submission_status ?? 'draft',
            'submission_status' => $lp->submission_status ?? 'draft',
            'submitted_at' => $lp->submitted_at?->toIso8601String(),
            'is_late' => (bool) ($lp->is_late ?? false),
            'approved_at' => $lp->approved_at?->toIso8601String(),
            'approved_by' => $lp->approved_by,
            'approved_by_name' => $lp->approver?->full_name,
            'approval_notes' => $lp->approval_notes,
            'rejected_at' => $lp->rejected_at?->toIso8601String(),
            'rejected_by' => $lp->rejected_by,
            'rejected_by_name' => $lp->rejector?->full_name,
            'rejection_notes' => $lp->rejection_notes,
            'timetable_id' => $lp->timetable_id,
            'created_at' => $lp->created_at->toIso8601String(),
            'updated_at' => $lp->updated_at->toIso8601String(),
        ];
    }
}
