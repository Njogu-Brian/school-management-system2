<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\Homework;
use App\Models\Academics\HomeworkDiary;
use App\Models\Academics\Stream;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            'allow_late_submission' => 'nullable|boolean',
            'links' => 'nullable|array',
            'links.*' => 'nullable',
            'files' => 'nullable|array',
            'files.*' => 'file|max:20480',
            'instruction_blocks' => 'nullable',
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
            'allow_late_submission' => $request->boolean('allow_late_submission', true),
        ]);

        $attachments = $this->buildAttachmentsFromRequest($request, $homework->id);
        if ($attachments !== []) {
            $homework->attachment_paths = $attachments;
            $homework->save();
        }

        $homework->load(['classroom', 'stream', 'subject', 'teacher']);

        return response()->json([
            'success' => true,
            'message' => 'Homework created.',
            'data' => $this->formatHomework($homework),
        ], 201);
    }

    /**
     * Optional update — lets a teacher edit metadata and append attachments.
     */
    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $homework = Homework::findOrFail($id);

        if (! $user || ! $user->hasTeacherLikeRole()) {
            abort(403, 'Only teaching staff can edit homework.');
        }
        $priv = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        if (! $priv && $homework->classroom_id && ! $user->canTeacherAccessClassroom((int) $homework->classroom_id)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this class.'], 403);
        }
        $ownStaffId = $user->staff?->id;
        if (! $priv && ! $user->isSeniorTeacherUser() && $ownStaffId && (int) ($homework->teacher_id ?? 0) !== $ownStaffId) {
            return response()->json(['success' => false, 'message' => 'You did not create this homework.'], 403);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_score' => 'nullable|integer|min:1|max:1000',
            'allow_late_submission' => 'nullable|boolean',
            'links' => 'nullable|array',
            'files' => 'nullable|array',
            'files.*' => 'file|max:20480',
            'instruction_blocks' => 'nullable',
        ]);

        if ($request->filled('title')) {
            $homework->title = $request->title;
        }
        if ($request->has('instructions')) {
            $homework->instructions = $request->instructions;
        }
        if ($request->filled('due_date')) {
            $homework->due_date = $request->due_date;
        }
        if ($request->has('max_score')) {
            $homework->max_score = $request->max_score;
        }
        if ($request->has('allow_late_submission')) {
            $homework->allow_late_submission = $request->boolean('allow_late_submission', true);
        }

        $newAttachments = $this->buildAttachmentsFromRequest($request, $homework->id);
        if ($newAttachments !== []) {
            $existing = $this->normalizeAttachments($homework->attachment_paths);
            $homework->attachment_paths = array_merge($existing, $newAttachments);
        }

        $homework->save();
        $homework->load(['classroom', 'stream', 'subject', 'teacher']);

        return response()->json([
            'success' => true,
            'message' => 'Homework updated.',
            'data' => $this->formatHomework($homework),
        ]);
    }

    /**
     * Diary status for one student (create a pending row if missing).
     * GET /assignments/{id}/status?student_id=
     */
    public function status(Request $request, int $id)
    {
        $user = $request->user();
        $homework = Homework::findOrFail($id);

        $studentId = (int) $request->input('student_id', 0);
        if ($studentId <= 0) {
            return response()->json(['success' => false, 'message' => 'student_id is required.'], 422);
        }
        $student = Student::findOrFail($studentId);

        $this->authorizeStudentForHomework($user, $homework, $student);

        $diary = HomeworkDiary::firstOrCreate(
            ['homework_id' => $homework->id, 'student_id' => $student->id],
            ['status' => 'pending', 'max_score' => $homework->max_score]
        );
        $diary->loadMissing('student');

        return response()->json([
            'success' => true,
            'data' => $this->formatDiary($diary, $student),
        ]);
    }

    /**
     * Parent/guardian marks the assignment done for their child.
     * POST /assignments/{id}/complete { student_id, notes? }
     */
    public function complete(Request $request, int $id)
    {
        $user = $request->user();
        $homework = Homework::findOrFail($id);

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'notes' => 'nullable|string|max:1000',
        ]);
        $student = Student::findOrFail((int) $data['student_id']);

        $this->authorizeStudentForHomework($user, $homework, $student, true);

        $diary = HomeworkDiary::firstOrCreate(
            ['homework_id' => $homework->id, 'student_id' => $student->id],
            ['status' => 'pending', 'max_score' => $homework->max_score]
        );
        $diary->status = 'completed';
        $diary->completed_at = now();
        if (array_key_exists('notes', $data) && $data['notes'] !== null) {
            $diary->student_notes = $data['notes'];
        }
        $diary->save();
        $diary->loadMissing('student');

        return response()->json([
            'success' => true,
            'message' => 'Marked as done.',
            'data' => $this->formatDiary($diary, $student),
        ]);
    }

    /**
     * Undo completion (back to pending).
     * POST /assignments/{id}/uncomplete { student_id }
     */
    public function uncomplete(Request $request, int $id)
    {
        $user = $request->user();
        $homework = Homework::findOrFail($id);

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
        ]);
        $student = Student::findOrFail((int) $data['student_id']);

        $this->authorizeStudentForHomework($user, $homework, $student, true);

        $diary = HomeworkDiary::firstOrCreate(
            ['homework_id' => $homework->id, 'student_id' => $student->id],
            ['status' => 'pending', 'max_score' => $homework->max_score]
        );
        $diary->status = 'pending';
        $diary->completed_at = null;
        $diary->save();
        $diary->loadMissing('student');

        return response()->json([
            'success' => true,
            'message' => 'Marked as pending.',
            'data' => $this->formatDiary($diary, $student),
        ]);
    }

    /**
     * Teacher-facing completion roster for one assignment.
     * GET /assignments/{id}/diary
     */
    public function diary(Request $request, int $id)
    {
        $user = $request->user();
        $homework = Homework::with(['classroom', 'stream'])->findOrFail($id);

        if (! $user || (! $user->hasTeacherLikeRole() && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']))) {
            abort(403, 'Only staff can view the homework roster.');
        }
        if ($user->hasTeacherLikeRole() && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            if ($homework->classroom_id && ! $user->canTeacherAccessClassroom((int) $homework->classroom_id)) {
                abort(403, 'You do not have access to this homework.');
            }
        }

        $studentQuery = Student::query()
            ->where('classroom_id', $homework->classroom_id)
            ->where('archive', 0)
            ->where('is_alumni', false);
        if ($homework->target_scope === 'stream' && $homework->stream_id) {
            $studentQuery->where('stream_id', $homework->stream_id);
        }
        $students = $studentQuery->orderBy('first_name')->limit(500)->get();

        $diaries = HomeworkDiary::where('homework_id', $homework->id)
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function (Student $s) use ($diaries) {
            $d = $diaries->get($s->id);

            return [
                'student_id' => $s->id,
                'student_name' => $s->full_name,
                'admission_number' => $s->admission_number,
                'status' => $d?->status ?? 'pending',
                'completed_at' => $d?->completed_at?->toIso8601String(),
                'notes' => $d?->student_notes,
            ];
        })->values();

        $completed = $rows->where('status', 'completed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'homework_id' => $homework->id,
                'title' => $homework->title,
                'total' => $rows->count(),
                'completed' => $completed,
                'pending' => $rows->count() - $completed,
                'students' => $rows,
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
            'instructions' => (string) ($h->instructions ?? ''),
            'subject_id' => $h->subject_id,
            'subject_name' => $h->subject?->name,
            'class_id' => $h->classroom_id,
            'class_name' => $h->classroom?->name,
            'stream_id' => $h->stream_id,
            'stream_name' => $h->stream?->name,
            'teacher_id' => $h->teacher_id,
            'teacher_name' => $h->teacher?->full_name,
            'due_date' => $due?->toDateString(),
            'total_marks' => (int) ($h->max_score ?? 0),
            'max_score' => $h->max_score,
            'allow_late_submission' => (bool) ($h->allow_late_submission ?? true),
            'attachments' => $this->formatAttachments($h->attachment_paths),
            'status' => $status,
            'created_at' => $h->created_at->toIso8601String(),
            'updated_at' => $h->updated_at->toIso8601String(),
        ];
    }

    protected function formatDiary(HomeworkDiary $diary, ?Student $student = null): array
    {
        $student = $student ?? $diary->student;

        return [
            'id' => $diary->id,
            'homework_id' => $diary->homework_id,
            'student_id' => $diary->student_id,
            'student_name' => $student?->full_name,
            'status' => $diary->status,
            'completed_at' => $diary->completed_at?->toIso8601String(),
            'submitted_at' => $diary->submitted_at?->toIso8601String(),
            'notes' => $diary->student_notes,
        ];
    }

    /**
     * Build typed attachment objects from a multipart request.
     *
     * @return list<array<string,mixed>>
     */
    protected function buildAttachmentsFromRequest(Request $request, int $homeworkId): array
    {
        $attachments = [];

        // Uploaded files → photo/video/document by mime.
        if ($request->hasFile('files')) {
            foreach ((array) $request->file('files') as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }
                $mime = (string) ($file->getMimeType() ?? '');
                $type = str_starts_with($mime, 'image/')
                    ? 'photo'
                    : (str_starts_with($mime, 'video/') ? 'video' : 'document');
                $path = $file->store("homework/{$homeworkId}", 'public');
                $attachments[] = [
                    'type' => $type,
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'mime' => $mime ?: null,
                    'size' => $file->getSize(),
                ];
            }
        }

        // Link fields → type link. Accepts array of strings or {url,label} objects.
        $links = $request->input('links');
        if (is_array($links)) {
            foreach ($links as $link) {
                if (is_string($link)) {
                    $url = trim($link);
                    $label = null;
                } elseif (is_array($link)) {
                    $url = trim((string) ($link['url'] ?? ''));
                    $label = $link['label'] ?? $link['name'] ?? null;
                } else {
                    continue;
                }
                if ($url === '') {
                    continue;
                }
                $attachments[] = array_filter([
                    'type' => 'link',
                    'url' => $url,
                    'name' => $label,
                ], fn ($v) => $v !== null);
            }
        }

        // Instruction blocks → type text.
        $blocks = $request->input('instruction_blocks');
        if (is_string($blocks) && $blocks !== '') {
            $decoded = json_decode($blocks, true);
            if (is_array($decoded)) {
                $blocks = $decoded;
            } else {
                $blocks = [$blocks];
            }
        }
        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                if (is_string($block)) {
                    $text = trim($block);
                    $name = null;
                } elseif (is_array($block)) {
                    $text = trim((string) ($block['text'] ?? ''));
                    $name = $block['name'] ?? $block['title'] ?? null;
                } else {
                    continue;
                }
                if ($text === '') {
                    continue;
                }
                $attachments[] = array_filter([
                    'type' => 'text',
                    'text' => $text,
                    'name' => $name,
                ], fn ($v) => $v !== null);
            }
        }

        return $attachments;
    }

    /**
     * Normalize stored attachments into typed objects (backward compatible with
     * legacy plain-string paths, which are treated as documents).
     *
     * @return list<array<string,mixed>>
     */
    protected function normalizeAttachments($raw): array
    {
        if (empty($raw)) {
            return [];
        }
        if (! is_array($raw)) {
            $raw = [$raw];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_string($item)) {
                $out[] = [
                    'type' => str_starts_with($item, 'http') ? 'link' : 'document',
                    str_starts_with($item, 'http') ? 'url' : 'path' => $item,
                    'name' => basename($item),
                ];

                continue;
            }
            if (is_array($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * Format attachments for the API response, resolving absolute file URLs.
     *
     * @return list<array<string,mixed>>
     */
    protected function formatAttachments($raw): array
    {
        $normalized = $this->normalizeAttachments($raw);

        return array_map(function (array $att) {
            $type = $att['type'] ?? 'document';
            $out = [
                'type' => $type,
                'name' => $att['name'] ?? null,
                'mime' => $att['mime'] ?? null,
                'size' => $att['size'] ?? null,
                'text' => $att['text'] ?? null,
            ];

            $url = $att['url'] ?? null;
            $path = $att['path'] ?? null;
            if ($url) {
                $out['url'] = str_starts_with($url, 'http') ? $url : url($url);
            } elseif ($path) {
                $out['path'] = $path;
                $out['url'] = url(Storage::disk('public')->url($path));
            }

            return array_filter($out, fn ($v) => $v !== null);
        }, $normalized);
    }

    /**
     * Access control shared by the diary endpoints.
     * When $requireGuardian is true, only parents/guardians of the student
     * (or admins) may proceed — used for complete/uncomplete.
     */
    protected function authorizeStudentForHomework($user, Homework $homework, Student $student, bool $requireGuardian = false): void
    {
        abort_unless((bool) $user, 403, 'Unauthenticated.');

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Academic Administrator'])) {
            return;
        }

        $isGuardian = $user->hasAnyRole(['Parent', 'Guardian', 'parent']);
        if ($isGuardian) {
            abort_unless($user->canAccessStudent($student->id), 403, 'You do not have access to this student.');

            return;
        }

        if ($requireGuardian) {
            abort(403, 'Only a parent or guardian can update this homework.');
        }

        if ($user->hasTeacherLikeRole()) {
            if ($homework->classroom_id && $user->canTeacherAccessClassroom((int) $homework->classroom_id)) {
                return;
            }
            if ($student->classroom_id && $user->canTeacherAccessClassroom((int) $student->classroom_id)) {
                return;
            }
        }

        abort(403, 'You do not have access to this homework.');
    }
}
