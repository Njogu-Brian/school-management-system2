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
use App\Services\ReportCardPublishService;
use App\Support\AcademicContext;
use Illuminate\Validation\Rule;

class ReportCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:report_cards.view')->only(['index', 'show']);
        $this->middleware('permission:report_cards.create')->only(['create', 'store']);
        $this->middleware('permission:report_cards.edit')->only(['edit', 'update']);
        $this->middleware('permission:report_cards.delete')->only(['destroy']);
        $this->middleware('permission:report_cards.publish')->only(['publish', 'bulkPublish', 'bulkPublishClass']);
        $this->middleware('permission:report_cards.generate')->only(['generateForm', 'generate']);
        $this->middleware('permission:report_cards.export_pdf')->only(['exportPdf']);
    }

    public function index(Request $request)
    {
        $query = ReportCard::with(['student','publisher','academicYear','term','classroom']);

        // Teachers can only see report cards for their assigned classes
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $assignedClassroomIds = \Illuminate\Support\Facades\DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                $query->whereIn('classroom_id', $assignedClassroomIds);
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }

        // Filters
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('archive', 0)->where('is_alumni', false);
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }

        $report_cards = $query->latest()->paginate(20)->withQueryString();

        $years = \App\Support\AcademicContext::years();
        $terms = \App\Support\AcademicContext::allTermsForSelect();
        
        // Filter classrooms based on user role
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $assignedClassroomIds = \Illuminate\Support\Facades\DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->distinct()
                    ->pluck('classroom_id')
                    ->toArray();
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
            } else {
                $classrooms = collect();
            }
        } else {
            $classrooms = Classroom::orderBy('name')->get();
        }

        return view('academics.report_cards.index', compact('report_cards', 'years', 'terms', 'classrooms'));
    }

    public function show(ReportCard $report_card)
    {
        // Check if teacher has access to this report card's classroom
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff && $report_card->classroom_id) {
                $hasAccess = \Illuminate\Support\Facades\DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $report_card->classroom_id)
                    ->exists();
                
                if (!$hasAccess) {
                    abort(403, 'You do not have access to this report card.');
                }
            }
        }

        $report_card->load([
            'student.classroom','student.stream',
            'academicYear','term','classroom','stream',
            'skills',
            'overallPerformanceLevel'
        ]);

        $dto = ReportCardBatchService::build($report_card->id);
        $isPdf = false;

        return view('academics.report_cards.show', compact('report_card','dto','isPdf'));
    }

    public function create()
    {
        return view('academics.report_cards.create', [
            'students' => Student::with('classroom','stream')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->orderBy('last_name')
                ->get(),
            'years'    => \App\Support\AcademicContext::years(),
            'terms'    => \App\Support\AcademicContext::allTermsForSelect(),
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

        // Validate student is not alumni or archived
        $student = Student::withAlumni()->findOrFail($v['student_id']);
        if ($student->is_alumni || $student->archive) {
            return back()
                ->withInput()
                ->with('error', 'Cannot create report cards for alumni or archived students.');
        }
        
        // Check if teacher has access to classroom
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $hasAccess = \Illuminate\Support\Facades\DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $v['classroom_id'])
                    ->exists();
                
                if (!$hasAccess) {
                    return back()
                        ->withInput()
                        ->with('error', 'You do not have access to this classroom.');
                }
            }
        }

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

    public function publish(Request $request, ReportCard $report_card, ReportCardPublishService $publishService)
    {
        $notify = $request->boolean('notify_parents');
        $channels = $notify ? $request->input('channels', ['sms', 'email', 'whatsapp']) : [];

        $publishService->publishMany([$report_card->id], (array) $channels, $notify);

        return back()->with('success', $notify
            ? 'Report published and family link sent.'
            : 'Report published.');
    }

    public function bulkPublish(Request $request, ReportCardPublishService $publishService)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:report_cards,id'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(['sms', 'email', 'whatsapp'])],
        ]);

        $result = $publishService->publishMany(
            $data['ids'],
            $data['channels'],
            true
        );

        return back()->with('success', sprintf(
            'Published %d report card(s). Notified %d familie(s).',
            $result['published'],
            $result['families_notified']
        ));
    }

    public function bulkPublishClass(Request $request, ReportCardPublishService $publishService)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(['sms', 'email', 'whatsapp'])],
        ]);

        $ids = ReportCard::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where('classroom_id', $data['classroom_id'])
            ->when(! empty($data['stream_id']), fn ($q) => $q->where('stream_id', $data['stream_id']))
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return back()->with('warning', 'No report cards found for the selected class.');
        }

        $result = $publishService->publishMany($ids, $data['channels'], true);

        return back()->with('success', sprintf(
            'Published %d report card(s). Notified %d familie(s).',
            $result['published'],
            $result['families_notified']
        ));
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
            'years' => \App\Support\AcademicContext::years(),
            'terms' => \App\Support\AcademicContext::allTermsForSelect(),
            'classrooms' => Classroom::orderBy('name')->get(),
            'selected' => compact('yearId','termId','classId','subjectId'),
        ]);
    }

    /** UPDATED: Export PDF via service that aggregates subjects/skills/behavior/attendance + branding */
    public function exportPdf(\App\Models\Academics\ReportCard $report)
    {
        $dto = ReportCardBatchService::build($report->id);

        $pdf = Pdf::loadView('academics.report_cards.pdf', [
            'dto'         => $dto,
            'report_card' => $report,
        ])->setPaper('A4', 'portrait');

        $filename = ReportCardBatchService::pdfFilename($dto);

        $dir = storage_path('app/public/reports');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $path = 'reports/'.$filename;
        $pdf->save(storage_path('app/public/'.$path));

        $report->update(['pdf_path' => $path]);

        return $pdf->download($filename);
    }

    public function generateForm()
    {
        return view('academics.report_cards.generate', array_merge(
            AcademicContext::forView(),
            [
                'classrooms' => Classroom::orderBy('name')->get(),
                'streams'    => Stream::orderBy('name')->get(),
            ]
        ));
    }

    public function generate(Request $request, ReportCardBatchService $service, ReportCardPublishService $publishService)
    {
        $v = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'classroom_id'     => 'required|exists:classrooms,id',
            'stream_id'        => 'nullable|exists:streams,id',
            'publish_and_notify' => 'nullable|boolean',
            'channels' => ['nullable', 'array'],
            'channels.*' => [Rule::in(['sms', 'email', 'whatsapp'])],
        ]);

        // Check if teacher has access to classroom
        if (Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $hasAccess = \Illuminate\Support\Facades\DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $v['classroom_id'])
                    ->exists();
                
                if (!$hasAccess) {
                    return back()
                        ->withInput()
                        ->with('error', 'You do not have access to generate report cards for this classroom.');
                }
            }
        }

        $service->generateForClass(
            $v['academic_year_id'],
            $v['term_id'],
            $v['classroom_id'],
            $v['stream_id'] ?? null
        );

        $message = 'Report cards generated/updated for the selected class.';

        if ($request->boolean('publish_and_notify')) {
            $ids = ReportCard::query()
                ->where('academic_year_id', $v['academic_year_id'])
                ->where('term_id', $v['term_id'])
                ->where('classroom_id', $v['classroom_id'])
                ->when(! empty($v['stream_id']), fn ($q) => $q->where('stream_id', $v['stream_id']))
                ->pluck('id')
                ->all();

            $channels = $request->input('channels', ['sms', 'email', 'whatsapp']);
            $result = $publishService->publishMany($ids, $channels, true);
            $message = sprintf(
                'Generated and published %d report card(s). Notified %d familie(s).',
                $result['published'],
                $result['families_notified']
            );
        }

        return redirect()
            ->route('academics.report_cards.index')
            ->with('success', $message);
    }

}
