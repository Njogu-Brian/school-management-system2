<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
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
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|before:today',
            'marital_status' => 'required|in:Single,Married,Divorced,Widowed',
            'id_number' => 'required|string|max:32',
            'personal_email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:30',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'kra_pin' => 'nullable|string|max:32',
            'nssf' => 'nullable|string|max:32',
            'nhif' => 'nullable|string|max:32',
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:64',
        ]);

        $this->registrations->submit($data, $request->ip());

        return redirect()
            ->route('staff.public-register')
            ->with('success', 'Your registration was submitted. HR will review it and send your staff login details.');
    }
}
