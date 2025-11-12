<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\SchemeOfWork;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\CBCSubstrand;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SchemeOfWorkController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:schemes_of_work.view')->only(['index', 'show']);
        $this->middleware('permission:schemes_of_work.create')->only(['create', 'store']);
        $this->middleware('permission:schemes_of_work.edit')->only(['edit', 'update']);
        $this->middleware('permission:schemes_of_work.delete')->only(['destroy']);
        $this->middleware('permission:schemes_of_work.approve')->only(['approve']);
    }

    public function index(Request $request)
    {
        $query = SchemeOfWork::with(['subject', 'classroom', 'academicYear', 'term', 'creator']);

        // Teachers can only see their assigned classes
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $assignedClassroomIds = \DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
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
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $schemes = $query->latest()->paginate(20)->withQueryString();

        $classrooms = Classroom::orderBy('name')->get();
        $subjects = Subject::active()->orderBy('name')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('academics.schemes_of_work.index', compact('schemes', 'classrooms', 'subjects', 'years', 'terms'));
    }

    public function create()
    {
        $subjects = Subject::active()->orderBy('name')->get();
        $classrooms = $this->getAccessibleClassrooms();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        $strands = CBCStrand::active()->ordered()->get();

        return view('academics.schemes_of_work.create', compact('subjects', 'classrooms', 'years', 'terms', 'strands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'strands_coverage' => 'nullable|array',
            'substrands_coverage' => 'nullable|array',
            'general_remarks' => 'nullable|string',
        ]);

        // Check authorization
        if (!$this->canAccessClassroom($validated['classroom_id'])) {
            return back()->with('error', 'You do not have access to this classroom.');
        }

        $validated['created_by'] = Auth::user()->staff?->id;
        $scheme = SchemeOfWork::create($validated);

        return redirect()
            ->route('academics.schemes-of-work.show', $scheme)
            ->with('success', 'Scheme of work created successfully.');
    }

    public function show(SchemeOfWork $schemes_of_work)
    {
        if (!$this->canAccessClassroom($schemes_of_work->classroom_id)) {
            abort(403, 'You do not have access to this scheme of work.');
        }

        $schemes_of_work->load([
            'subject', 'classroom', 'academicYear', 'term',
            'creator', 'approver', 'lessonPlans'
        ]);

        return view('academics.schemes_of_work.show', compact('schemes_of_work'));
    }

    public function edit(SchemeOfWork $schemes_of_work)
    {
        if (!$this->canAccessClassroom($schemes_of_work->classroom_id)) {
            abort(403);
        }

        if ($schemes_of_work->isApproved() && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            return back()->with('error', 'Cannot edit approved scheme of work.');
        }

        $subjects = Subject::active()->orderBy('name')->get();
        $classrooms = $this->getAccessibleClassrooms();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        $strands = CBCStrand::active()->ordered()->get();

        return view('academics.schemes_of_work.edit', compact('schemes_of_work', 'subjects', 'classrooms', 'years', 'terms', 'strands'));
    }

    public function update(Request $request, SchemeOfWork $schemes_of_work)
    {
        if (!$this->canAccessClassroom($schemes_of_work->classroom_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'strands_coverage' => 'nullable|array',
            'substrands_coverage' => 'nullable|array',
            'general_remarks' => 'nullable|string',
            'status' => 'required|in:draft,active,completed,archived',
        ]);

        $schemes_of_work->update($validated);

        return redirect()
            ->route('academics.schemes-of-work.show', $schemes_of_work)
            ->with('success', 'Scheme of work updated successfully.');
    }

    public function destroy(SchemeOfWork $schemes_of_work)
    {
        if (!$this->canAccessClassroom($schemes_of_work->classroom_id) && !Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            abort(403);
        }

        if ($schemes_of_work->isApproved()) {
            return back()->with('error', 'Cannot delete approved scheme of work.');
        }

        $schemes_of_work->delete();

        return redirect()
            ->route('academics.schemes-of-work.index')
            ->with('success', 'Scheme of work deleted successfully.');
    }

    public function approve(Request $request, SchemeOfWork $schemes_of_work)
    {
        if (!Auth::user()->hasPermissionTo('schemes_of_work.approve')) {
            abort(403);
        }

        $schemes_of_work->update([
            'approved_at' => now(),
            'approved_by' => Auth::user()->staff?->id,
            'status' => 'active',
        ]);

        return back()->with('success', 'Scheme of work approved successfully.');
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

        $classroomIds = \DB::table('classroom_subjects')
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

        return \DB::table('classroom_subjects')
            ->where('staff_id', $staff->id)
            ->where('classroom_id', $classroomId)
            ->exists();
    }
}
