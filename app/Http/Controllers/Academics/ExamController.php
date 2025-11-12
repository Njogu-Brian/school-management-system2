<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $query = Exam::with([
            'academicYear',
            'term',
            'classroom',
            'subject',
            'creator'
        ])
        ->withCount(['marks', 'schedules']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('year_id')) {
            $query->where('academic_year_id', $request->year_id);
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        // Statistics
        $stats = [
            'total' => Exam::count(),
            'draft' => Exam::where('status', 'draft')->count(),
            'open' => Exam::where('status', 'open')->count(),
            'marking' => Exam::where('status', 'marking')->count(),
            'approved' => Exam::where('status', 'approved')->count(),
            'published' => Exam::where('status', 'published')->count(),
            'locked' => Exam::where('status', 'locked')->count(),
        ];

        $exams = $query->latest('created_at')->paginate(20)->withQueryString();

        $types = ExamType::orderBy('name')->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();
        $classrooms = Classroom::orderBy('name')->get();

        return view('academics.exams.index', compact(
            'exams',
            'stats',
            'types',
            'years',
            'terms',
            'classrooms'
        ));
    }


    public function create()
    {
        return view('academics.exams.create', [
            'years'      => AcademicYear::orderByDesc('year')->get(),
            'terms'      => Term::orderBy('name')->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
            'subjects'   => Subject::orderBy('name')->get(),
            'types'      => ExamType::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:cat,midterm,endterm,sba,mock,quiz',
            'modality'         => 'required|in:physical,online',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'classroom_id'     => 'nullable|exists:classrooms,id',
            'stream_id'        => 'nullable|exists:streams,id',
            'subject_id'       => 'nullable|exists:subjects,id',
            'starts_on'        => 'nullable|date',
            'ends_on'          => 'nullable|date|after_or_equal:starts_on',
            'max_marks'        => 'required|numeric|min:1',
            'weight'           => 'required|numeric|min:0|max:100',
            'publish_exam'     => 'boolean',
            'publish_result'   => 'boolean',
        ]);

        $exam = Exam::create($v + [
            'created_by' => Auth::id(),
            'status' => 'draft',
        ]);

        return redirect()
            ->route('academics.exams.index')
            ->with('success', 'Exam created successfully.');
    }

    public function edit(Exam $exam)
    {
        return view('academics.exams.edit', [
            'exam'       => $exam->load(['academicYear', 'term', 'classroom', 'subject']),
            'years'      => AcademicYear::orderByDesc('year')->get(),
            'terms'      => Term::orderBy('name')->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
            'subjects'   => Subject::orderBy('name')->get(),
            'types'      => ExamType::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $v = $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:cat,midterm,endterm,sba,mock,quiz',
            'modality'         => 'required|in:physical,online',
            'starts_on'        => 'nullable|date',
            'ends_on'          => 'nullable|date|after_or_equal:starts_on',
            'max_marks'        => 'required|numeric|min:1',
            'weight'           => 'required|numeric|min:0|max:100',
            'status'           => 'required|in:draft,open,marking,moderation,approved,published,locked',
            'publish_exam'     => 'boolean',
            'publish_result'   => 'boolean',
        ]);

        // Validate status transition
        if ($v['status'] !== $exam->status && !$exam->canTransitionTo($v['status'])) {
            return back()
                ->withInput()
                ->with('error', "Cannot transition from {$exam->status} to {$v['status']}.");
        }

        // Handle status-specific actions
        if ($v['status'] === 'published' && !$exam->published_at) {
            $v['published_at'] = now();
        }

        if ($v['status'] === 'locked' && !$exam->locked_at) {
            $v['locked_at'] = now();
        }

        $exam->update($v);

        return redirect()
            ->route('academics.exams.index')
            ->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam)
    {
        // Prevent deletion of locked or published exams
        if ($exam->is_locked || $exam->status === 'published') {
            return back()
                ->with('error', 'Cannot delete locked or published exams.');
        }

        // Check if exam has marks
        if ($exam->marks_count > 0) {
            return back()
                ->with('error', 'Cannot delete exam with existing marks. Archive it instead.');
        }

        $exam->delete();

        return back()->with('success', 'Exam deleted successfully.');
    }

    public function timetable(Request $request)
    {
        $query = \App\Models\Academics\ExamPaper::with([
            'exam.examGroup',
            'subject',
            'classroom',
            'exam.term',
            'exam.academicYear'
        ]);

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        $papers = $query->orderBy('exam_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('exam_date');

        $exams = Exam::latest()->get();
        $classrooms = Classroom::orderBy('name')->get();

        return view('academics.exams.timetable', compact('papers', 'exams', 'classrooms'));
    }

    public function show(Exam $exam)
    {
        $exam->load([
            'academicYear',
            'term',
            'classroom',
            'subject',
            'creator',
            'marks.student',
            'marks.subject',
            'schedules'
        ]);

        $stats = [
            'total_students' => $exam->students_count,
            'marks_entered' => $exam->marks_count,
            'marks_pending' => max(0, $exam->students_count - $exam->marks_count),
            'completion_rate' => $exam->students_count > 0 
                ? round(($exam->marks_count / $exam->students_count) * 100, 1) 
                : 0,
        ];

        return view('academics.exams.show', compact('exam', 'stats'));
    }
}
