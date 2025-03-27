<?php

namespace App\Http\Controllers;

use App\Models\OnlineAdmission;
use App\Models\Student;
use Illuminate\Http\Request;

class OnlineAdmissionController extends Controller
{
    public function index()
    {
        $admissions = OnlineAdmission::all();
        return view('online_admissions.index', compact('admissions'));
    }

    public function approve($id)
    {
        $admission = OnlineAdmission::findOrFail($id);

        // Create a student from the admission data
        Student::create([
            'first_name' => $admission->first_name,
            'middle_name' => $admission->middle_name,
            'last_name' => $admission->last_name,
            'dob' => $admission->dob,
            'gender' => $admission->gender,
            'parent_id' => null, // Assuming parent details will be added separately
            'admission_number' => Student::max('admission_number') + 1,
        ]);

        $admission->update(['enrolled' => true]);

        return redirect()->back()->with('success', 'Student approved and enrolled successfully.');
    }

    public function reject($id)
    {
        $admission = OnlineAdmission::findOrFail($id);
        $admission->delete();

        return redirect()->back()->with('success', 'Admission application rejected and deleted.');
    }
}
