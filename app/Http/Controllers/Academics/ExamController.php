<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ExamsImport;

class ExamController extends Controller
{
    private array $types = [
        'cat'     => 'Continuous Assessment Test (CAT)',
        'rat'     => 'Random Assessment Test (RAT)',
        'midterm' => 'Mid Term Exam',
        'endterm' => 'End Term Exam',
        'sba'     => 'School Based Assessment',
        'mock'    => 'Mock Exam',
        'quiz'    => 'Quiz',
    ];

    private array $modalities = [
        'physical' => 'Physical',
        'online'   => 'Online',
    ];

    public function index()
    {
        $exams = Exam::with(['classrooms', 'subjects', 'term', 'academicYear'])
            ->latest('starts_on')->paginate(20);

        return view('academics.exams.index', compact('exams'));
    }

    public function create()
    {
        $subjects   = Subject::all();
        $classrooms = Classroom::all();
        $years      = AcademicYear::orderBy('year', 'desc')->get();
        $terms      = Term::orderBy('id', 'desc')->get();

        $types      = $this->types;
        $modalities = $this->modalities;

        return view('academics.exams.create', compact(
            'subjects','classrooms','years','terms','types','modalities'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:cat,rat,midterm,endterm,sba,mock,quiz',
            'modality'         => 'required|in:physical,online',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'classrooms'       => 'required|array',
            'subjects'         => 'required|array',
            'starts_on'        => 'nullable|date',
            'ends_on'          => 'nullable|date|after_or_equal:starts_on',
            'max_marks'        => 'required|numeric|min:1',
            'weight'           => 'required|numeric|min:0|max:100',
        ]);

        $exam = Exam::create(array_merge(
            $request->only(['name','type','modality','academic_year_id','term_id','starts_on','ends_on','max_marks','weight']),
            ['created_by' => Auth::id()]
        ));

        foreach ($request->classrooms as $classId) {
            foreach ($request->subjects as $subId) {
                $exam->classrooms()->attach($classId, ['subject_id' => $subId]);
            }
        }

        return redirect()->route('academics.exams.index')->with('success','Exam created successfully.');
    }

    public function edit(Exam $exam)
    {
        $subjects   = Subject::all();
        $classrooms = Classroom::all();
        $years      = AcademicYear::orderBy('year', 'desc')->get();
        $terms      = Term::orderBy('id', 'desc')->get();

        $types      = $this->types;
        $modalities = $this->modalities;

        $selectedClassrooms = $exam->classrooms->pluck('id')->toArray();
        $selectedSubjects   = $exam->classrooms->pluck('pivot.subject_id')->toArray();

        return view('academics.exams.edit', compact(
            'exam','subjects','classrooms','years','terms','types','modalities',
            'selectedClassrooms','selectedSubjects'
        ));
    }

    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:cat,rat,midterm,endterm,sba,mock,quiz',
            'modality'         => 'required|in:physical,online',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'classrooms'       => 'required|array',
            'subjects'         => 'required|array',
            'starts_on'        => 'nullable|date',
            'ends_on'          => 'nullable|date|after_or_equal:starts_on',
            'max_marks'        => 'required|numeric|min:1',
            'weight'           => 'required|numeric|min:0|max:100',
        ]);

        $exam->update($request->only(['name','type','modality','academic_year_id','term_id','starts_on','ends_on','max_marks','weight']));

        $exam->classrooms()->detach();
        foreach ($request->classrooms as $classId) {
            foreach ($request->subjects as $subId) {
                $exam->classrooms()->attach($classId, ['subject_id' => $subId]);
            }
        }

        return redirect()->route('academics.exams.index')->with('success','Exam updated successfully.');
    }

    public function destroy(Exam $exam)
    {
        $exam->classrooms()->detach();
        $exam->delete();
        return redirect()->route('academics.exams.index')->with('success','Exam deleted.');
    }

    public function timetable()
    {
        $exams = Exam::with(['classrooms','subjects','term'])
            ->whereNotNull('starts_on')
            ->orderBy('starts_on')
            ->get()
            ->groupBy(fn($e) => optional($e->starts_on)->format('Y-m-d'));

        return view('academics.exams.timetable', compact('exams'));
    }

    public function importForm()
    {
        return view('academics.exams.import');
    }

    public function importStore(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv']);
        Excel::import(new ExamsImport, $request->file('file'));
        return redirect()->route('academics.exams.index')->with('success','Exams imported successfully.');
    }

    public function template()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="exams_template.csv"',
        ];
        $csv = implode(",", [
            'name','type(cat|rat|midterm|endterm|sba|mock|quiz)',
            'modality(physical|online)','academic_year','term','classrooms','subjects',
            'starts_on(YYYY-MM-DD HH:MM)','ends_on(YYYY-MM-DD HH:MM)','max_marks','weight'
        ]);

        return response($csv . "\n", 200, $headers);
    }
}
