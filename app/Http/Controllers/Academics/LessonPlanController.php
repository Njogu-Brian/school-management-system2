<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\LessonPlan;
use App\Models\Academics\SchemeOfWork;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\Academics\CBCSubstrand;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LessonPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lesson_plans.view')->only(['index', 'show']);
        $this->middleware('permission:lesson_plans.create')->only(['create', 'store']);
        $this->middleware('permission:lesson_plans.edit')->only(['edit', 'update']);
        $this->middleware('permission:lesson_plans.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = LessonPlan::with(['subject', 'classroom', 'academicYear', 'term', 'substrand', 'creator']);

        // Teachers can only see their assigned classes
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $assignedClassroomIds = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                $query->whereIn('classroom_id', $assignedClassroomIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filters
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('planned_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('planned_date', '<=', $request->date_to);
        }

        $lessonPlans = $query->latest('planned_date')->paginate(20)->withQueryString();

        $classrooms = $this->getAccessibleClassrooms();
        $subjects = Subject::active()->orderBy('name')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('academics.lesson_plans.index', compact('lessonPlans', 'classrooms', 'subjects', 'years', 'terms'));
    }

    public function create()
    {
        $subjects = Subject::active()->orderBy('name')->get();
        $classrooms = $this->getAccessibleClassrooms();
        $schemes = SchemeOfWork::where('status', 'active')->with('subject', 'classroom')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        $substrands = CBCSubstrand::active()->with('strand')->ordered()->get();

        return view('academics.lesson_plans.create', compact('subjects', 'classrooms', 'schemes', 'years', 'terms', 'substrands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scheme_of_work_id' => 'nullable|exists:schemes_of_work,id',
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'substrand_id' => 'nullable|exists:cbc_substrands,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
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
        ]);

        if (!$this->canAccessClassroom($validated['classroom_id'])) {
            return back()->with('error', 'You do not have access to this classroom.');
        }

        $validated['created_by'] = Auth::user()->staff?->id;
        $validated['status'] = 'planned';
        $validated['duration_minutes'] = $validated['duration_minutes'] ?? 40;

        $lessonPlan = LessonPlan::create($validated);

        return redirect()
            ->route('academics.lesson-plans.show', $lessonPlan)
            ->with('success', 'Lesson plan created successfully.');
    }

    public function show(LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id)) {
            abort(403);
        }

        $lesson_plan->load([
            'subject', 'classroom', 'academicYear', 'term',
            'substrand.strand', 'schemeOfWork', 'creator'
        ]);

        return view('academics.lesson_plans.show', compact('lesson_plan'));
    }

    public function edit(LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id)) {
            abort(403);
        }

        if ($lesson_plan->isCompleted() && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            return back()->with('error', 'Cannot edit completed lesson plan.');
        }

        $subjects = Subject::active()->orderBy('name')->get();
        $classrooms = $this->getAccessibleClassrooms();
        $schemes = SchemeOfWork::where('status', 'active')->with('subject', 'classroom')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        $substrands = CBCSubstrand::active()->with('strand')->ordered()->get();

        return view('academics.lesson_plans.edit', compact('lesson_plan', 'subjects', 'classrooms', 'schemes', 'years', 'terms', 'substrands'));
    }

    public function update(Request $request, LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lesson_number' => 'nullable|string|max:50',
            'planned_date' => 'required|date',
            'actual_date' => 'nullable|date',
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
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'execution_status' => 'nullable|in:excellent,good,fair,poor',
            'challenges' => 'nullable|string',
            'improvements' => 'nullable|string',
        ]);

        $lesson_plan->update($validated);

        return redirect()
            ->route('academics.lesson-plans.show', $lesson_plan)
            ->with('success', 'Lesson plan updated successfully.');
    }

    public function destroy(LessonPlan $lesson_plan)
    {
        if (!$this->canAccessClassroom($lesson_plan->classroom_id) && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            abort(403);
        }

        $lesson_plan->delete();

        return redirect()
            ->route('academics.lesson-plans.index')
            ->with('success', 'Lesson plan deleted successfully.');
    }

    private function getAccessibleClassrooms()
    {
        if (Auth::user()->hasAnyRole(['Admin', 'Super Admin', 'Secretary'])) {
            return Classroom::orderBy('name')->get();
        }

        $staff = Auth::user()->staff;
        if (!$staff) {
            return collect();
        }

        $classroomIds = DB::table('classroom_subjects')
            ->where('staff_id', $staff->id)
            ->distinct()
            ->pluck('classroom_id')
            ->toArray();

        return Classroom::whereIn('id', $classroomIds)->orderBy('name')->get();
    }

    private function canAccessClassroom(int $classroomId): bool
    {
        if (Auth::user()->hasAnyRole(['Admin', 'Super Admin', 'Secretary'])) {
            return true;
        }

        $staff = Auth::user()->staff;
        if (!$staff) {
            return false;
        }

        return DB::table('classroom_subjects')
            ->where('staff_id', $staff->id)
            ->where('classroom_id', $classroomId)
            ->exists();
    }
}
