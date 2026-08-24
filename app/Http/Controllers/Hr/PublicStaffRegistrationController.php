<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\StaffCategory;
use App\Services\Hr\StaffRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicStaffRegistrationController extends Controller
{
    public function __construct(protected StaffRegistrationService $registrations) {}

    public function show(): View
    {
        return view('staff.registrations.public_form', [
            'open' => $this->registrations->isOpen(),
            'departments' => Department::orderBy('name')->get(),
            'jobTitles' => JobTitle::orderBy('name')->get(),
            'categories' => StaffCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->registrations->isOpen()) {
            return back()->with('error', 'Staff registration is currently closed.');
        }

        if (filled($request->input('company_website'))) {
            return redirect()
                ->route('staff.public-register')
                ->with('success', 'Thank you. HR will review your details.');
        }

        $data = $request->validate([
            'photo' => 'nullable|image|max:2048',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date|before:today',
            'marital_status' => 'required|in:Single,Married,Divorced,Widowed',
            'residential_address' => 'nullable|string|max:255',
            'id_number' => 'required|string|max:32',
            'personal_email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:30',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'department_id' => 'nullable|exists:departments,id',
            'job_title_id' => 'nullable|exists:job_titles,id',
            'staff_category_id' => 'nullable|exists:staff_categories,id',
            'hire_date' => 'nullable|date',
            'employment_type' => 'nullable|in:full_time,part_time,contract,intern',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'kra_pin' => 'nullable|string|max:32',
            'nssf' => 'nullable|string|max:32',
            'nhif' => 'nullable|string|max:32',
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:64',
            'payment_method' => 'nullable|in:bank,mpesa',
            'max_lessons_per_week' => 'nullable|integer|min:0|max:80',
        ]);

        $this->registrations->submit($data, $request->ip(), $request->file('photo'));

        return redirect()
            ->route('staff.public-register')
            ->with('success', 'Your registration was submitted. HR will review it and send your staff login details.');
    }
}
