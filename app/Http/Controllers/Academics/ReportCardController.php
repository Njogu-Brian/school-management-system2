<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportCardSkill;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Exam;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\TermAssessmentService;
use App\Services\ReportCardBatchService;
use App\Models\Academics\Stream;

class ReportCardController extends Controller
{
    public function index()
    {
        $report_cards = ReportCard::with(['student','publisher','academicYear','term','classroom'])
            ->latest()
            ->paginate(20);

        return view('academics.report_cards.index', compact('report_cards'));
    }

    public function show(ReportCard $report_card)
    {
        $report_card->load([
            'student.classroom','student.stream',
            'academicYear','term','classroom','stream',
            'skills'
        ]);

        $dto = ReportCardBatchService::build($report_card->id);
        $isPdf = false;

        return view('academics.report_cards.show', compact('report_card','dto','isPdf'));
}
    public function create()
    {
        return view('academics.report_cards.create', [
            'students' => Student::with('classroom','stream')->orderBy('last_name')->get(),
            'years'    => AcademicYear::orderByDesc('year')->get(),
            'terms'    => Term::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'student_id'       => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'classroom_id'     => 'required|exists:classrooms,id',
            'stream_id'        => 'nullable|exists:streams,id',
        ]);

        $report_card = ReportCard::firstOrCreate(
            [
                'student_id'       => $v['student_id'],
                'academic_year_id' => $v['academic_year_id'],
                'term_id'          => $v['term_id'],
            ],
            array_merge($v, [
                'public_token' => Str::random(40),
            ])
        );

        return redirect()->route('academics.report_cards.show', $report_card)
            ->with('success', 'Report card created.');
    }

    public function update(Request $request, ReportCard $report_card)
    {
        $v = $request->validate([
            'summary'           => 'nullable|string',
            'career_interest'   => 'nullable|string|max:255',
            'talent_noticed'    => 'nullable|string|max:255',
            'teacher_remark'    => 'nullable|string',
            'headteacher_remark'=> 'nullable|string',
        ]);

        $report_card->update($v);

        return back()->with('success','Report card updated.');
    }

    public function destroy(ReportCard $report_card)
    {
        $report_card->delete();
        return redirect()->route('academics.report_cards.index')
            ->with('success','Report card deleted.');
    }

    public function publish(ReportCard $report_card)
    {
        $report_card->update([
            'published_at'=>now(),
            'published_by'=>optional(Auth::user()->staff)->id
        ]);

        // Hook: notify guardians if needed
        return redirect()->route('academics.report_cards.index')->with('success','Report published.');
    }

    /** NEW: Term assessment rollup (per class, optional subject) */
    public function termAssessment(Request $request)
    {
        $yearId = $request->query('academic_year_id');
        $termId = $request->query('term_id');
        $classId = $request->query('classroom_id');
        $subjectId = $request->query('subject_id'); // optional

        $filtersValid = $yearId && $termId && $classId;

        $data = $filtersValid
            ? TermAssessmentService::build($yearId, $termId, $classId, $subjectId)
            : null;

        return view('academics.assessments.term', [
            'data' => $data,
            'years' => AcademicYear::orderByDesc('year')->get(),
            'terms' => Term::orderBy('name')->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
            'selected' => compact('yearId','termId','classId','subjectId'),
        ]);
    }

    /** UPDATED: Export PDF via service that aggregates subjects/skills/behavior/attendance + branding */
    public function exportPdf(\App\Models\Academics\ReportCard $report)
    {
        // Build the DTO (marks across all exams in the term, skills, attendance, branding, etc.)
        $dto = ReportCardBatchService::build($report->id);

        // Pass BOTH 'dto' and 'report_card' so old blades don't break
        $pdf = Pdf::loadView('academics.report_cards.pdf', [
            'dto'         => $dto,
            'report_card' => $report,
        ])->setPaper('A4');

        $filename = 'report_card_'.$dto['student']['admission_number'].'.pdf';

        $dir = storage_path('app/public/reports');
        if (! is_dir($dir)) { @mkdir($dir, 0775, true); }

        $path = "reports/$filename";
        $pdf->save(storage_path("app/public/$path"));

        $report->update(['pdf_path' => $path]);

        return $pdf->download($filename);
    }

    /** Public view by token (unchanged, but can load via service if you want) */
    public function publicView($token)
    {
        $report_card = ReportCard::where('public_token',$token)->firstOrFail();
        // For public, you can also build DTO if the PDF blade and show blade share structure
        return view('academics.report_cards.public', compact('report_card'));
    }

    public function generateForm()
    {
        return view('academics.report_cards.generate', [
            'years'      => \App\Models\AcademicYear::orderByDesc('year')->get(),
            'terms'      => \App\Models\Term::orderBy('name')->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
            'streams'    => Stream::orderBy('name')->get(),
        ]);
    }

    public function generate(Request $request, ReportCardBatchService $service)
    {
        $v = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'classroom_id'     => 'required|exists:classrooms,id',
            'stream_id'        => 'nullable|exists:streams,id',
        ]);

        $service->generateForClass(
            $v['academic_year_id'],
            $v['term_id'],
            $v['classroom_id'],
            $v['stream_id'] ?? null
        );

        return redirect()
            ->route('academics.report_cards.index')
            ->with('success', 'Report cards generated/updated for the selected class.');
    }

}
