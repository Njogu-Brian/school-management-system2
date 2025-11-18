<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Homework;
use App\Models\Academics\HomeworkDiary;
use App\Models\Student;
use App\Models\Academics\LessonPlan;
use App\Models\ParentInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomeworkDiaryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:homework.view_diary')->only(['index', 'show']);
        $this->middleware('permission:homework.submit')->only(['submitForm', 'submit', 'updateSubmission']);
        $this->middleware('permission:homework.mark')->only(['markForm', 'mark']);
    }

    /**
     * Display homework diary for a student or class
     */
    public function index(Request $request)
    {
        $query = HomeworkDiary::with(['homework.subject', 'homework.classroom', 'student', 'lessonPlan']);

        // Students can only see their own homework
        if (Auth::user()->hasRole('Student')) {
            $student = Auth::user()->student;
            if ($student) {
                $query->where('student_id', $student->id);
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }

        // Teachers can see homework for their classes
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $classroomIds = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                
                $query->whereHas('homework', function($q) use ($classroomIds) {
                    $q->whereIn('classroom_id', $classroomIds);
                });
            }
        }

        // Parents can see their children's homework
        if (Auth::user()->hasRole('Parent')) {
            // Get parent_id from user (assuming users table has parent_id column)
            $user = Auth::user();
            $parentId = $user->parent_id ?? null;
            
            // Alternative: try to get from ParentInfo model relationship
            if (!$parentId && method_exists($user, 'parentInfo')) {
                $parentInfo = $user->parentInfo;
                $parentId = $parentInfo->id ?? null;
            }
            
            if ($parentId) {
                // Get students linked to parent (students have parent_id column)
                $studentIds = Student::where('parent_id', $parentId)->pluck('id')->toArray();
                if (empty($studentIds)) {
                    $query->whereRaw('1 = 0'); // No access
                } else {
                    $query->whereIn('student_id', $studentIds);
                }
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }

        // Filters
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('homework_id')) {
            $query->where('homework_id', $request->homework_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereHas('homework', function($q) use ($request) {
                $q->where('due_date', '>=', $request->date_from);
            });
        }
        if ($request->filled('date_to')) {
            $query->whereHas('homework', function($q) use ($request) {
                $q->where('due_date', '<=', $request->date_to);
            });
        }

        $homeworkDiary = $query->latest()->paginate(20)->withQueryString();

        // Get students based on user role
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $classroomIds = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                $students = Student::whereIn('classroom_id', $classroomIds)
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->get();
            } else {
                $students = collect();
            }
        } elseif (Auth::user()->hasRole('Parent')) {
            $user = Auth::user();
            $parentId = $user->parent_id ?? null;
            if (!$parentId && method_exists($user, 'parentInfo')) {
                $parentInfo = $user->parentInfo;
                $parentId = $parentInfo->id ?? null;
            }
            if ($parentId) {
                $studentIds = Student::where('parent_id', $parentId)->pluck('id')->toArray();
                $students = Student::whereIn('id', $studentIds)
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->get();
            } else {
                $students = collect();
            }
        } else {
            $students = Student::orderBy('first_name')->orderBy('last_name')->get();
        }

        // Get homeworks based on user role
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $classroomIds = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                $homeworks = Homework::whereIn('classroom_id', $classroomIds)
                    ->with('subject', 'classroom')
                    ->latest()
                    ->get();
            } else {
                $homeworks = collect();
            }
        } else {
            $homeworks = Homework::with('subject', 'classroom')->latest()->get();
        }

        return view('academics.homework_diary.index', compact('homeworkDiary', 'students', 'homeworks'));
    }

    /**
     * Show homework diary entry
     */
    public function show(HomeworkDiary $homework_diary)
    {
        // Check access
        if (Auth::user()->hasRole('Student')) {
            $student = Auth::user()->student;
            if (!$student || $homework_diary->student_id != $student->id) {
                abort(403);
            }
        }

        $homework_diary->load(['homework.subject', 'homework.classroom', 'student', 'lessonPlan']);

        return view('academics.homework_diary.show', compact('homework_diary'));
    }

    /**
     * Show form to submit homework (Student)
     */
    public function submitForm(HomeworkDiary $homework_diary)
    {
        // Check if student owns this homework
        if (Auth::user()->hasRole('Student')) {
            $student = Auth::user()->student;
            if (!$student || $homework_diary->student_id != $student->id) {
                abort(403);
            }
        }

        $homework_diary->load(['homework.subject', 'homework.classroom', 'student']);
        return view('academics.homework_diary.submit', compact('homework_diary'));
    }

    /**
     * Submit homework (Student)
     */
    public function submit(Request $request, HomeworkDiary $homework_diary)
    {
        // Check if student owns this homework
        if (Auth::user()->hasRole('Student')) {
            $student = Auth::user()->student;
            if (!$student || $homework_diary->student_id != $student->id) {
                abort(403);
            }
        }

        $validated = $request->validate([
            'student_notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $attachments = $homework_diary->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('homework_submissions/' . $homework_diary->id, 'public');
                $attachments[] = $path;
            }
        }

        $homework_diary->update([
            'status' => 'submitted',
            'student_notes' => $validated['student_notes'] ?? $homework_diary->student_notes,
            'attachments' => $attachments,
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('academics.homework-diary.show', $homework_diary)
            ->with('success', 'Homework submitted successfully.');
    }

    /**
     * Show form to mark homework (Teacher)
     */
    public function markForm(HomeworkDiary $homework_diary)
    {
        // Check authorization - teachers, admins, and super admins can mark
        if (!Auth::user()->hasAnyRole(['Teacher', 'Admin', 'Super Admin'])) {
            abort(403, 'You are not authorized to mark homework.');
        }

        $homework_diary->load(['homework', 'student']);
        return view('academics.homework_diary.mark', compact('homework_diary'));
    }

    /**
     * Mark homework (Teacher/Admin)
     */
    public function mark(Request $request, HomeworkDiary $homework_diary)
    {
        // Check authorization - teachers, admins, and super admins can mark
        if (!Auth::user()->hasAnyRole(['Teacher', 'Admin', 'Super Admin'])) {
            abort(403, 'You are not authorized to mark homework.');
        }

        $validated = $request->validate([
            'teacher_feedback' => 'nullable|string',
            'score' => 'nullable|integer|min:0|max:' . ($homework_diary->homework->max_score ?? 100),
        ]);

        $homework_diary->update([
            'status' => 'marked',
            'teacher_feedback' => $validated['teacher_feedback'] ?? null,
            'score' => $validated['score'] ?? null,
            'max_score' => $homework_diary->homework->max_score ?? 100,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('academics.homework-diary.show', $homework_diary)
            ->with('success', 'Homework marked successfully.');
    }

    /**
     * Update submission (Student)
     */
    public function updateSubmission(Request $request, HomeworkDiary $homework_diary)
    {
        // Check if student owns this homework and it's not yet marked
        if (Auth::user()->hasRole('Student')) {
            $student = Auth::user()->student;
            if (!$student || $homework_diary->student_id != $student->id) {
                abort(403);
            }
        }

        if ($homework_diary->status === 'marked') {
            return back()->with('error', 'Cannot update marked homework.');
        }

        $validated = $request->validate([
            'student_notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $attachments = $homework_diary->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('homework_submissions', 'public');
                $attachments[] = $path;
            }
        }

        $homework_diary->update([
            'student_notes' => $validated['student_notes'] ?? $homework_diary->student_notes,
            'attachments' => $attachments,
        ]);

        return back()->with('success', 'Submission updated successfully.');
    }
}

