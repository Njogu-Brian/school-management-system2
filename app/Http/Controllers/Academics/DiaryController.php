<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Diary;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiaryController extends Controller
{
    public function index()
    {
        $diaries = Diary::with(['teacher','classroom'])->latest()->paginate(20);
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
            'activities'=>'required|string',
            'announcements'=>'nullable|string',
        ]);

        Diary::create([
            'classroom_id' => $request->classroom_id,
            'stream_id' => $request->stream_id,
            'teacher_id' => Auth::id(),
            'week_start' => $request->week_start,
            'entries' => [
                'activities' => $request->activities,
                'announcements' => $request->announcements,
            ],
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
            'activities'=>'required|string',
            'announcements'=>'nullable|string',
        ]);

        $diary->update([
            'week_start' => $request->week_start,
            'entries' => [
                'activities' => $request->activities,
                'announcements' => $request->announcements,
            ],
        ]);

        return redirect()->route('academics.diaries.index')->with('success','Diary updated.');
    }

    public function destroy(Diary $diary)
    {
        $diary->delete();
        return redirect()->route('academics.diaries.index')->with('success','Diary deleted.');
    }

    public function show(Diary $diary)
    {
        return view('academics.diaries.show', compact('diary'));
    }
}
