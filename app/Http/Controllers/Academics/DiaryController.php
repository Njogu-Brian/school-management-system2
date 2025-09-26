<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Diary;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiaryController extends Controller
{
    public function index()
    {
        $diaries = Diary::with(['teacher'])->latest()->paginate(20);
        return view('academics.diaries.index', compact('diaries'));
    }

    public function create()
    {
        $classrooms = Classroom::all();
        return view('academics.diaries.create', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'classroom_id'=>'required|exists:classrooms,id',
            'week_start'=>'required|date',
            'entries'=>'required|array',
        ]);

        Diary::create([
            'classroom_id'=>$request->classroom_id,
            'stream_id'=>$request->stream_id,
            'teacher_id'=>Auth::id(),
            'week_start'=>$request->week_start,
            'entries'=>$request->entries,
        ]);

        return redirect()->route('academics.diaries.index')->with('success','Diary created.');
    }

    public function edit(Diary $diary)
    {
        $classrooms = Classroom::all();
        return view('academics.diaries.edit', compact('diary','classrooms'));
    }

    public function update(Request $request, Diary $diary)
    {
        $request->validate([
            'week_start'=>'required|date',
            'entries'=>'required|array',
        ]);

        $diary->update($request->all());

        return redirect()->route('academics.diaries.index')->with('success','Diary updated.');
    }

    public function destroy(Diary $diary)
    {
        $diary->delete();
        return redirect()->route('academics.diaries.index')->with('success','Diary deleted.');
    }
}
