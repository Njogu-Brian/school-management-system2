<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Admissions\AdmissionApplication;
use App\Models\StudentCategory;
use App\Policies\Website\ManagesWebsiteCms;
use App\Services\Admissions\AdmissionApplicationEnrollmentService;
use App\Services\Admissions\AdmissionApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmissionApplicationController extends Controller
{
    use ManagesWebsiteCms;

    public function __construct(
        private AdmissionApplicationService $applications,
        private AdmissionApplicationEnrollmentService $enrollment,
    ) {
        $this->middleware(function ($request, $next) {
            abort_unless($this->canManageWebsite($request->user()), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $query = AdmissionApplication::query()->with(['assignedStaff', 'student'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->paginate(25);

        return view('website.admissions.index', compact('applications'));
    }

    public function show(AdmissionApplication $application): View
    {
        $application->load(['documents', 'assignedStaff', 'student', 'reviewer']);
        $classrooms = Classroom::query()->where('is_alumni', false)->orderBy('name')->get();
        $categories = StudentCategory::query()->orderBy('name')->get();

        return view('website.admissions.show', compact('application', 'classrooms', 'categories'));
    }

    public function updateStatus(Request $request, AdmissionApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', AdmissionApplication::statuses()),
            'admission_notes' => 'nullable|string|max:5000',
            'assessment_date' => 'nullable|date',
            'assigned_staff' => 'nullable|exists:users,id',
        ]);

        $this->applications->updateStatus(
            $application,
            $validated['status'],
            $validated['assigned_staff'] ?? null,
            $validated['admission_notes'] ?? null
        );

        if (! empty($validated['assessment_date'])) {
            $application->update(['assessment_date' => $validated['assessment_date']]);
        }

        return back()->with('success', 'Application status updated.');
    }

    public function enroll(Request $request, AdmissionApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'category_id' => 'required|exists:student_categories,id',
            'residential_area' => 'required|string|max:255',
            'transport_fee_amount' => 'nullable|numeric|min:0',
            'enrollment_year' => 'nullable|integer',
            'enrollment_term' => 'nullable|integer|min:1|max:3',
        ]);

        $student = $this->enrollment->enroll($application, $validated, auth()->id());

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Applicant enrolled as student '.$student->admission_number);
    }

    public function verifyDocument(Request $request, AdmissionApplication $application, int $document): RedirectResponse
    {
        $doc = $application->documents()->findOrFail($document);
        $doc->update(['verified' => $request->boolean('verified', true)]);

        return back()->with('success', 'Document verification updated.');
    }
}
