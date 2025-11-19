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
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'repeat_weekly' => 'boolean',
            'fee_amount' => 'nullable|numeric|min:0',
            'auto_invoice' => 'boolean',
        ]);

        $activity = ExtraCurricularActivity::create($validated);

        // Sync finance integration if fee is set
        if ($request->filled('fee_amount') && $request->fee_amount > 0) {
            $activity->syncFinanceIntegration();
        }

        return redirect()
            ->route('academics.extra-curricular-activities.index')
            ->with('success', 'Activity created successfully.');
    }

    public function show(ExtraCurricularActivity $extra_curricular_activity)
    {
        $extra_curricular_activity->load(['academicYear', 'term', 'votehead']);
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
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'repeat_weekly' => 'boolean',
            'fee_amount' => 'nullable|numeric|min:0',
            'auto_invoice' => 'boolean',
        ]);

        $extra_curricular_activity->update($validated);

        // Sync finance integration if fee is set
        if ($request->filled('fee_amount') && $request->fee_amount > 0) {
            $extra_curricular_activity->syncFinanceIntegration();
        }

        // Invoice students if auto_invoice is enabled
        if ($request->filled('auto_invoice') && $request->auto_invoice && $request->filled('student_ids')) {
            $extra_curricular_activity->invoiceStudents();
        }

        return redirect()
            ->route('academics.extra-curricular-activities.index')
            ->with('success', 'Activity updated successfully.');
    }

    /**
     * Assign students to activity and invoice them
     */
    public function assignStudents(Request $request, ExtraCurricularActivity $extra_curricular_activity)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'invoice' => 'boolean',
        ]);

        $extra_curricular_activity->update([
            'student_ids' => array_merge($extra_curricular_activity->student_ids ?? [], $validated['student_ids'])
        ]);

        if ($request->filled('invoice') && $request->invoice) {
            $extra_curricular_activity->invoiceStudents();
        }

        return redirect()
            ->route('academics.extra-curricular-activities.show', $extra_curricular_activity)
            ->with('success', 'Students assigned successfully.');
    }

    public function destroy(ExtraCurricularActivity $extra_curricular_activity)
    {
        $extra_curricular_activity->delete();

        return redirect()
            ->route('academics.extra-curricular-activities.index')
            ->with('success', 'Extra-curricular activity deleted successfully.');
    }
}
