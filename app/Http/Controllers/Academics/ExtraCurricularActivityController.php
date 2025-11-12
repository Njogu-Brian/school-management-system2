<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExtraCurricularActivity;
use App\Models\Academics\Classroom;
use App\Models\Staff;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExtraCurricularActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:extra_curricular.view')->only(['index', 'show']);
        $this->middleware('permission:extra_curricular.create')->only(['create', 'store']);
        $this->middleware('permission:extra_curricular.edit')->only(['edit', 'update']);
        $this->middleware('permission:extra_curricular.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = ExtraCurricularActivity::with(['academicYear', 'term']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $activities = $query->latest()->paginate(20)->withQueryString();

        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('academics.extra_curricular_activities.index', compact('activities', 'years', 'terms'));
    }

    public function create()
    {
        $classrooms = Classroom::orderBy('name')->get();
        $staff = Staff::whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher']))->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('academics.extra_curricular_activities.create', compact('classrooms', 'staff', 'years', 'terms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:club,sport,event,parade,other',
            'day' => 'nullable|string|max:20',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'period' => 'nullable|integer|min:1|max:10',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'exists:staff,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'repeat_weekly' => 'boolean',
        ]);

        ExtraCurricularActivity::create($validated);

        return redirect()
            ->route('academics.extra-curricular-activities.index')
            ->with('success', 'Extra-curricular activity created successfully.');
    }

    public function show(ExtraCurricularActivity $extra_curricular_activity)
    {
        $extra_curricular_activity->load(['academicYear', 'term']);
        return view('academics.extra_curricular_activities.show', compact('extra_curricular_activity'));
    }

    public function edit(ExtraCurricularActivity $extra_curricular_activity)
    {
        $classrooms = Classroom::orderBy('name')->get();
        $staff = Staff::whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher']))->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('academics.extra_curricular_activities.edit', compact('extra_curricular_activity', 'classrooms', 'staff', 'years', 'terms'));
    }

    public function update(Request $request, ExtraCurricularActivity $extra_curricular_activity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:club,sport,event,parade,other',
            'day' => 'nullable|string|max:20',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'period' => 'nullable|integer|min:1|max:10',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'exists:staff,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'repeat_weekly' => 'boolean',
        ]);

        $extra_curricular_activity->update($validated);

        return redirect()
            ->route('academics.extra-curricular-activities.index')
            ->with('success', 'Extra-curricular activity updated successfully.');
    }

    public function destroy(ExtraCurricularActivity $extra_curricular_activity)
    {
        $extra_curricular_activity->delete();

        return redirect()
            ->route('academics.extra-curricular-activities.index')
            ->with('success', 'Extra-curricular activity deleted successfully.');
    }
}
