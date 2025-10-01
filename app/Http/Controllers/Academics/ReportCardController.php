<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportCardSkill;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Exam;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $report_card->load(['student','academicYear','term','classroom','stream','marks.subject','skills']);
        return view('academics.report_cards.show', compact('report_card'));
    }

    public function create()
    {
        return view('academics.report_cards.create', [
            'students' => Student::with('classroom','stream')->orderBy('last_name')->get(),
            'years'    => \App\Models\AcademicYear::all(),
            'terms'    => \App\Models\Term::all(),
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

        return redirect()->route('academics.report-cards.show', $report_card)
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
            'skills'            => 'array',
            'skills.*.id'       => 'nullable|exists:report_card_skills,id',
            'skills.*.skill_name'=> 'required|string|max:255',
            'skills.*.rating'   => 'nullable|in:EE,ME,AE,BE',
        ]);

        // Only update scalar fields, not skills array
        $report_card->update(collect($v)->except('skills')->toArray());

        // Save/update skills
        if (!empty($v['skills'])) {
            foreach ($v['skills'] as $skillData) {
                if (!empty($skillData['id'])) {
                    $skill = ReportCardSkill::find($skillData['id']);
                    $skill?->update($skillData);
                } else {
                    $report_card->skills()->create($skillData);
                }
            }
        }

        return back()->with('success','Report card updated.');
    }

    public function destroy(ReportCard $report_card)
    {
        $report_card->delete();
        return redirect()->route('academics.report-cards.index')
            ->with('success','Report card deleted.');
    }

    public function publish(ReportCard $report_card)
    {
        $report_card->update([
            'published_at'=>now(),
            'published_by'=>optional(Auth::user()->staff)->id
        ]);

        // TODO: trigger notification service (SMS + email with link)
        return redirect()->route('academics.report-cards.index')->with('success','Report published.');
    }

    public function exportPdf(ReportCard $report)
{
    $report->load(['student','academicYear','term','classroom','marks.subject','skills']);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('academics.report_cards.pdf', compact('report'));

    $filename = 'report_card_'.$report->student->admission_no.'.pdf';

    // Ensure reports directory exists inside storage/app/public
    $reportsDir = storage_path('app/public/reports');
    if (!file_exists($reportsDir)) {
        mkdir($reportsDir, 0775, true);
    }

    // Save into storage/app/public/reports
    $path = "reports/$filename";
    $pdf->save(storage_path("app/public/$path"));

    // Update DB with relative path (no duplicate 'storage/')
    $report->update(['pdf_path' => $path]);

    // Return file for immediate download
    return $pdf->download($filename);
}


    public function publicView($token)
    {
        $report_card = ReportCard::where('public_token',$token)->firstOrFail();
        $report_card->load(['student','academicYear','term','classroom','marks.subject','skills']);
        return view('academics.report_cards.public', compact('report_card'));
    }
}
