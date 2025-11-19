<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $exams = Exam::with(['academicYear', 'term'])->orderByDesc('created_at')->get();
        $classrooms = Classroom::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();

        $selectedExam = $request->filled('exam_id') ? Exam::find($request->exam_id) : null;
        $selectedClassroom = $request->filled('classroom_id') ? Classroom::find($request->classroom_id) : null;
        $selectedSubject = $request->filled('subject_id') ? Subject::find($request->subject_id) : null;

        $analytics = null;
        if ($selectedExam) {
            $analytics = $this->calculateAnalytics($selectedExam, $selectedClassroom, $selectedSubject);
        }

        return view('academics.exam_analytics.index', compact(
            'exams', 'classrooms', 'subjects',
            'selectedExam', 'selectedClassroom', 'selectedSubject',
            'analytics'
        ));
    }

    protected function calculateAnalytics(?Exam $exam, ?Classroom $classroom = null, ?Subject $subject = null)
    {
        $query = ExamMark::where('exam_id', $exam->id)
            ->with(['student', 'subject', 'exam']);

        if ($classroom) {
            $query->whereHas('student', function($q) use ($classroom) {
                $q->where('classroom_id', $classroom->id);
            });
        }

        if ($subject) {
            $query->where('subject_id', $subject->id);
        }

        $marks = $query->get();

        if ($marks->isEmpty()) {
            return null;
        }

        // Use score_raw or score_moderated, fallback to score_raw
        $marks = $marks->map(function($mark) {
            $mark->marks_obtained = $mark->score_moderated ?? $mark->score_raw ?? 0;
            return $mark;
        });

        $totalMarks = $marks->sum('marks_obtained');
        $count = $marks->count();
        $average = $count > 0 ? $totalMarks / $count : 0;
        $maxMark = $marks->max('marks_obtained');
        $minMark = $marks->min('marks_obtained');

        // Grade distribution
        $gradeDistribution = $marks->groupBy('grade_label')->map->count();

        // Subject-wise performance
        $subjectPerformance = $marks->groupBy('subject_id')->map(function($subjectMarks) {
            return [
                'subject' => $subjectMarks->first()->subject->name ?? 'Unknown',
                'average' => $subjectMarks->avg('marks_obtained'),
                'count' => $subjectMarks->count(),
                'max' => $subjectMarks->max('marks_obtained'),
                'min' => $subjectMarks->min('marks_obtained'),
            ];
        });

        // Top performers
        $topPerformers = $marks->sortByDesc('marks_obtained')->take(10)->values();

        // Bottom performers
        $bottomPerformers = $marks->sortBy('marks_obtained')->take(10)->values();

        return [
            'total_students' => $count,
            'average' => round($average, 2),
            'max_mark' => $maxMark,
            'min_mark' => $minMark,
            'grade_distribution' => $gradeDistribution,
            'subject_performance' => $subjectPerformance,
            'top_performers' => $topPerformers,
            'bottom_performers' => $bottomPerformers,
        ];
    }

    public function classroomPerformance(Classroom $classroom, Request $request)
    {
        $exam = $request->filled('exam_id') ? Exam::find($request->exam_id) : null;
        
        if (!$exam) {
            return back()->with('error', 'Please select an exam.');
        }

        $analytics = $this->calculateAnalytics($exam, $classroom);
        
        return view('academics.exam_analytics.classroom', compact('classroom', 'exam', 'analytics'));
    }
}
