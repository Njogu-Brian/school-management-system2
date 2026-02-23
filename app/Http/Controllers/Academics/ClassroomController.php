<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:classrooms.view')->only(['index', 'show']);
        $this->middleware('permission:classrooms.create')->only(['create', 'store']);
        $this->middleware('permission:classrooms.edit')->only(['edit', 'update']);
        $this->middleware('permission:classrooms.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Classroom::with(['teachers.staff', 'streams', 'nextClass', 'previousClasses'])
            ->withCount('students');

        // Teachers can only see their assigned classes
        if (Auth::user()->hasRole('Teacher')) {
            $user = Auth::user();
            // Use the helper method that includes all assignment types
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $query->whereIn('id', $assignedClassroomIds);
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $classrooms = $query->orderBy('name')->get();
        return view('academics.classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->get();
        $classrooms = Classroom::orderBy('name')->get();
        
        // Get classes that are already selected as next_class_id by another class
        $usedAsNextClass = Classroom::whereNotNull('next_class_id')
            ->pluck('next_class_id')
            ->unique()
            ->toArray();
        
        return view('academics.classrooms.create', compact('teachers', 'classrooms', 'usedAsNextClass'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name',
            'next_class_id' => 'nullable|exists:classrooms,id',
            'is_beginner' => 'nullable|boolean',
            'is_alumni' => 'nullable|boolean',
            'campus' => 'nullable|in:lower,upper',
            'level_type' => 'nullable|in:preschool,lower_primary,upper_primary,junior_high',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $classroom = Classroom::create([
            'name' => $request->name,
            'next_class_id' => $request->next_class_id,
            'is_beginner' => $request->boolean('is_beginner'),
            'is_alumni' => $request->boolean('is_alumni'),
            'campus' => $request->campus,
            'level_type' => $request->level_type,
        ]);

        if ($request->has('teacher_ids')) {
            $classroom->teachers()->sync($request->teacher_ids);
        }

        return redirect()->route('academics.classrooms.index')
            ->with('success', 'Classroom added successfully.');
    }

    public function edit($id)
    {
        $classroom = Classroom::with('teachers.staff')->findOrFail($id);
        $teachers = User::with('staff')->whereHas('roles', fn($q) => $q->where('name', 'teacher'))->get();
        $assignedTeachers = $classroom->teachers->pluck('id')->toArray();
        $classrooms = Classroom::where('id', '!=', $id)->orderBy('name')->get();
        
        // Get classes that are already selected as next_class_id by another class (excluding current class's next_class_id)
        $usedAsNextClass = Classroom::whereNotNull('next_class_id')
            ->where('id', '!=', $id)
            ->where('next_class_id', '!=', $classroom->next_class_id)
            ->pluck('next_class_id')
            ->unique()
            ->toArray();

        return view('academics.classrooms.edit', compact('classroom', 'teachers', 'assignedTeachers', 'classrooms', 'usedAsNextClass'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name,' . $id,
            'next_class_id' => 'nullable|exists:classrooms,id|different:id',
            'is_beginner' => 'nullable|boolean',
            'is_alumni' => 'nullable|boolean',
            'campus' => 'nullable|in:lower,upper',
            'level_type' => 'nullable|in:preschool,lower_primary,upper_primary,junior_high',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $classroom = Classroom::findOrFail($id);
        $classroom->update([
            'name' => $request->name,
            'next_class_id' => $request->next_class_id,
            'is_beginner' => $request->boolean('is_beginner'),
            'is_alumni' => $request->boolean('is_alumni'),
            'campus' => $request->campus,
            'level_type' => $request->level_type,
        ]);

        $classroom->teachers()->sync($request->teacher_ids ?? []);

        return redirect()->route('academics.classrooms.index')
            ->with('success', 'Classroom updated successfully.');
    }

    public function destroy($id)
    {
        $classroom = Classroom::findOrFail($id);

        // Check if classroom has students
        if ($classroom->students()->count() > 0) {
            return back()
                ->with('error', 'Cannot delete classroom with existing students. Transfer students first.');
        }

        // Check if classroom has exam marks
        $hasExamMarks = \Illuminate\Support\Facades\DB::table('exam_marks')
            ->join('exams', 'exam_marks.exam_id', '=', 'exams.id')
            ->where('exams.classroom_id', $classroom->id)
            ->exists();
        if ($hasExamMarks) {
            return back()
                ->with('error', 'Cannot delete classroom with existing exam marks. Archive it instead.');
        }

        // Check if classroom has schemes of work
        $hasSchemesOfWork = \Illuminate\Support\Facades\DB::table('schemes_of_work')
            ->where('classroom_id', $classroom->id)
            ->exists();
        if ($hasSchemesOfWork) {
            return back()
                ->with('error', 'Cannot delete classroom with existing schemes of work. Remove schemes first.');
        }

        // Check if classroom has lesson plans
        $hasLessonPlans = \Illuminate\Support\Facades\DB::table('lesson_plans')
            ->where('classroom_id', $classroom->id)
            ->exists();
        if ($hasLessonPlans) {
            return back()
                ->with('error', 'Cannot delete classroom with existing lesson plans. Remove lesson plans first.');
        }

        // Check if classroom has homework
        $hasHomework = \Illuminate\Support\Facades\DB::table('homeworks')
            ->where('classroom_id', $classroom->id)
            ->exists();
        if ($hasHomework) {
            return back()
                ->with('error', 'Cannot delete classroom with existing homework. Remove homework first.');
        }

        // Check if classroom has report cards
        $hasReportCards = \Illuminate\Support\Facades\DB::table('report_cards')
            ->where('classroom_id', $classroom->id)
            ->exists();
        if ($hasReportCards) {
            return back()
                ->with('error', 'Cannot delete classroom with existing report cards. Archive them first.');
        }

        $classroom->teachers()->detach();
        $classroom->delete();

        return redirect()->route('academics.classrooms.index')
            ->with('success', 'Classroom deleted successfully.');
    }
}
