<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\RequirementTemplate;
use App\Models\RequirementTemplateAssignment;
use App\Models\RequirementType;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;

class RequirementTemplateAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = RequirementTemplateAssignment::with([
            'template.requirementType',
            'classroom',
            'academicYear',
            'term',
        ]);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->filled('requirement_type_id')) {
            $query->whereHas('template', fn ($q) => $q->where('requirement_type_id', $request->requirement_type_id));
        }
        if ($request->filled('student_type')) {
            $query->where('student_type', $request->student_type);
        }
        if ($request->filled('category')) {
            $query->whereHas('template.requirementType', fn ($q) => $q->where('category', $request->category));
        }

        $assignments = $query
            ->orderByDesc('is_active')
            ->orderBy('classroom_id')
            ->paginate(30)
            ->withQueryString();

        $templates = RequirementTemplate::with('requirementType')->orderByDesc('id')->get();
        $requirementTypes = RequirementType::active()->orderBy('name')->get();
        $categories = RequirementType::presetCategories();
        $classrooms = Classroom::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('inventory.requirement-template-assignments.index', compact(
            'assignments', 'templates', 'requirementTypes', 'categories', 'classrooms', 'academicYears', 'terms'
        ));
    }

    public function create()
    {
        $templates = RequirementTemplate::with('requirementType')->orderByDesc('id')->get();
        $classrooms = Classroom::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('inventory.requirement-template-assignments.create', compact(
            'templates', 'classrooms', 'academicYears', 'terms'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requirement_template_id' => 'required|exists:requirement_templates,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'student_type' => 'required|in:new,existing,both',
            'brand' => 'nullable|string|max:255',
            'quantity_per_student' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'notes' => 'nullable|string',
            'leave_with_teacher' => 'boolean',
            'is_verification_only' => 'boolean',
            'is_active' => 'boolean',
        ]);

        RequirementTemplateAssignment::create($validated);

        return redirect()->route('inventory.requirement-template-assignments.index')
            ->with('success', 'Requirement assignment created successfully.');
    }

    public function edit(RequirementTemplateAssignment $requirement_template_assignment)
    {
        $assignment = $requirement_template_assignment->load(['template.requirementType']);
        $templates = RequirementTemplate::with('requirementType')->orderByDesc('id')->get();
        $classrooms = Classroom::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('inventory.requirement-template-assignments.edit', compact(
            'assignment', 'templates', 'classrooms', 'academicYears', 'terms'
        ));
    }

    public function update(Request $request, RequirementTemplateAssignment $requirement_template_assignment)
    {
        $validated = $request->validate([
            'requirement_template_id' => 'required|exists:requirement_templates,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'student_type' => 'required|in:new,existing,both',
            'brand' => 'nullable|string|max:255',
            'quantity_per_student' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'notes' => 'nullable|string',
            'leave_with_teacher' => 'boolean',
            'is_verification_only' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $requirement_template_assignment->update($validated);

        return redirect()->route('inventory.requirement-template-assignments.index')
            ->with('success', 'Requirement assignment updated successfully.');
    }

    public function destroy(RequirementTemplateAssignment $requirement_template_assignment)
    {
        $requirement_template_assignment->delete();

        return back()->with('success', 'Requirement assignment deleted successfully.');
    }
}

