<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ReportCard;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Academics\Classroom;
use App\Services\ReportCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ReportCardController extends Controller
{
    public function index()
    {
        $reports = ReportCard::with(['student','publisher'])->latest()->paginate(20);
        return view('academics.report_cards.index', compact('reports'));
    }

    public function create()
    {
        return view('academics.report_cards.create', [
            'years'      => AcademicYear::orderByDesc('year')->get(),
            'terms'      => Term::orderBy('id','desc')->get(),
            'classrooms' => Classroom::orderBy('name')->get(),
        ]);
    }

    public function store(Request $r, ReportCardService $svc)
    {
        $data = $r->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'classroom_id'     => 'required|exists:classrooms,id',
        ]);

        $students = Student::where('classroom_id',$data['classroom_id'])->get();
        foreach ($students as $s) { $svc->build($s->id, $data['academic_year_id'], $data['term_id']); }

        return redirect()->route('academics.report-cards.index')->with('success','Report cards created/updated.');
    }

    public function show(ReportCard $report_card)
    {
        $report_card->load(['student','classroom','stream','skills','marks.subject','marks.exam']);
        return view('academics.report_cards.show', compact('report_card'));
    }

    public function edit(ReportCard $report_card)
    {
        $skillsPreset = [
            'Communication and collaboration skills',
            'Creativity and imagination skills',
            'Self efficacy skills',
            'Critical thinking skills',
            'Respect for teachers and other learners',
            'Hard work and self driven',
            'Discipline and organization',
            'Handwriting','Spelling','Groupwork',
            'Project work given completion','Digital Literacy skills',
            'Games interest like football, netball, volleyball, interest',
            'Self love, self respect, assertiveness',
        ];
        $report_card->load('skills');
        return view('academics.report_cards.edit', compact('report_card','skillsPreset'));
    }

    public function update(Request $r, ReportCard $report_card)
    {
        $data = $r->validate([
            'career_interest'    => 'nullable|string|max:255',
            'talent_noticed'     => 'nullable|string|max:255',
            'teacher_remark'     => 'nullable|string',
            'headteacher_remark' => 'nullable|string',
            'skills'             => 'nullable|array',
            'skills.*.name'      => 'required_with:skills|string|max:255',
            'skills.*.rating'    => 'nullable|in:EE,ME,AE,BE',
        ]);

        $report_card->update($data);

        if (!empty($data['skills'])) {
            $report_card->skills()->delete();
            foreach ($data['skills'] as $row) {
                $report_card->skills()->create([
                    'skill_name'=>$row['name'],
                    'rating'=>$row['rating'] ?? null,
                ]);
            }
        }

        return redirect()->route('academics.report-cards.show',$report_card)->with('success','Report card updated.');
    }

    public function publish(ReportCard $report, ReportCardService $svc)
    {
        $svc->generatePdf($report);
        $report->update(['published_at'=>now(), 'published_by'=>auth()->id()]);

        // OPTIONAL email attach if parent email exists on your model
        $parentEmail = optional($report->student->guardian)->email ?? null;
        if ($parentEmail && $report->pdf_path) {
            Mail::raw('Your child’s report card is attached.', function($m) use ($report, $parentEmail) {
                $m->to($parentEmail)->subject('Report Card')
                  ->attach(Storage::disk('public')->path($report->pdf_path));
            });
        }

        return redirect()->route('academics.report-cards.index')->with('success','Report published.');
    }

    // Public token view (for SMS link)
    public function publicView(string $token) {
        $report = ReportCard::where('public_token',$token)->firstOrFail()
            ->load(['student','classroom','stream','skills','marks.subject','marks.exam']);
        return view('academics.report_cards.public', compact('report'));
    }

    public function destroy(ReportCard $report_card)
    {
        $report_card->delete();
        return redirect()->route('academics.report-cards.index')->with('success','Report card deleted.');
    }
}
