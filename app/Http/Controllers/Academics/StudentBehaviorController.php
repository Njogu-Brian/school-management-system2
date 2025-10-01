<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\StudentBehavior;
use App\Models\Academics\Behavior;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentBehaviorController extends Controller
{
    public function index()
    {
        $records = StudentBehavior::with(['student','behaviour','teacher','term','academicYear'])
            ->latest()->paginate(30);

        return view('academics.student_behaviors.index', compact('records'));
    }

    public function create()
    {
        return view('academics.student_behaviors.create', [
            'students' => Student::orderBy('last_name')->get(),
            'behaviors' => Behavior::orderBy('name')->get(),
            'years' => AcademicYear::orderByDesc('year')->get(),
            'terms' => Term::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'behaviour_id' => 'required|exists:behaviors,id',
            'term_id' => 'required|exists:terms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'notes' => 'nullable|string|max:500',
        ]);

        StudentBehavior::create($data + ['recorded_by' => Auth::id()]);

        return redirect()->route('academics.student-behaviors.index')
            ->with('success', 'Behavior record saved.');
    }

    public function destroy(StudentBehavior $student_behavior)
    {
        $student_behavior->delete();
        return back()->with('success','Behavior record deleted.');
    }
}
