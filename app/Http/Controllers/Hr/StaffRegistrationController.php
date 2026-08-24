<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\StaffCategory;
use App\Models\StaffRegistration;
use App\Services\Hr\StaffRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class StaffRegistrationController extends Controller
{
    public function __construct(protected StaffRegistrationService $registrations) {}

    public function index(Request $request): View
    {
        $query = StaffRegistration::query()->with(['reviewer', 'staff'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $registrations = $query->paginate(20)->withQueryString();
        $pendingCount = StaffRegistration::where('status', StaffRegistration::STATUS_PENDING)->count();

        return view('staff.registrations.index', [
            'registrations' => $registrations,
            'pendingCount' => $pendingCount,
            'publicUrl' => route('staff.public-register'),
        ]);
    }

    public function show(StaffRegistration $registration): View
    {
        $registration->load(['reviewer', 'staff']);

        return view('staff.registrations.show', [
            'registration' => $registration,
            'suggestedEmail' => $this->registrations->suggestWorkEmail($registration->first_name, $registration->last_name),
            'departments' => Department::orderBy('name')->get(),
            'jobTitles' => JobTitle::orderBy('name')->get(),
            'categories' => StaffCategory::orderBy('name')->get(),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    public function approve(Request $request, StaffRegistration $registration): RedirectResponse
    {
        $data = $request->validate([
            'work_email' => 'required|email|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'job_title_id' => 'nullable|exists:job_titles,id',
            'staff_category_id' => 'nullable|exists:staff_categories,id',
            'spatie_role_id' => 'nullable|integer|exists:roles,id',
        ]);

        $staff = $this->registrations->approve($registration, $data, $request->user());

        return redirect()
            ->route('staff.registrations.show', $registration)
            ->with('success', "Approved. {$staff->full_name} is {$staff->staff_id}. Login: {$staff->work_email} (password is their ID number).");
    }

    public function reject(Request $request, StaffRegistration $registration): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $this->registrations->reject($registration, $data['rejection_reason'], $request->user());

        return redirect()
            ->route('staff.registrations.show', $registration)
            ->with('success', 'Application rejected.');
    }
}
