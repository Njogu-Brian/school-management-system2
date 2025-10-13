<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\StudentBehaviour;
use App\Models\Academics\Behaviour;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentBehaviourController extends Controller
{
    public function index()
    {
        $records = Studentbehaviour::with(['student','behaviour','teacher','term','academicYear'])
            ->latest()->paginate(30);

        return view('academics.student_behaviours.index', compact('records'));
    }

    public function create()
    {
        return view('academics.student_behaviours.create', [
            'students' => Student::orderBy('last_name')->get(),
            'behaviours' => behaviour::orderBy('name')->get(),
            'years' => AcademicYear::orderByDesc('year')->get(),
            'terms' => Term::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'behaviour_id' => 'required|exists:behaviours,id',
            'term_id' => 'required|exists:terms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'notes' => 'nullable|string|max:500',
        ]);

        Studentbehaviour::create($data + ['recorded_by' => Auth::id()]);

        return redirect()->route('academics.student-behaviours.index')
            ->with('success', 'behaviour record saved.');
    }

    public function destroy(Studentbehaviour $student_behaviour)
    {
        $student_behaviour->delete();
        return back()->with('success','behaviour record deleted.');
    }
}
