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
 * Mobile digital diary — WhatsApp-style thread per student (parents ↔ teachers ↔ admin).
 */
class ApiDiaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = min((int) $request->input('per_page', 30), 100);

        $query = StudentDiary::with(['student.classroom', 'latestEntry.author']);

        if ($user->hasAnyRole(['Parent', 'Guardian', 'parent'])) {
            $childIds = $user->accessibleStudentIds();
            $query->whereIn('student_id', $childIds);
        } elseif ($user->hasAnyRole(['Teacher', 'teacher', 'Senior Teacher', 'Deputy Senior Teacher'])) {
            $assigned = array_unique(array_merge(
                $user->getAssignedClassroomIds(),
                $user->getSupervisedClassroomIds()
            ));
            if (empty($assigned)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('student', fn ($q) => $q->whereIn('classroom_id', $assigned));
            }
        } elseif (!$user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Academic Administrator'])) {
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
        $student = Student::with('classroom')->findOrFail($studentId);
        $this->authorizeStudentAccess($user, $student);

        $diary = $student->diary()->firstOrCreate([]);
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
        $student = Student::findOrFail($studentId);
        $this->authorizeStudentAccess($user, $student);

        $data = $request->validate([
            'content' => 'required|string|max:5000',
            'parent_entry_id' => 'nullable|exists:diary_entries,id',
            'attachments.*' => 'file|max:10240',
        ]);

        $diary = $student->diary()->firstOrCreate([]);

        if (!empty($data['parent_entry_id'])) {
            DiaryEntry::where('student_diary_id', $diary->id)
                ->findOrFail($data['parent_entry_id']);
        }

        $attachments = $this->storeAttachments($request);

        $entry = $diary->entries()->create([
            'author_id' => $user->id,
            'author_type' => $this->determineAuthorType($user),
            'parent_entry_id' => $data['parent_entry_id'] ?? null,
            'content' => $data['content'],
            'attachments' => $attachments,
        ]);

        $diary->touch();

        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
            'data' => $this->serializeEntry($entry->load('author')),
        ], 201);
    }

    protected function authorizeStudentAccess($user, Student $student): void
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Academic Administrator'])) {
            return;
        }

        if ($user->hasAnyRole(['Parent', 'Guardian', 'parent'])) {
            abort_unless($user->canAccessStudent($student->id), 403, 'You do not have access to this student.');

            return;
        }

        if ($user->hasAnyRole(['Teacher', 'teacher', 'Senior Teacher', 'Deputy Senior Teacher'])) {
            $assigned = array_unique(array_merge(
                $user->getAssignedClassroomIds(),
                $user->getSupervisedClassroomIds()
            ));
            if (in_array($student->classroom_id, $assigned, true)) {
                return;
            }
        }

        abort(403, 'You do not have access to this diary.');
    }

    protected function determineAuthorType($user): string
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Academic Administrator'])) {
            return 'admin';
        }
        if ($user->hasAnyRole(['Teacher', 'teacher', 'Senior Teacher', 'Deputy Senior Teacher'])) {
            return 'teacher';
        }
        if ($user->hasAnyRole(['Parent', 'Guardian', 'parent'])) {
            return 'parent';
        }

        return 'user';
    }

    protected function storeAttachments(Request $request): ?array
    {
        if (!$request->hasFile('attachments')) {
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
