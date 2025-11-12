<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\PortfolioAssessment;
use App\Models\Academics\CBCPerformanceLevel;
use App\Models\Student;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PortfolioAssessmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:portfolio_assessments.view')->only(['index', 'show']);
        $this->middleware('permission:portfolio_assessments.create')->only(['create', 'store']);
        $this->middleware('permission:portfolio_assessments.edit')->only(['edit', 'update']);
        $this->middleware('permission:portfolio_assessments.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = PortfolioAssessment::with(['student', 'subject', 'classroom', 'academicYear', 'term', 'performanceLevel', 'assessor']);

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
        if ($request->filled('portfolio_type')) {
            $query->where('portfolio_type', $request->portfolio_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $portfolios = $query->latest()->paginate(20)->withQueryString();

        $classrooms = $this->getAccessibleClassrooms();
        $subjects = Subject::active()->orderBy('name')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('academics.portfolio_assessments.index', compact('portfolios', 'classrooms', 'subjects', 'years', 'terms'));
    }

    public function create()
    {
        $students = $this->getAccessibleStudents();
        $subjects = Subject::active()->orderBy('name')->get();
        $classrooms = $this->getAccessibleClassrooms();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        $performanceLevels = CBCPerformanceLevel::where('is_active', true)->ordered()->get();

        return view('academics.portfolio_assessments.create', compact('students', 'subjects', 'classrooms', 'years', 'terms', 'performanceLevels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'portfolio_type' => 'required|in:project,practical,creative,research,group_work,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'evidence_files' => 'nullable|array',
            'rubric_scores' => 'nullable|array',
            'total_score' => 'nullable|numeric|min:0|max:100',
            'performance_level_id' => 'nullable|exists:cbc_performance_levels,id',
            'assessment_date' => 'nullable|date',
            'status' => 'required|in:draft,submitted,assessed,published',
            'feedback' => 'nullable|string',
        ]);

        if (!$this->canAccessClassroom($validated['classroom_id'])) {
            return back()->with('error', 'You do not have access to this classroom.');
        }

        $validated['assessed_by'] = Auth::user()->staff?->id;
        if (!$validated['assessment_date'] && $validated['status'] === 'assessed') {
            $validated['assessment_date'] = now();
        }

        $portfolio = PortfolioAssessment::create($validated);

        return redirect()
            ->route('academics.portfolio-assessments.show', $portfolio)
            ->with('success', 'Portfolio assessment created successfully.');
    }

    public function show(PortfolioAssessment $portfolio_assessment)
    {
        if (!$this->canAccessClassroom($portfolio_assessment->classroom_id)) {
            abort(403);
        }

        $portfolio_assessment->load([
            'student', 'subject', 'classroom', 'academicYear', 'term',
            'performanceLevel', 'assessor'
        ]);

        return view('academics.portfolio_assessments.show', compact('portfolio_assessment'));
    }

    public function edit(PortfolioAssessment $portfolio_assessment)
    {
        if (!$this->canAccessClassroom($portfolio_assessment->classroom_id)) {
            abort(403);
        }

        $students = $this->getAccessibleStudents();
        $subjects = Subject::active()->orderBy('name')->get();
        $classrooms = $this->getAccessibleClassrooms();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        $performanceLevels = CBCPerformanceLevel::where('is_active', true)->ordered()->get();

        return view('academics.portfolio_assessments.edit', compact('portfolio_assessment', 'students', 'subjects', 'classrooms', 'years', 'terms', 'performanceLevels'));
    }

    public function update(Request $request, PortfolioAssessment $portfolio_assessment)
    {
        if (!$this->canAccessClassroom($portfolio_assessment->classroom_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'portfolio_type' => 'required|in:project,practical,creative,research,group_work,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'evidence_files' => 'nullable|array',
            'rubric_scores' => 'nullable|array',
            'total_score' => 'nullable|numeric|min:0|max:100',
            'performance_level_id' => 'nullable|exists:cbc_performance_levels,id',
            'assessment_date' => 'nullable|date',
            'status' => 'required|in:draft,submitted,assessed,published',
            'feedback' => 'nullable|string',
        ]);

        if ($validated['status'] === 'assessed' && !$portfolio_assessment->assessed_by) {
            $validated['assessed_by'] = Auth::user()->staff?->id;
            if (!$validated['assessment_date']) {
                $validated['assessment_date'] = now();
            }
        }

        $portfolio_assessment->update($validated);

        return redirect()
            ->route('academics.portfolio-assessments.show', $portfolio_assessment)
            ->with('success', 'Portfolio assessment updated successfully.');
    }

    public function destroy(PortfolioAssessment $portfolio_assessment)
    {
        if (!$this->canAccessClassroom($portfolio_assessment->classroom_id) && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            abort(403);
        }

        $portfolio_assessment->delete();

        return redirect()
            ->route('academics.portfolio-assessments.index')
            ->with('success', 'Portfolio assessment deleted successfully.');
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

    private function getAccessibleStudents()
    {
        $classrooms = $this->getAccessibleClassrooms();
        if ($classrooms->isEmpty()) {
            return collect();
        }

        return \App\Models\Student::whereIn('classroom_id', $classrooms->pluck('id'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
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
