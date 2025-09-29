<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Homework;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeworkController extends Controller
{
    public function index()
    {
        $homeworks = Homework::with(['subject','teacher'])->latest()->paginate(20);
        return view('academics.homework.index', compact('homeworks'));
    }

    public function create()
    {
        $subjects = Subject::all();
        $classrooms = Classroom::all();
        return view('academics.homework.create', compact('subjects','classrooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'classroom_id'=>'required|exists:classrooms,id',
            'subject_id'=>'required|exists:subjects,id',
            'title'=>'required|string|max:255',
            'instructions'=>'required|string',
            'due_date'=>'required|date',
        ]);

        Homework::create(array_merge(
            $request->all(),
            ['teacher_id'=>Auth::id()]
        ));

        return redirect()->route('academics.homework.index')->with('success','Homework created.');
    }

    public function edit(Homework $homework)
    {
        $subjects = Subject::all();
        $classrooms = Classroom::all();
        return view('academics.homework.edit', compact('homework','subjects','classrooms'));
    }

    public function update(Request $request, Homework $homework)
    {
        $request->validate([
            'title'=>'required|string|max:255',
            'instructions'=>'required|string',
            'due_date'=>'required|date',
        ]);

        $homework->update($request->all());

        return redirect()->route('academics.homework.index')->with('success','Homework updated.');
    }

    public function destroy(Homework $homework)
    {
        $homework->delete();
        return redirect()->route('academics.homework.index')->with('success','Homework deleted.');
    }
}
