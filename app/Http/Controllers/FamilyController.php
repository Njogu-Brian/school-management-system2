<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Student;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->input('q', ''));
        $families = Family::query()
            ->when($q, function($f) use ($q){
                $f->where('guardian_name','like',"%$q%")
                  ->orWhere('phone','like',"%$q%")
                  ->orWhere('email','like',"%$q%");
            })
            ->withCount('students')
            ->orderByDesc('students_count')
            ->paginate(20)
            ->withQueryString();

        return view('families.index', compact('families','q'));
    }

    // optional create/store if you want to manually create empty families
    public function create() { return view('families.create'); }
    public function store(Request $request)
    {
        $data = $request->validate([
            'guardian_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);
        $family = Family::create($data);
        return redirect()->route('families.manage', $family)->with('success','Family created.');
    }

    public function manage(Family $family)
    {
        $family->load(['students.classroom','students.stream']);
        return view('families.manage', compact('family'));
    }

    public function update(Request $request, Family $family)
    {
        $data = $request->validate([
            'guardian_name' => 'required|string|max:255',
            'phone'         => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
        ]);
        $family->update($data);
        return back()->with('success','Family details updated.');
    }

    public function attachMember(Request $request, Family $family)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id'
        ]);
        $student = Student::withArchived()->findOrFail($data['student_id']);
        $student->update(['family_id' => $family->id]);
        return back()->with('success', 'Student attached to family.');
    }

    public function detachMember(Request $request, Family $family)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id'
        ]);
        $student = Student::withArchived()->findOrFail($data['student_id']);

        // Only detach if they belong to this family
        if ($student->family_id == $family->id) {
            $student->update(['family_id' => null]);
        }
        return back()->with('success', 'Student removed from family.');
    }
}
