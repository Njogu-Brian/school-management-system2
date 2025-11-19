<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\RequirementTemplate;
use App\Models\RequirementType;
use App\Models\ActivityLog;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;

class RequirementTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = RequirementTemplate::with(['requirementType', 'classroom', 'academicYear', 'term']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        $templates = $query->orderBy('classroom_id')->orderBy('requirement_type_id')->paginate(30);
        $requirementTypes = RequirementType::active()->orderBy('name')->get();
        $classrooms = Classroom::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('inventory.requirement-templates.index', compact(
            'templates', 'requirementTypes', 'classrooms', 'academicYears', 'terms'
        ));
    }

    public function create(Request $request)
    {
        $requirementTypes = RequirementType::active()->orderBy('name')->get();
        $classrooms = Classroom::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('inventory.requirement-templates.create', compact(
            'requirementTypes', 'classrooms', 'academicYears', 'terms'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requirement_type_id' => 'required|exists:requirement_types,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'brand' => 'nullable|string|max:255',
            'quantity_per_student' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'student_type' => 'required|in:new,existing,both',
            'leave_with_teacher' => 'boolean',
            'is_verification_only' => 'boolean',
            'notes' => 'nullable|string',
            'duplicate_to_classes' => 'nullable|array',
            'duplicate_to_classes.*' => 'exists:classrooms,id',
        ]);

        $template = RequirementTemplate::create($validated);

        // Duplicate to other classes if specified
        if ($request->filled('duplicate_to_classes')) {
            foreach ($request->duplicate_to_classes as $classroomId) {
                RequirementTemplate::create(array_merge($validated, [
                    'classroom_id' => $classroomId,
                ]));
            }
        }

        ActivityLog::log('create', $template, "Created requirement template for {$template->requirementType->name}");

        return redirect()->route('inventory.requirement-templates.index')
            ->with('success', 'Requirement template created successfully.');
    }

    public function edit(RequirementTemplate $template)
    {
        $requirementTypes = RequirementType::active()->orderBy('name')->get();
        $classrooms = Classroom::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('inventory.requirement-templates.edit', compact(
            'template', 'requirementTypes', 'classrooms', 'academicYears', 'terms'
        ));
    }

    public function update(Request $request, RequirementTemplate $template)
    {
        $oldValues = $template->toArray();
        
        $validated = $request->validate([
            'requirement_type_id' => 'required|exists:requirement_types,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'brand' => 'nullable|string|max:255',
            'quantity_per_student' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'student_type' => 'required|in:new,existing,both',
            'leave_with_teacher' => 'boolean',
            'is_verification_only' => 'boolean',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        ActivityLog::log('update', $template, "Updated requirement template", $oldValues, $template->toArray());

        return redirect()->route('inventory.requirement-templates.index')
            ->with('success', 'Requirement template updated successfully.');
    }

    public function destroy(RequirementTemplate $template)
    {
        $name = $template->requirementType->name ?? 'Template';
        $template->delete();

        ActivityLog::log('delete', null, "Deleted requirement template: {$name}");

        return back()->with('success', 'Requirement template deleted successfully.');
    }
}
