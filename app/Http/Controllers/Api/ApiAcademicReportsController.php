<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicReports\AcademicReportAnswer;
use App\Models\AcademicReports\AcademicReportAssignment;
use App\Models\AcademicReports\AcademicReportQuestion;
use App\Models\AcademicReports\AcademicReportSubmission;
use App\Models\AcademicReports\AcademicReportTemplate;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApiAcademicReportsController extends Controller
{
    protected function canManageTemplates($user): bool
    {
        return $user && $user->hasAnyRole(['Super Admin', 'Admin', 'Academic Administrator', 'Senior Teacher', 'Supervisor']);
    }

    public function templates(Request $request)
    {
        if (! $this->canManageTemplates($request->user())) {
            abort(403);
        }

        $templates = AcademicReportTemplate::query()
            ->withCount(['questions', 'assignments', 'submissions'])
            ->orderByDesc('id')
            ->get();

        return response()->json(['success' => true, 'data' => $templates]);
    }

    public function showTemplate(Request $request, AcademicReportTemplate $template)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        // Managers can view any template; others only published templates they are assigned to.
        $isManager = $this->canManageTemplates($user);
        if (! $isManager) {
            if ($template->status !== 'published') {
                abort(403);
            }
            if (! $this->userHasAnyAssignmentForTemplate($user, $template->id)) {
                abort(403);
            }
        }

        $template->load(['questions', 'assignments']);
        return response()->json(['success' => true, 'data' => $template]);
    }

    public function storeTemplate(Request $request)
    {
        $user = $request->user();
        if (! $this->canManageTemplates($user)) {
            abort(403);
        }

        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'open_from' => 'nullable|date',
            'open_until' => 'nullable|date|after_or_equal:open_from',
            'questions' => 'nullable|array',
            'questions.*.type' => ['required_with:questions', 'string', Rule::in(['short_text', 'long_text', 'single_select', 'multi_select', 'file_upload'])],
            'questions.*.label' => 'required_with:questions|string|max:255',
            'questions.*.help_text' => 'nullable|string|max:2000',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.options' => 'nullable|array',
            'questions.*.display_order' => 'nullable|integer|min:0',
            'assignments' => 'nullable|array',
            'assignments.*.target_type' => ['required_with:assignments', 'string', Rule::in(['role', 'user', 'class_context'])],
            'assignments.*.role_name' => 'nullable|string|max:100',
            'assignments.*.user_id' => 'nullable|integer|exists:users,id',
            'assignments.*.classroom_id' => 'nullable|integer|exists:classrooms,id',
            'assignments.*.stream_id' => 'nullable|integer|exists:streams,id',
            'assignments.*.subject_id' => 'nullable|integer|exists:subjects,id',
        ]);

        $template = null;
        DB::transaction(function () use ($v, $user, &$template) {
            $template = AcademicReportTemplate::create([
                'title' => $v['title'],
                'description' => $v['description'] ?? null,
                'status' => 'draft',
                'created_by_user_id' => $user?->id,
                'open_from' => $v['open_from'] ?? null,
                'open_until' => $v['open_until'] ?? null,
            ]);

            foreach (($v['questions'] ?? []) as $idx => $q) {
                AcademicReportQuestion::create([
                    'template_id' => $template->id,
                    'type' => $q['type'],
                    'label' => $q['label'],
                    'help_text' => $q['help_text'] ?? null,
                    'is_required' => (bool) ($q['is_required'] ?? false),
                    'options' => $q['options'] ?? null,
                    'display_order' => (int) ($q['display_order'] ?? $idx),
                ]);
            }

            foreach (($v['assignments'] ?? []) as $a) {
                AcademicReportAssignment::create([
                    'template_id' => $template->id,
                    'target_type' => $a['target_type'],
                    'role_name' => $a['role_name'] ?? null,
                    'user_id' => $a['user_id'] ?? null,
                    'classroom_id' => $a['classroom_id'] ?? null,
                    'stream_id' => $a['stream_id'] ?? null,
                    'subject_id' => $a['subject_id'] ?? null,
                ]);
            }
        });

        $template->load(['questions', 'assignments']);
        return response()->json(['success' => true, 'data' => $template], 201);
    }

    public function updateTemplate(Request $request, AcademicReportTemplate $template)
    {
        $user = $request->user();
        if (! $this->canManageTemplates($user)) {
            abort(403);
        }

        if ($template->status === 'archived') {
            return response()->json(['success' => false, 'message' => 'Archived templates cannot be edited.'], 422);
        }

        $v = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'open_from' => 'nullable|date',
            'open_until' => 'nullable|date|after_or_equal:open_from',
            'status' => ['nullable', 'string', Rule::in(['draft', 'published', 'archived'])],
            'questions' => 'nullable|array',
            'questions.*.id' => 'nullable|integer|exists:academic_report_questions,id',
            'questions.*.type' => ['required_with:questions', 'string', Rule::in(['short_text', 'long_text', 'single_select', 'multi_select', 'file_upload'])],
            'questions.*.label' => 'required_with:questions|string|max:255',
            'questions.*.help_text' => 'nullable|string|max:2000',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.options' => 'nullable|array',
            'questions.*.display_order' => 'nullable|integer|min:0',
            'assignments' => 'nullable|array',
            'assignments.*.id' => 'nullable|integer|exists:academic_report_assignments,id',
            'assignments.*.target_type' => ['required_with:assignments', 'string', Rule::in(['role', 'user', 'class_context'])],
            'assignments.*.role_name' => 'nullable|string|max:100',
            'assignments.*.user_id' => 'nullable|integer|exists:users,id',
            'assignments.*.classroom_id' => 'nullable|integer|exists:classrooms,id',
            'assignments.*.stream_id' => 'nullable|integer|exists:streams,id',
            'assignments.*.subject_id' => 'nullable|integer|exists:subjects,id',
        ]);

        DB::transaction(function () use ($template, $v) {
            $template->fill([
                'title' => $v['title'] ?? $template->title,
                'description' => array_key_exists('description', $v) ? ($v['description'] ?? null) : $template->description,
                'open_from' => array_key_exists('open_from', $v) ? ($v['open_from'] ?? null) : $template->open_from,
                'open_until' => array_key_exists('open_until', $v) ? ($v['open_until'] ?? null) : $template->open_until,
            ]);
            if (isset($v['status'])) {
                $template->status = $v['status'];
            }
            $template->save();

            if (isset($v['questions'])) {
                $existingIds = $template->questions()->pluck('id')->toArray();
                $incomingIds = array_values(array_filter(array_map(fn ($q) => $q['id'] ?? null, $v['questions'])));
                $toDelete = array_diff($existingIds, $incomingIds);
                if (! empty($toDelete)) {
                    AcademicReportQuestion::whereIn('id', $toDelete)->delete();
                }
                foreach ($v['questions'] as $idx => $q) {
                    $payload = [
                        'type' => $q['type'],
                        'label' => $q['label'],
                        'help_text' => $q['help_text'] ?? null,
                        'is_required' => (bool) ($q['is_required'] ?? false),
                        'options' => $q['options'] ?? null,
                        'display_order' => (int) ($q['display_order'] ?? $idx),
                    ];
                    if (! empty($q['id'])) {
                        AcademicReportQuestion::where('id', (int) $q['id'])->where('template_id', $template->id)->update($payload);
                    } else {
                        AcademicReportQuestion::create(array_merge($payload, ['template_id' => $template->id]));
                    }
                }
            }

            if (isset($v['assignments'])) {
                $existingIds = $template->assignments()->pluck('id')->toArray();
                $incomingIds = array_values(array_filter(array_map(fn ($a) => $a['id'] ?? null, $v['assignments'])));
                $toDelete = array_diff($existingIds, $incomingIds);
                if (! empty($toDelete)) {
                    AcademicReportAssignment::whereIn('id', $toDelete)->delete();
                }
                foreach ($v['assignments'] as $a) {
                    $payload = [
                        'target_type' => $a['target_type'],
                        'role_name' => $a['role_name'] ?? null,
                        'user_id' => $a['user_id'] ?? null,
                        'classroom_id' => $a['classroom_id'] ?? null,
                        'stream_id' => $a['stream_id'] ?? null,
                        'subject_id' => $a['subject_id'] ?? null,
                    ];
                    if (! empty($a['id'])) {
                        AcademicReportAssignment::where('id', (int) $a['id'])->where('template_id', $template->id)->update($payload);
                    } else {
                        AcademicReportAssignment::create(array_merge($payload, ['template_id' => $template->id]));
                    }
                }
            }
        });

        $template->load(['questions', 'assignments']);
        return response()->json(['success' => true, 'data' => $template]);
    }

    public function publish(Request $request, AcademicReportTemplate $template)
    {
        if (! $this->canManageTemplates($request->user())) {
            abort(403);
        }
        if ($template->status === 'archived') {
            return response()->json(['success' => false, 'message' => 'Archived templates cannot be published.'], 422);
        }
        if ($template->questions()->count() === 0) {
            return response()->json(['success' => false, 'message' => 'Add at least one question before publishing.'], 422);
        }
        if ($template->assignments()->count() === 0) {
            return response()->json(['success' => false, 'message' => 'Add at least one assignment before publishing.'], 422);
        }
        $template->status = 'published';
        $template->save();
        return response()->json(['success' => true, 'data' => $template]);
    }

    public function assigned(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $now = now();
        $roleNames = $user->roles->pluck('name')->values()->all();

        $templates = AcademicReportTemplate::query()
            ->where('status', 'published')
            ->where(function ($q) use ($now) {
                $q->whereNull('open_from')->orWhere('open_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('open_until')->orWhere('open_until', '>=', $now);
            })
            ->whereIn('id', function ($sub) use ($user, $roleNames) {
                $sub->select('template_id')->from('academic_report_assignments')
                    ->where(function ($a) use ($user, $roleNames) {
                        $a->where(function ($r) use ($roleNames) {
                            $r->where('target_type', 'role')->whereIn('role_name', $roleNames);
                        })->orWhere(function ($u) use ($user) {
                            $u->where('target_type', 'user')->where('user_id', $user->id);
                        })->orWhere(function ($c) use ($user) {
                            $c->where('target_type', 'class_context');
                            // class_context filtering is applied after load to avoid complex SQL for stream/subject rules.
                        });
                    });
            })
            ->withCount(['questions'])
            ->with(['assignments'])
            ->orderByDesc('id')
            ->get()
            ->filter(function (AcademicReportTemplate $t) use ($user) {
                // Keep templates where any assignment matches user
                foreach ($t->assignments as $a) {
                    if ($a->target_type === 'role') {
                        if ($user->roles->pluck('name')->contains($a->role_name)) {
                            return true;
                        }
                    }
                    if ($a->target_type === 'user') {
                        if ((int) $a->user_id === (int) $user->id) {
                            return true;
                        }
                    }
                    if ($a->target_type === 'class_context') {
                        if ($this->userMatchesClassContextAssignment($user, $a->classroom_id, $a->stream_id, $a->subject_id)) {
                            return true;
                        }
                    }
                }
                return false;
            })
            ->values();

        $data = $templates->map(fn (AcademicReportTemplate $t) => [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'status' => $t->status,
            'open_from' => $t->open_from?->toIso8601String(),
            'open_until' => $t->open_until?->toIso8601String(),
            'questions_count' => (int) ($t->questions_count ?? 0),
        ])->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function submit(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $v = $request->validate([
            'template_id' => 'required|exists:academic_report_templates,id',
            'is_anonymous' => 'nullable|boolean',
            'submitted_for' => 'nullable|array',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|exists:academic_report_questions,id',
            'answers.*.value_text' => 'nullable|string|max:10000',
            'answers.*.value_json' => 'nullable|array',
        ]);

        $template = AcademicReportTemplate::with('questions')->findOrFail((int) $v['template_id']);
        if ($template->status !== 'published') {
            return response()->json(['success' => false, 'message' => 'This report is not open.'], 422);
        }
        if (! $this->userHasAnyAssignmentForTemplate($user, $template->id)) {
            abort(403);
        }

        $isAnonymous = (bool) ($v['is_anonymous'] ?? false);

        $questionsById = $template->questions->keyBy('id');
        $answers = $v['answers'] ?? [];

        // Validate required questions (file_upload can be submitted later via upload endpoint).
        $submittedQids = collect($answers)->pluck('question_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        foreach ($template->questions as $q) {
            if (! $q->is_required) {
                continue;
            }
            if ($q->type === 'file_upload') {
                continue;
            }
            if (! in_array((int) $q->id, $submittedQids, true)) {
                return response()->json(['success' => false, 'message' => 'Please answer all required questions.'], 422);
            }
        }

        $submission = null;
        DB::transaction(function () use ($template, $user, $isAnonymous, $v, $answers, $questionsById, &$submission) {
            $submission = AcademicReportSubmission::create([
                'template_id' => $template->id,
                'submitted_by_user_id' => $isAnonymous ? null : $user->id,
                'is_anonymous' => $isAnonymous,
                'submitted_for' => $v['submitted_for'] ?? null,
            ]);

            foreach ($answers as $a) {
                $qid = (int) $a['question_id'];
                $q = $questionsById->get($qid);
                if (! $q) {
                    continue;
                }
                if ($q->type === 'file_upload') {
                    continue;
                }
                AcademicReportAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $qid,
                    'value_text' => $a['value_text'] ?? null,
                    'value_json' => $a['value_json'] ?? null,
                    'file_path' => null,
                ]);
            }
        });

        return response()->json(['success' => true, 'data' => ['id' => $submission->id]], 201);
    }

    public function uploadFile(Request $request, AcademicReportSubmission $submission, AcademicReportQuestion $question)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        if ((int) $submission->template_id !== (int) $question->template_id) {
            abort(404);
        }

        $templateId = (int) $submission->template_id;
        if (! $this->userHasAnyAssignmentForTemplate($user, $templateId)) {
            abort(403);
        }
        if ($question->type !== 'file_upload') {
            return response()->json(['success' => false, 'message' => 'This question does not accept files.'], 422);
        }

        $v = $request->validate([
            'file' => 'required|file|max:10240', // 10MB
        ]);

        $path = $v['file']->store("academic-reports/{$templateId}/submissions/{$submission->id}", ['disk' => config('filesystems.default', 'local')]);

        $answer = AcademicReportAnswer::updateOrCreate(
            ['submission_id' => $submission->id, 'question_id' => $question->id],
            ['file_path' => $path]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $answer->id,
                'file_path' => $answer->file_path,
            ],
        ]);
    }

    public function submissions(Request $request)
    {
        $user = $request->user();
        if (! $this->canManageTemplates($user)) {
            abort(403);
        }

        $request->validate([
            'template_id' => 'nullable|exists:academic_report_templates,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $perPage = (int) $request->input('per_page', 30);

        $query = AcademicReportSubmission::query()
            ->with(['template', 'answers', 'answers.question'])
            ->orderByDesc('id');

        if ($request->filled('template_id')) {
            $query->where('template_id', (int) $request->template_id);
        }

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(function (AcademicReportSubmission $s) {
            return [
                'id' => $s->id,
                'template_id' => (int) $s->template_id,
                'template_title' => $s->template?->title,
                'submitted_by_user_id' => $s->submitted_by_user_id ? (int) $s->submitted_by_user_id : null,
                'is_anonymous' => (bool) $s->is_anonymous,
                'submitted_for' => $s->submitted_for,
                'created_at' => $s->created_at?->toIso8601String(),
                'answers' => $s->answers->map(fn (AcademicReportAnswer $a) => [
                    'question_id' => (int) $a->question_id,
                    'label' => $a->question?->label,
                    'type' => $a->question?->type,
                    'value_text' => $a->value_text,
                    'value_json' => $a->value_json,
                    'file_path' => $a->file_path,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    protected function userHasAnyAssignmentForTemplate($user, int $templateId): bool
    {
        $roleNames = $user->roles->pluck('name')->values()->all();
        $hasRoleOrUser = AcademicReportAssignment::query()
            ->where('template_id', $templateId)
            ->where(function ($q) use ($user, $roleNames) {
                $q->where(function ($r) use ($roleNames) {
                    $r->where('target_type', 'role')->whereIn('role_name', $roleNames);
                })->orWhere(function ($u) use ($user) {
                    $u->where('target_type', 'user')->where('user_id', $user->id);
                })->orWhere(function ($c) {
                    $c->where('target_type', 'class_context');
                });
            })
            ->exists();

        if (! $hasRoleOrUser) {
            return false;
        }

        // If there are any class_context assignments, user must match at least one; otherwise role/user match is enough.
        $classAssignments = AcademicReportAssignment::query()
            ->where('template_id', $templateId)
            ->where('target_type', 'class_context')
            ->get(['classroom_id', 'stream_id', 'subject_id']);

        if ($classAssignments->isEmpty()) {
            return true;
        }

        foreach ($classAssignments as $a) {
            if ($this->userMatchesClassContextAssignment($user, $a->classroom_id, $a->stream_id, $a->subject_id)) {
                return true;
            }
        }
        return false;
    }

    protected function userMatchesClassContextAssignment($user, ?int $classroomId, ?int $streamId, ?int $subjectId): bool
    {
        if (! $user->hasTeacherLikeRole()) {
            return false;
        }
        if (! $classroomId) {
            return false;
        }
        if (! $user->canTeacherAccessClassroom((int) $classroomId)) {
            return false;
        }
        if ($streamId) {
            // ensure teacher has any students in that stream/classroom scope
            $q = Student::query()->where('classroom_id', (int) $classroomId)->where('stream_id', (int) $streamId)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($q);
            if (! $q->exists()) {
                return false;
            }
        }
        if ($subjectId) {
            $staffId = optional($user->staff)->id;
            if (! $staffId) {
                return false;
            }
            $hasSubject = DB::table('classroom_subjects')
                ->where('classroom_id', (int) $classroomId)
                ->where('subject_id', (int) $subjectId)
                ->where('staff_id', $staffId)
                ->exists();
            if (! $hasSubject) {
                return false;
            }
        }
        return true;
    }
}

