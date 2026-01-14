<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Homework;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomeworkController extends Controller
{
    public function __construct()
    {
        // Allow Senior Teachers and Teachers to access homework without explicit permissions
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if ($user && ($user->hasRole('Senior Teacher') || $user->hasRole('Teacher') || $user->hasRole('teacher'))) {
                return $next($request);
            }
            // For other users, check permissions
            if ($request->routeIs('academics.homework.index') || $request->routeIs('academics.homework.show')) {
                if (!$user->can('homework.view')) {
                    abort(403, 'You do not have permission to view homework.');
                }
            } elseif ($request->routeIs('academics.homework.create') || $request->routeIs('academics.homework.store')) {
                if (!$user->can('homework.create')) {
                    abort(403, 'You do not have permission to create homework.');
                }
            } elseif ($request->routeIs('academics.homework.edit') || $request->routeIs('academics.homework.update')) {
                if (!$user->can('homework.edit')) {
                    abort(403, 'You do not have permission to edit homework.');
                }
            } elseif ($request->routeIs('academics.homework.destroy')) {
                if (!$user->can('homework.delete')) {
                    abort(403, 'You do not have permission to delete homework.');
                }
            }
            return $next($request);
        })->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $query = Homework::with(['classroom','stream','subject','teacher']);

        // Teachers and senior teachers can only see homework for their assigned classes
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $query->whereIn('classroom_id', $assignedClassroomIds);
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }

        // Filters
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('instructions', 'like', "%{$search}%");
            });
        }

        $homeworks = $query->latest()->paginate(20)->withQueryString();

        // Filter classrooms and subjects based on user role
        if ($isTeacher) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
                $staff = $user->staff;
                if ($staff) {
                    $subjects = Subject::whereHas('classroomSubjects', function($q) use ($staff) {
                        $q->where('staff_id', $staff->id);
                    })->orderBy('name')->get();
                } else {
                    $subjects = collect();
                }
            } else {
                $classrooms = collect();
                $subjects = collect();
            }
        } else {
            $classrooms = Classroom::orderBy('name')->get();
            $subjects = Subject::active()->orderBy('name')->get();
        }

        return view('academics.homework.index', compact('homeworks', 'classrooms', 'subjects'));
    }

    public function create()
    {
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');

        // Filter classrooms and subjects based on user role
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            $classrooms = Classroom::orderBy('name')->get();
            $subjects = Subject::active()->orderBy('name')->get();
        } elseif ($isTeacher) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
                $staff = $user->staff;
                if ($staff) {
                    $subjects = Subject::whereHas('classroomSubjects', function($q) use ($staff) {
                        $q->where('staff_id', $staff->id);
                    })->orderBy('name')->get();
                } else {
                    $subjects = collect();
                }
            } else {
                $classrooms = collect();
                $subjects = collect();
            }
        } else {
            $classrooms = collect();
            $subjects = collect();
        }

        // Filter students based on assigned classrooms/streams for teachers
        $studentsQuery = Student::where('archive', 0)
            ->where('is_alumni', false)
            ->orderBy('last_name')->orderBy('first_name');
        if ($isTeacher) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            $streamAssignments = $user->getStreamAssignments();
            $user->applyTeacherStudentFilter($studentsQuery, $streamAssignments, $assignedClassroomIds);
        }
        $students = $studentsQuery->get();

        return view('academics.homework.create', compact('classrooms','subjects','students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'instructions'  => 'nullable|string',
            'due_date'      => 'required|date|after:today',
            'target_scope'  => 'required|in:class,stream,students',
            'classroom_id'  => 'required_if:target_scope,class,stream|nullable|exists:classrooms,id',
            'stream_id'     => 'nullable|exists:streams,id',
            'subject_id'    => 'nullable|exists:subjects,id',
            'student_ids'   => 'nullable|array|required_if:target_scope,students',
            'student_ids.*' => 'exists:students,id',
            'attachment'    => 'nullable|file|max:10240',
            'max_score'     => 'nullable|integer|min:1',
            'allow_late_submission' => 'boolean',
        ]);

        $user = Auth::user();

        // Check if teacher or senior teacher has access to classroom
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher && $request->classroom_id) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!in_array($request->classroom_id, $assignedClassroomIds)) {
                return back()
                    ->withInput()
                    ->with('error', 'You do not have access to assign homework to this classroom.');
            }
        }

        DB::beginTransaction();
        try {
            $path = null;
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('homeworks','public');
            }

            $homework = Homework::create([
                'assigned_by'   => $user->id,
                'teacher_id'    => $user->hasRole('Teacher') ? $user->staff?->id : null,
                'classroom_id'  => $request->classroom_id,
                'stream_id'     => $request->stream_id,
                'subject_id'    => $request->subject_id,
                'title'         => $request->title,
                'instructions'  => $request->instructions,
                'file_path'     => $path,
                'attachment_paths' => $path ? [$path] : null,
                'due_date'      => $request->due_date,
                'target_scope'  => $request->target_scope,
                'max_score'     => $request->max_score ?? null,
                'allow_late_submission' => $request->boolean('allow_late_submission', true),
            ]);

            if ($request->target_scope === 'students' && $request->filled('student_ids')) {
                $homework->students()->sync($request->student_ids);
            }

            DB::commit();

            return redirect()->route('academics.homework.index')->with('success','Homework assigned successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to assign homework: ' . $e->getMessage());
        }
    }

    public function show(Homework $homework)
    {
        // Check if teacher/senior teacher has access
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher && !$user->hasAnyRole(['Super Admin', 'Admin'])) {
            $assignedClassroomIds = array_unique(array_merge(
                $user->getAssignedClassroomIds(),
                $user->getSupervisedClassroomIds()
            ));
            
            if ($homework->classroom_id && !in_array($homework->classroom_id, $assignedClassroomIds)) {
                abort(403, 'You do not have access to this homework.');
            }
        }

        $homework->load([
            'classroom',
            'stream',
            'subject',
            'students',
            'teacher',
            'lessonPlan',
            'schemeOfWork',
            'homeworkDiaries.student'
        ]);
        return view('academics.homework.show', compact('homework'));
    }

    public function edit(Homework $homework)
    {
        // Check if teacher/senior teacher has access
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher && !$user->hasAnyRole(['Super Admin', 'Admin'])) {
            $assignedClassroomIds = array_unique(array_merge(
                $user->getAssignedClassroomIds(),
                $user->getSupervisedClassroomIds()
            ));
            
            if ($homework->classroom_id && !in_array($homework->classroom_id, $assignedClassroomIds)) {
                abort(403, 'You do not have access to edit this homework.');
            }
        }

        // Filter classrooms and subjects based on user role
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            $classrooms = Classroom::orderBy('name')->get();
            $subjects = Subject::active()->orderBy('name')->get();
        } else {
            $staff = $user->staff;
            if ($staff) {
                $assignedClassroomIds = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
                $subjects = Subject::whereHas('classroomSubjects', function($q) use ($staff) {
                    $q->where('staff_id', $staff->id);
                })->orderBy('name')->get();
            } else {
                $classrooms = collect();
                $subjects = collect();
            }
        }

        // Filter students based on assigned classrooms/streams for teachers
        $studentsQuery = Student::orderBy('last_name')->orderBy('first_name');
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $streamAssignments = $user->getStreamAssignments();
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            $user->applyTeacherStudentFilter($studentsQuery, $streamAssignments, $assignedClassroomIds);
        }
        $students = $studentsQuery->get();

        return view('academics.homework.edit', compact('homework', 'classrooms', 'subjects', 'students'));
    }

    public function update(Request $request, Homework $homework)
    {
        // Check if teacher has access
        $user = Auth::user();
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            $staff = $user->staff;
            if ($staff && $homework->classroom_id) {
                $hasAccess = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $homework->classroom_id)
                    ->exists();
                
                if (!$hasAccess) {
                    abort(403, 'You do not have access to update this homework.');
                }
            }
        }

        $request->validate([
            'title'         => 'required|string|max:255',
            'instructions'  => 'nullable|string',
            'due_date'      => 'required|date|after:today',
            'target_scope'  => 'required|in:class,stream,students',
            'classroom_id'  => 'required_if:target_scope,class,stream|nullable|exists:classrooms,id',
            'stream_id'     => 'nullable|exists:streams,id',
            'subject_id'    => 'nullable|exists:subjects,id',
            'student_ids'   => 'nullable|array|required_if:target_scope,students',
            'student_ids.*' => 'exists:students,id',
            'attachment'    => 'nullable|file|max:10240',
            'max_score'     => 'nullable|integer|min:1',
            'allow_late_submission' => 'boolean',
        ]);

        // Check if teacher has access to new classroom if changed
        if ($user->hasRole('Teacher') || $user->hasRole('teacher')) {
            if ($request->classroom_id && $request->classroom_id != $homework->classroom_id) {
                $staff = $user->staff;
                if ($staff) {
                    $hasAccess = DB::table('classroom_subjects')
                        ->where('staff_id', $staff->id)
                        ->where('classroom_id', $request->classroom_id)
                        ->exists();
                    
                    if (!$hasAccess) {
                        return back()
                            ->withInput()
                            ->with('error', 'You do not have access to assign homework to this classroom.');
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            $path = $homework->file_path;
            if ($request->hasFile('attachment')) {
                // Delete old attachment
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
                $path = $request->file('attachment')->store('homeworks','public');
            }

            $homework->update([
                'classroom_id'  => $request->classroom_id,
                'stream_id'     => $request->stream_id,
                'subject_id'    => $request->subject_id,
                'title'         => $request->title,
                'instructions'  => $request->instructions,
                'file_path'     => $path,
                'attachment_paths' => $path ? [$path] : null,
                'due_date'      => $request->due_date,
                'target_scope'  => $request->target_scope,
                'max_score'     => $request->max_score ?? null,
                'allow_late_submission' => $request->boolean('allow_late_submission', true),
            ]);

            if ($request->target_scope === 'students' && $request->filled('student_ids')) {
                $homework->students()->sync($request->student_ids);
            } else {
                $homework->students()->detach();
            }

            DB::commit();

            return redirect()->route('academics.homework.index')->with('success','Homework updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update homework: ' . $e->getMessage());
        }
    }

    public function destroy(Homework $homework)
    {
        // Check if teacher has access
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff && $homework->classroom_id) {
                $hasAccess = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $homework->classroom_id)
                    ->exists();
                
                if (!$hasAccess) {
                    abort(403, 'You do not have access to delete this homework.');
                }
            }
        }

        // Delete attachment if exists
        if ($homework->file_path) {
            Storage::disk('public')->delete($homework->file_path);
        }

        // Delete attachment paths if exists
        if ($homework->attachment_paths && is_array($homework->attachment_paths)) {
            foreach ($homework->attachment_paths as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $homework->delete();
        return redirect()->route('academics.homework.index')->with('success','Homework deleted.');
    }
}
