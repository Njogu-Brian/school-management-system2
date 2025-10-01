<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Behavior;   // <-- add
use App\Models\Student;              // <-- add
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- add


class BehaviorController extends Controller
{
    public function index()
    {
        $records = Behavior::with('student')->latest()->paginate(30);
        return view('academics.behaviors.index', compact('records'));
    }

    public function create()
    {
        return view('academics.behaviors.create', [
            'students' => Student::orderBy('last_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'student_id' => 'required|exists:students,id',
            'category'   => 'required|string|max:50',
            'description'=> 'required|string|max:500',
            'severity'   => 'required|in:minor,moderate,major',
        ]);

        Behavior::create($v + ['recorded_by' => Auth::id()]);

        return redirect()->route('academics.behaviors.index')->with('success','Behavior record added.');
    }

    public function edit(Behavior $behavior)
    {
        return view('academics.behaviors.edit', [
            'behavior' => $behavior,
            'students' => Student::orderBy('last_name')->get(),
        ]);
    }

    public function update(Request $request, Behavior $behavior)
    {
        $v = $request->validate([
            'student_id' => 'required|exists:students,id',
            'category'   => 'required|string|max:50',
            'description'=> 'required|string|max:500',
            'severity'   => 'required|in:minor,moderate,major',
        ]);

        $behavior->update($v);

        return redirect()->route('academics.behaviors.index')->with('success','Behavior updated.');
    }

    public function destroy(Behavior $behavior)
    {
        $behavior->delete();
        return back()->with('success','Behavior deleted.');
    }
}
