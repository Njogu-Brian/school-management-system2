<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exams.view')->only(['index', 'show', 'timetable']);
        $this->middleware('permission:exams.create')->only(['create', 'store']);
        $this->middleware('permission:exams.edit')->only(['edit', 'update']);
        $this->middleware('permission:exams.delete')->only(['destroy']);
        $this->middleware('permission:exams.publish')->only(['publish']);
    }

    public function index(Request $request)
    {
        $query = Exam::with([
            'academicYear',
            'term',
            'classroom',
            'subject',
            'creator'
        ])
        ->withCount(['marks', 'schedules']);

        // Teachers can only see exams for their assigned classes (unless they're supervisors)
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher && !is_supervisor()) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $query->whereIn('classroom_id', $assignedClassroomIds);
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }
        
        // Supervisors can see exams for their subordinates' classes
        if (is_supervisor() && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            $subordinateClassroomIds = get_subordinate_classroom_ids();
            $ownClassroomIds = Auth::user()->staff ? DB::table('classroom_subjects')
                ->where('staff_id', Auth::user()->staff->id)
                ->distinct()
                ->pluck('classroom_id')
                ->toArray() : [];
            
            $allClassroomIds = array_unique(array_merge($ownClassroomIds, $subordinateClassroomIds));
            if (!empty($allClassroomIds)) {
                $query->whereIn('classroom_id', $allClassroomIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhereHas('subject', function($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('classroom', function($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('year_id')) {
            $query->where('academic_year_id', $request->year_id);
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        // Statistics (filtered by teacher or senior teacher if applicable)
        $statsQuery = Exam::query();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $statsQuery->whereIn('classroom_id', $assignedClassroomIds);
            } else {
                $statsQuery->whereRaw('1 = 0');
            }
        }

        $stats = [
            'total' => $statsQuery->count(),
            'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
            'open' => (clone $statsQuery)->where('status', 'open')->count(),
            'marking' => (clone $statsQuery)->where('status', 'marking')->count(),
            'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
            'published' => (clone $statsQuery)->where('status', 'published')->count(),
            'locked' => (clone $statsQuery)->where('status', 'locked')->count(),
        ];

        $exams = $query->latest('created_at')->paginate(20)->withQueryString();

        $types = ExamType::orderBy('name')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        
        // Filter classrooms based on user role
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
            } else {
                $classrooms = collect();
            }
        } else {
            $classrooms = Classroom::orderBy('name')->get();
        }

        $subjects = Subject::active()->orderBy('name')->get();

        return view('academics.exams.index', compact(
            'exams',
            'stats',
            'types',
            'years',
            'terms',
            'classrooms',
            'subjects'
        ));
    }


    public function create()
    {
        // Filter classrooms based on user role
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
            } else {
                $classrooms = collect();
            }
        } else {
            $classrooms = Classroom::orderBy('name')->get();
        }

        return view('academics.exams.create', [
            'years'      => AcademicYear::orderByDesc('year')->get(),
            'terms'      => Term::orderBy('name')->get(),
            'classrooms' => $classrooms,
            'subjects'   => Subject::active()->orderBy('name')->get(),
            'types'      => ExamType::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:cat,midterm,endterm,sba,mock,quiz',
            'modality'         => 'required|in:physical,online',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'classroom_id'     => 'nullable|exists:classrooms,id',
            'stream_id'        => 'nullable|exists:streams,id',
            'subject_id'       => 'nullable|exists:subjects,id',
            'starts_on'        => 'nullable|date',
            'ends_on'          => 'nullable|date|after_or_equal:starts_on',
            'max_marks'        => 'required|numeric|min:1',
            'weight'           => 'required|numeric|min:0|max:100',
            'publish_exam'     => 'boolean',
            'publish_result'   => 'boolean',
        ]);

        // Check if teacher or senior teacher has access to classroom
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher && $v['classroom_id']) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!in_array($v['classroom_id'], $assignedClassroomIds)) {
                return back()
                    ->withInput()
                    ->with('error', 'You do not have access to this classroom.');
            }
        }

        $exam = Exam::create($v + [
            'created_by' => Auth::id(),
            'status' => 'draft',
        ]);

        return redirect()
            ->route('academics.exams.index')
            ->with('success', 'Exam created successfully.');
    }

    public function edit(Exam $exam)
    {
        // Check if teacher has access to this exam's classroom
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff && $exam->classroom_id) {
                $hasAccess = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $exam->classroom_id)
                    ->exists();
                
                if (!$hasAccess) {
                    abort(403, 'You do not have access to this exam.');
                }
            }
        }

        // Filter classrooms based on user role
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
            } else {
                $classrooms = collect();
            }
        } else {
            $classrooms = Classroom::orderBy('name')->get();
        }

        return view('academics.exams.edit', [
            'exam'       => $exam->load(['academicYear', 'term', 'classroom', 'subject']),
            'years'      => AcademicYear::orderByDesc('year')->get(),
            'terms'      => Term::orderBy('name')->get(),
            'classrooms' => $classrooms,
            'subjects'   => Subject::active()->orderBy('name')->get(),
            'types'      => ExamType::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        // Check if teacher has access to this exam's classroom
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff && $exam->classroom_id) {
                $hasAccess = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $exam->classroom_id)
                    ->exists();
                
                if (!$hasAccess) {
                    abort(403, 'You do not have access to this exam.');
                }
            }

            // Teachers can't change status to published or locked
            if ($request->has('status') && in_array($request->status, ['published', 'locked'])) {
                if (!Auth::user()->hasPermissionTo('exams.publish')) {
                    return back()
                        ->withInput()
                        ->with('error', 'You do not have permission to publish or lock exams.');
                }
            }
        }

        $v = $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:cat,midterm,endterm,sba,mock,quiz',
            'modality'         => 'required|in:physical,online',
            'starts_on'        => 'nullable|date',
            'ends_on'          => 'nullable|date|after_or_equal:starts_on',
            'max_marks'        => 'required|numeric|min:1',
            'weight'           => 'required|numeric|min:0|max:100',
            'status'           => 'required|in:draft,open,marking,moderation,approved,published,locked',
            'publish_exam'     => 'boolean',
            'publish_result'   => 'boolean',
        ]);

        // Validate status transition
        if (method_exists($exam, 'canTransitionTo') && $v['status'] !== $exam->status) {
            if (!$exam->canTransitionTo($v['status'])) {
                return back()
                    ->withInput()
                    ->with('error', "Cannot transition from {$exam->status} to {$v['status']}.");
            }
        }

        // Handle status-specific actions
        if ($v['status'] === 'published' && !$exam->published_at) {
            $v['published_at'] = now();
            $v['published_by'] = Auth::id();
        }

        if ($v['status'] === 'locked' && !$exam->locked_at) {
            $v['locked_at'] = now();
            $v['locked_by'] = Auth::id();
        }

        $exam->update($v);

        return redirect()
            ->route('academics.exams.index')
            ->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam)
    {
        // Prevent deletion of locked or published exams
        if ($exam->is_locked || $exam->status === 'published') {
            return back()
                ->with('error', 'Cannot delete locked or published exams.');
        }

        // Check if exam has marks
        if ($exam->marks_count > 0) {
            return back()
                ->with('error', 'Cannot delete exam with existing marks. Archive it instead.');
        }

        $exam->delete();

        return back()->with('success', 'Exam deleted successfully.');
    }

    public function timetable(Request $request)
    {
        $query = \App\Models\Academics\ExamSchedule::with([
            'exam',
            'subject',
            'classroom',
            'exam.term',
            'exam.academicYear'
        ]);

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        $schedules = $query->orderBy('exam_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('exam_date');

        $exams = Exam::latest()->get();
        $classrooms = Classroom::orderBy('name')->get();

        return view('academics.exams.timetable', compact('schedules', 'exams', 'classrooms'));
    }

    public function show(Exam $exam)
    {
        $exam->load([
            'academicYear',
            'term',
            'classroom',
            'subject',
            'creator',
            'marks.student',
            'marks.subject',
            'schedules'
        ]);

        $stats = [
            'total_students' => $exam->students_count,
            'marks_entered' => $exam->marks_count,
            'marks_pending' => max(0, $exam->students_count - $exam->marks_count),
            'completion_rate' => $exam->students_count > 0 
                ? round(($exam->marks_count / $exam->students_count) * 100, 1) 
                : 0,
        ];

        return view('academics.exams.show', compact('exam', 'stats'));
    }
}
