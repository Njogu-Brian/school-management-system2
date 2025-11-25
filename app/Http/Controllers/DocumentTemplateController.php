<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\Student;
use App\Models\Staff;
use App\Services\DocumentGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentTemplateController extends Controller
{
    protected DocumentGeneratorService $generator;

    public function __construct(DocumentGeneratorService $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Display a listing of templates
     */
    public function index(Request $request)
    {
        $query = DocumentTemplate::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $templates = $query->latest()->paginate(20)->withQueryString();

        return view('documents.templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        return view('documents.templates.create');
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:document_templates,slug',
            'type' => 'required|in:certificate,transcript,id_card,transfer_certificate,character_certificate,diploma,merit_certificate,participation_certificate,custom',
            'template_html' => 'required|string',
            'placeholders' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $validated['is_active'] ?? true;

        $template = DocumentTemplate::create($validated);

        return redirect()
            ->route('document-templates.show', $template)
            ->with('success', 'Template created successfully.');
    }

    /**
     * Display the specified template
     */
    public function show(DocumentTemplate $template)
    {
        return view('documents.templates.show', compact('template'));
    }

    /**
     * Show the form for editing the template
     */
    public function edit(DocumentTemplate $template)
    {
        return view('documents.templates.edit', compact('template'));
    }

    /**
     * Update the template
     */
    public function update(Request $request, DocumentTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:document_templates,slug,' . $template->id,
            'type' => 'required|in:certificate,transcript,id_card,transfer_certificate,character_certificate,diploma,merit_certificate,participation_certificate,custom',
            'template_html' => 'required|string',
            'placeholders' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['slug']) && $validated['slug'] !== $template->slug) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $template->update($validated);

        return redirect()
            ->route('document-templates.show', $template)
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Remove the template
     */
    public function destroy(DocumentTemplate $template)
    {
        $template->delete();

        return redirect()
            ->route('document-templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    /**
     * Preview template with sample data
     */
    public function preview(Request $request, DocumentTemplate $template)
    {
        $studentId = $request->get('student_id');
        $staffId = $request->get('staff_id');

        $student = $studentId ? Student::find($studentId) : null;
        $staff = $staffId ? Staff::find($staffId) : null;

        $data = $request->get('data', []);

        try {
            $generated = $this->generator->generate($template, $data, $student, $staff);
            
            return response()->download(
                storage_path('app/public/' . $generated->pdf_path),
                $generated->filename
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Preview generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate document for a student
     */
    public function generateForStudent(Request $request, DocumentTemplate $template, Student $student)
    {
        $data = $request->get('data', []);

        try {
            $generated = $this->generator->generate($template, $data, $student);

            return redirect()
                ->route('generated-documents.show', $generated)
                ->with('success', 'Document generated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Document generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate document for staff
     */
    public function generateForStaff(Request $request, DocumentTemplate $template, Staff $staff)
    {
        $data = $request->get('data', []);

        try {
            $generated = $this->generator->generate($template, $data, null, $staff);

            return redirect()
                ->route('generated-documents.show', $generated)
                ->with('success', 'Document generated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Document generation failed: ' . $e->getMessage());
        }
    }
}

