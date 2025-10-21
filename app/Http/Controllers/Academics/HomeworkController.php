<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Homework;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\Student;
use App\Models\Academics\Diary;
use App\Models\Academics\DiaryMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomeworkController extends Controller
{
    public function index()
    {
        $homeworks = Homework::with(['classroom','stream','subject','teacher'])
            ->latest()->paginate(20);

        return view('academics.homework.index', compact('homeworks'));
    }

    public function create()
    {
        $user = Auth::user();

        // Admins see all, Teachers only their classes/subjects
        if ($user->hasRole(['Super Admin','Admin'])) {
            $classrooms = Classroom::orderBy('first_name')->get();
            $subjects   = Subject::orderBy('first_name')->get();
        } else {
            $teacherId  = $user->staff?->id;
            $classrooms = Classroom::where('teacher_id',$teacherId)->get();
            $subjects   = Subject::where('teacher_id',$teacherId)->get();
        }

        $students = Student::orderBy('first_name')->get();

        return view('academics.homework.create', compact('classrooms','subjects','students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'instructions'  => 'nullable|string',
            'due_date'      => 'required|date',
            'target_scope'  => 'required|in:class,stream,students,school',
            'classroom_id'  => 'nullable|exists:classrooms,id',
            'stream_id'     => 'nullable|exists:streams,id',
            'subject_id'    => 'nullable|exists:subjects,id',
            'student_ids'   => 'array',
            'student_ids.*' => 'exists:students,id',
            'attachment'    => 'nullable|file|max:10240',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($request,$user) {
            $path = null;
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('homeworks','public');
            }

            $homework = Homework::create([
                'assigned_by'   => $user->id,
                'teacher_id'    => $user->hasRole('Teacher') ? $user->staff?->id : null,
                'classroom_id'  => $request->classroom_id,
                'stream_id'     => $request->stream_id,
                'subject_id'    => $request->subject_id,
                'title'         => $request->title,
                'instructions'  => $request->instructions,
                'file_path'     => $path,
                'due_date'      => $request->due_date,
                'target_scope'  => $request->target_scope,
            ]);

            if ($request->target_scope === 'students' && $request->filled('student_ids')) {
                $homework->students()->sync($request->student_ids);
            }

            // Create linked diary entry
            $diary = Diary::create([
                'classroom_id' => $homework->classroom_id,
                'stream_id'    => $homework->stream_id,
                'teacher_id'   => $homework->teacher_id ?? $user->staff?->id,
                'week_start'   => now(),
                'entries'      => ['homework_id'=>$homework->id],
                'homework_id'  => $homework->id,
                'is_homework'  => true,
            ]);

            DiaryMessage::create([
                'diary_id'       => $diary->id,
                'user_id'        => $user->id,
                'message_type'   => $homework->file_path ? 'file' : 'text',
                'body'           => "**{$homework->title}**\nDue: ".$homework->due_date->format('d M Y')."\n".$homework->instructions,
                'attachment_path'=> $homework->file_path,
            ]);
        });

        return redirect()->route('academics.homework.index')->with('success','Homework assigned successfully.');
    }

    public function show(Homework $homework)
    {
        $homework->load(['classroom','stream','subject','students','diary.messages.sender']);
        return view('academics.homework.show', compact('homework'));
    }

    public function destroy(Homework $homework)
    {
        if ($homework->file_path) Storage::disk('public')->delete($homework->file_path);
        $homework->delete();
        return redirect()->route('academics.homework.index')->with('success','Homework deleted.');
    }
}
