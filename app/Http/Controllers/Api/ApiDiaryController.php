<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\DiaryEntry;
use App\Models\Academics\StudentDiary;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Mobile digital diary — channel-scoped threads per student:
 * - teacher_parent: Teacher ↔ Parent
 * - admin_parent: Admin ↔ Parent (not visible to teachers in Work mode)
 *
 * Dual-role accounts honor X-App-Mode: home → guardian scope (same as ApiStudentController).
 */
class ApiDiaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = min((int) $request->input('per_page', 30), 100);
        $channel = StudentDiary::normalizeChannel($request->input('channel'));
        $appMode = $this->resolveAppMode($request);
        $scopeAsParent = $user->shouldScopeAsParent($appMode);

        $this->assertCanListChannel($user, $channel, $scopeAsParent);

        $query = StudentDiary::with(['student.classroom', 'latestEntry.author'])
            ->where('channel', $channel);

        if ($scopeAsParent) {
            $childIds = $user->accessibleStudentIds();
            $query->whereIn('student_id', $childIds === [] ? [-1] : $childIds);
        } elseif ($this->isTeacherRole($user)) {
            // Teachers never see admin_parent (assertCanListChannel already blocks).
            $assigned = array_unique(array_merge(
                $user->getAssignedClassroomIds(),
                $user->getSupervisedClassroomIds()
            ));
            if (empty($assigned)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('student', fn ($q) => $q->whereIn('classroom_id', $assigned));
            }
        } elseif (! $this->isAdminRole($user)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->student_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderByDesc('updated_at')->paginate($perPage);
        $data = $paginated->getCollection()->map(function (StudentDiary $diary) use ($user) {
            $student = $diary->student;
            $latest = $diary->latestEntry;

            return [
                'id' => $diary->id,
                'channel' => $diary->channel ?? StudentDiary::CHANNEL_TEACHER_PARENT,
                'student_id' => $diary->student_id,
                'student_name' => $student?->full_name ?? trim(($student->first_name ?? '').' '.($student->last_name ?? '')),
                'admission_number' => $student?->admission_number,
                'class_name' => $student?->classroom?->name,
                'unread_count' => $diary->unreadCountForUser($user->id),
                'latest_entry' => $latest ? [
                    'id' => $latest->id,
                    'content' => $latest->content,
                    'author_type' => $latest->author_type,
                    'author_name' => $latest->author?->name,
                    'created_at' => optional($latest->created_at)->toIso8601String(),
                ] : null,
                'updated_at' => optional($diary->updated_at)->toIso8601String(),
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

    public function showForStudent(Request $request, int $studentId): JsonResponse
    {
        $user = Auth::user();
        $channel = StudentDiary::normalizeChannel($request->input('channel'));
        $student = Student::with('classroom')->findOrFail($studentId);
        $appMode = $this->resolveAppMode($request);
        $this->authorizeStudentChannelAccess($user, $student, $channel, $appMode);

        $diary = StudentDiary::query()->firstOrCreate(
            ['student_id' => $student->id, 'channel' => $channel],
            ['channel' => $channel]
        );

        $entries = $diary->entries()
            ->with(['author.staff', 'author.parentProfile'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (DiaryEntry $entry) => $this->serializeEntry($entry));

        $diary->entries()
            ->where('author_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $diary->id,
                'channel' => $diary->channel,
                'student_id' => $student->id,
                'student_name' => $student->full_name ?? trim($student->first_name.' '.$student->last_name),
                'class_name' => $student->classroom?->name,
                'entries' => $entries,
            ],
        ]);
    }

    public function storeEntry(Request $request, int $studentId): JsonResponse
    {
        $user = Auth::user();
        $channel = StudentDiary::normalizeChannel($request->input('channel'));
        $student = Student::findOrFail($studentId);
        $appMode = $this->resolveAppMode($request);
        $this->authorizeStudentChannelAccess($user, $student, $channel, $appMode);

        $data = $request->validate([
            'content' => 'required|string|max:5000',
            'parent_entry_id' => 'nullable|exists:diary_entries,id',
            'channel' => 'nullable|in:teacher_parent,admin_parent',
            'attachments.*' => 'file|max:10240',
        ]);

        $diary = StudentDiary::query()->firstOrCreate(
            ['student_id' => $student->id, 'channel' => $channel],
            ['channel' => $channel]
        );

        if (! empty($data['parent_entry_id'])) {
            DiaryEntry::where('student_diary_id', $diary->id)
                ->findOrFail($data['parent_entry_id']);
        }

        $attachments = $this->storeAttachments($request);

        $entry = $diary->entries()->create([
            'author_id' => $user->id,
            'author_type' => $this->determineAuthorType($user, $appMode),
            'parent_entry_id' => $data['parent_entry_id'] ?? null,
            'content' => $data['content'],
            'attachments' => $attachments,
        ]);

        $diary->touch();

        if (in_array($entry->author_type, ['teacher', 'admin'], true)) {
            try {
                $preview = \Illuminate\Support\Str::limit(trim(strip_tags((string) $entry->content)), 120);
                app(\App\Services\ParentAppNotifyService::class)->notifyDiaryComment($student, $preview, $user->id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
            'data' => $this->serializeEntry($entry->load('author')),
        ], 201);
    }

    /**
     * @return 'home'|null  Normalized app shell mode for shouldScopeAsParent().
     */
    protected function resolveAppMode(Request $request): ?string
    {
        $appMode = strtolower((string) $request->header('X-App-Mode', ''));

        return $appMode === 'home' ? 'home' : null;
    }

    /**
     * Teachers in Work mode cannot list admin↔parent threads.
     * Dual-role accounts in Home mode (guardian scope) may list their children's admin threads.
     */
    protected function assertCanListChannel($user, string $channel, bool $scopeAsParent): void
    {
        if ($channel !== StudentDiary::CHANNEL_ADMIN_PARENT) {
            return;
        }

        if ($this->isAdminRole($user) || $scopeAsParent) {
            return;
        }

        if ($this->isTeacherRole($user)) {
            abort(403, 'Teachers cannot access school/admin parent conversations.');
        }
    }

    protected function authorizeStudentChannelAccess($user, Student $student, string $channel, ?string $appMode = null): void
    {
        $scopeAsParent = $user->shouldScopeAsParent($appMode);

        if ($channel === StudentDiary::CHANNEL_ADMIN_PARENT) {
            // Explicit teacher Work-mode rejection (matches assertCanListChannel).
            if ($this->isTeacherRole($user) && ! $this->isAdminRole($user) && ! $scopeAsParent) {
                abort(403, 'Teachers cannot access school/admin parent conversations.');
            }

            if ($this->isAdminRole($user)) {
                return;
            }

            if ($scopeAsParent && $user->canAccessStudent($student->id)) {
                return;
            }

            abort(403, 'You do not have access to this school/admin conversation.');
        }

        // teacher_parent
        if ($this->isAdminRole($user) && ! $scopeAsParent) {
            // Administrative oversight of teacher–parent threads is intentional for office roles.
            return;
        }

        if ($scopeAsParent) {
            abort_unless($user->canAccessStudent($student->id), 403, 'You do not have access to this student.');

            return;
        }

        if ($this->isTeacherRole($user)) {
            $assigned = array_unique(array_merge(
                $user->getAssignedClassroomIds(),
                $user->getSupervisedClassroomIds()
            ));
            if (in_array((int) $student->classroom_id, array_map('intval', $assigned), true)) {
                return;
            }
        }

        abort(403, 'You do not have access to this diary.');
    }

    protected function isAdminRole($user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Academic Administrator', 'Director']);
    }

    protected function isTeacherRole($user): bool
    {
        return $user->hasAnyRole(['Teacher', 'teacher', 'Senior Teacher', 'Deputy Senior Teacher']);
    }

    protected function determineAuthorType($user, ?string $appMode = null): string
    {
        if ($user->shouldScopeAsParent($appMode)) {
            return 'parent';
        }
        if ($this->isAdminRole($user)) {
            return 'admin';
        }
        if ($this->isTeacherRole($user)) {
            return 'teacher';
        }

        return 'user';
    }

    protected function storeAttachments(Request $request): ?array
    {
        if (! $request->hasFile('attachments')) {
            return null;
        }

        $paths = [];
        foreach ($request->file('attachments') as $file) {
            $paths[] = $file->store('diary_entries', 'public');
        }

        return $paths;
    }

    protected function serializeEntry(DiaryEntry $entry): array
    {
        $urls = [];
        foreach ($entry->attachments ?? [] as $path) {
            $urls[] = Storage::disk('public')->url($path);
        }

        return [
            'id' => $entry->id,
            'content' => $entry->content,
            'author_id' => $entry->author_id,
            'author_type' => $entry->author_type,
            'author_name' => $entry->author?->name,
            'parent_entry_id' => $entry->parent_entry_id,
            'attachments' => $entry->attachments,
            'attachment_urls' => $urls,
            'is_read' => (bool) $entry->is_read,
            'is_mine' => $entry->author_id === Auth::id(),
            'created_at' => optional($entry->created_at)->toIso8601String(),
        ];
    }
}
