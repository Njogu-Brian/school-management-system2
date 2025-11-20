<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\StudentDiary;
use App\Models\Academics\DiaryEntry;
use App\Models\Student;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentDiaryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:diaries.view')->only(['index', 'show']);
        $this->middleware('permission:diaries.create')->only(['storeEntry', 'bulkStore']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = StudentDiary::with(['student.classroom', 'latestEntry.author']);

        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $streamAssignments = $user->getStreamAssignments();
            $assignedClassrooms = $user->getAssignedClassroomIds();
            
            if (!empty($streamAssignments)) {
                // Teacher has stream assignments - filter by those specific streams
                $query->whereHas('student', function ($q) use ($streamAssignments, $assignedClassrooms, $user) {
                    $q->where(function($subQ) use ($streamAssignments, $assignedClassrooms, $user) {
                        // Students from assigned streams
                        foreach ($streamAssignments as $assignment) {
                            $subQ->orWhere(function($streamQ) use ($assignment) {
                                $streamQ->where('classroom_id', $assignment->classroom_id)
                                       ->where('stream_id', $assignment->stream_id);
                            });
                        }
                        
                        // Also include students from direct classroom assignments (not via streams)
                        $directClassroomIds = DB::table('classroom_teacher')
                            ->where('teacher_id', $user->id)
                            ->pluck('classroom_id')
                            ->toArray();
                        
                        $subjectClassroomIds = [];
                        if ($user->staff) {
                            $subjectClassroomIds = DB::table('classroom_subjects')
                                ->where('staff_id', $user->staff->id)
                                ->distinct()
                                ->pluck('classroom_id')
                                ->toArray();
                        }
                        
                        $streamClassroomIds = array_column($streamAssignments, 'classroom_id');
                        $nonStreamClassroomIds = array_diff(
                            array_unique(array_merge($directClassroomIds, $subjectClassroomIds)),
                            $streamClassroomIds
                        );
                        
                        if (!empty($nonStreamClassroomIds)) {
                            $subQ->orWhereIn('classroom_id', $nonStreamClassroomIds);
                        }
                    });
                });
            } else {
                // No stream assignments, show all students from assigned classrooms
                if (!empty($assignedClassrooms)) {
                    $query->whereHas('student', function ($q) use ($assignedClassrooms) {
                        $q->whereIn('classroom_id', $assignedClassrooms);
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        if ($request->filled('classroom_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('classroom_id', $request->classroom_id);
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }

        $diaries = $query->orderByDesc('updated_at')->paginate(30)->withQueryString();
        $classrooms = Classroom::orderBy('name')->get();

        $studentsQuery = Student::with('classroom')->orderBy('first_name');
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $assignedClassrooms = $user->getAssignedClassroomIds();
            if (!empty($assignedClassrooms)) {
                $studentsQuery->whereIn('classroom_id', $assignedClassrooms);
            } else {
                $studentsQuery->whereRaw('1 = 0');
            }
        }
        $students = $studentsQuery->get();

        return view('academics.diaries.index', compact('diaries', 'classrooms', 'students'));
    }

    public function show(StudentDiary $diary)
    {
        $this->authorizeDiaryAccess($diary);

        $diary->load(['student.classroom']);

        $entries = $diary->entries()
            ->with(['author.staff', 'author.parentProfile'])
            ->orderBy('created_at')
            ->get();

        $diary->entries()
            ->where('author_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('academics.diaries.show', compact('diary', 'entries'));
    }

    public function storeEntry(StudentDiary $diary, Request $request)
    {
        $this->authorizeDiaryAccess($diary);

        $data = $request->validate([
            'content' => 'required|string',
            'parent_entry_id' => 'nullable|exists:diary_entries,id',
            'attachments.*' => 'file|max:10240',
        ]);

        if (!empty($data['parent_entry_id'])) {
            DiaryEntry::where('student_diary_id', $diary->id)
                ->findOrFail($data['parent_entry_id']);
        }

        $attachments = $this->storeAttachments($request);

        $diary->entries()->create([
            'author_id' => Auth::id(),
            'author_type' => $this->determineAuthorType(Auth::user()),
            'parent_entry_id' => $data['parent_entry_id'] ?? null,
            'content' => $data['content'],
            'attachments' => $attachments,
        ]);

        return back()->with('success', 'Diary entry added successfully.');
    }

    public function bulkStore(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'target_scope' => 'required|in:student,classroom,school',
            'student_id' => 'required_if:target_scope,student|nullable|exists:students,id',
            'classroom_id' => 'required_if:target_scope,classroom|nullable|exists:classrooms,id',
            'content' => 'required|string',
            'attachments.*' => 'file|max:10240',
        ]);

        if (($user->hasRole('Teacher') || $user->hasRole('teacher')) && $data['target_scope'] === 'school') {
            return back()->with('error', 'Teachers cannot broadcast to the entire school.');
        }

        $attachments = $this->storeAttachments($request);

        $diaries = collect();

        if ($data['target_scope'] === 'student') {
            $student = Student::findOrFail($data['student_id']);
            $diary = $student->diary()->firstOrCreate([]);
            $this->authorizeDiaryAccess($diary);
            $diaries = collect([$diary]);
        } elseif ($data['target_scope'] === 'classroom') {
            $classroom = Classroom::findOrFail($data['classroom_id']);
            $this->authorizeClassroomAccess($classroom);
            $this->ensureDiariesExistForQuery(Student::where('classroom_id', $classroom->id));
            $diaries = StudentDiary::whereHas('student', function ($q) use ($classroom) {
                $q->where('classroom_id', $classroom->id);
            })->get();
        } else {
            $this->authorizeSchoolBroadcast();
            $this->ensureDiariesExistForQuery(Student::query());
            $diaries = StudentDiary::with('student')->get();
        }

        DB::transaction(function () use ($diaries, $attachments, $data, $user) {
            foreach ($diaries as $diary) {
                $diary->entries()->create([
                    'author_id' => $user->id,
                    'author_type' => $this->determineAuthorType($user),
                    'content' => $data['content'],
                    'attachments' => $attachments,
                ]);
            }
        });

        return back()->with('success', 'Diary entry sent to selected recipients.');
    }

    protected function ensureDiariesExistForQuery($studentQuery): void
    {
        $studentQuery->whereDoesntHave('diary')
            ->chunkById(200, function ($students) {
                foreach ($students as $student) {
                    $student->diary()->create();
                }
            });
    }

    protected function authorizeDiaryAccess(StudentDiary $diary): void
    {
        $user = Auth::user();

        $diary->loadMissing('student');

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            return;
        }

        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $assigned = $user->getAssignedClassroomIds();
            if (in_array($diary->student->classroom_id, $assigned)) {
                return;
            }
        }

        abort(403, 'You do not have access to this diary.');
    }

    protected function authorizeClassroomAccess(Classroom $classroom): void
    {
        $user = Auth::user();

        $classroom->loadMissing('students');

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            return;
        }

        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $assigned = $user->getAssignedClassroomIds();
            if (!in_array($classroom->id, $assigned)) {
                abort(403, 'You do not have access to this classroom.');
            }
            return;
        }

        abort(403, 'You do not have access to this classroom.');
    }

    protected function authorizeSchoolBroadcast(): void
    {
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Only administrators can broadcast to the entire school.');
        }
    }

    protected function determineAuthorType($user): string
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            return 'admin';
        }

        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            return 'teacher';
        }

        if ($user->hasRole('Parent') || $user->hasRole('parent')) {
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
}

