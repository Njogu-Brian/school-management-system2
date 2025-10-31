<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use App\Models\Student;
use App\Models\Attendance;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;

use App\Models\Invoice;
use App\Models\Payment;

use App\Models\Academics\ExamMark;
use App\Models\Academics\Exam;
use App\Models\Academics\Subject;

use App\Models\Academics\StudentBehaviour;
use App\Models\Announcement;

use App\Models\Trip;
use App\Models\Vehicle;

class DashboardController extends Controller
{
    public function adminDashboard(Request $request)
    {
        $data = $this->buildDashboardData($request, 'admin');
        return view('dashboard.admin', $data);
    }

    public function teacherDashboard(Request $request)
    {
        $data = $this->buildDashboardData($request, 'teacher');
        return view('dashboard.teacher', $data + ['role' => 'teacher']);
    }

    private function buildDashboardData(Request $request, string $role = 'admin'): array
    {
        // ---- Filters
        $filters = [
            'year_id'      => (int)($request->get('year_id') ?? AcademicYear::latest('id')->value('id')),
            'term_id'      => (int)($request->get('term_id') ?? Term::latest('id')->value('id')),
            'from'         => $request->get('from') ?? now()->subDays(30)->toDateString(),
            'to'           => $request->get('to') ?? now()->toDateString(),
            'classroom_id' => $request->get('classroom_id'),
            'stream_id'    => $request->get('stream_id'),
        ];
        $today = now()->toDateString();

        // ---- Students base & counts
        $studentBase = Student::query()
            ->when($filters['classroom_id'], fn($q) => $q->where('classroom_id', $filters['classroom_id']))
            ->when($filters['stream_id'], fn($q) => $q->where('stream_id', $filters['stream_id']));
        $totalStudents = (clone $studentBase)->count();

        // ---- Attendance KPIs (present/absent today)
        $presentToday = Attendance::whereDate('date', $today)
            ->where('status', 'present')
            ->when($filters['classroom_id'], fn($q) => $q->whereHas('student', fn($s) => $s->where('classroom_id', $filters['classroom_id'])))
            ->when($filters['stream_id'], fn($q) => $q->whereHas('student', fn($s) => $s->where('stream_id', $filters['stream_id'])))
            ->count();

        $absentToday = Attendance::whereDate('date', $today)
            ->where('status', 'absent')
            ->when($filters['classroom_id'], fn($q) => $q->whereHas('student', fn($s) => $s->where('classroom_id', $filters['classroom_id'])))
            ->when($filters['stream_id'], fn($q) => $q->whereHas('student', fn($s) => $s->where('stream_id', $filters['stream_id'])))
            ->count();

        // ---- Finance KPIs
        $feesCollected = Payment::whereBetween('payment_date', [$filters['from'], $filters['to']])->sum('amount');

        // outstanding = total - sum(payments.amount) for unpaid|partial
        $paidSub = Payment::selectRaw('invoice_id, SUM(amount) as paid')->groupBy('invoice_id');
        $feesOutstanding = Invoice::leftJoinSub($paidSub, 'p', 'p.invoice_id', '=', 'invoices.id')
            ->whereIn('invoices.status', ['unpaid', 'partial'])
            ->selectRaw('SUM(GREATEST(invoices.total - COALESCE(p.paid, 0), 0)) as outstanding')
            ->value('outstanding') ?? 0;

        $teachersOnLeave = class_exists('\App\Models\Staff\Leave')
            ? \App\Models\Staff\Leave::whereDate('start', '<=', $today)->whereDate('end', '>=', $today)->count()
            : 0;

        $kpis = [
            'students'          => $totalStudents,
            'students_delta'    => 0.0,
            'present_today'     => $presentToday,
            'absent_today'      => $absentToday,
            'attendance_delta'  => 0.0,
            'fees_collected'    => $role === 'admin' ? $feesCollected : 0,
            'fees_outstanding'  => $role === 'admin' ? $feesOutstanding : 0,
            'fees_delta'        => 0.0,
            'teachers_on_leave' => $teachersOnLeave,
        ];

        // ---- Charts
        // 30-day attendance trend
        $days = collect(range(0, 29))->map(fn($i) => now()->subDays(29 - $i)->startOfDay());
        $attendance = [
            'labels'  => $days->map->format('d M')->toArray(),
            'present' => $days->map(fn($d) => Attendance::whereDate('date', $d)->where('status', 'present')->count())->toArray(),
            'absent'  => $days->map(fn($d) => Attendance::whereDate('date', $d)->where('status', 'absent')->count())->toArray(),
        ];

        // 12-month enrolment trend
        $months = collect(range(0, 11))->map(fn($i) => now()->subMonths(11 - $i)->startOfMonth());
        $enrolment = [
            'labels' => $months->map->format('M Y')->toArray(),
            'counts' => $months->map(fn($m) => Student::whereDate('created_at', '<=', $m->copy()->endOfMonth())->count())->toArray(),
        ];

        // Finance donut
        $finance = [
            'labels' => ['Collected', 'Outstanding'],
            'data'   => [$kpis['fees_collected'], $kpis['fees_outstanding']],
        ];

        // ---- Exam performance (avg by subject) using your actual columns
        $examMarksTable = (new ExamMark)->getTable(); // exam_marks
        $examsTable     = (new Exam)->getTable();     // exams
        $subjectsTable  = (new Subject)->getTable();  // subjects

        $examDateCol = collect(['starts_on', 'ends_on', 'start_date', 'exam_date', 'date', 'created_at'])
            ->first(fn($c) => Schema::hasColumn($examsTable, $c)) ?? 'created_at';

        $scoreExpr = collect(['final_score', 'score_moderated', 'score_raw', 'endterm_score', 'midterm_score', 'opener_score'])
            ->filter(fn($c) => Schema::hasColumn($examMarksTable, $c))
            ->pipe(fn($cols) => $cols->isEmpty() ? '0' : 'COALESCE(' . implode(',', $cols->all()) . ')');

        $examAgg = DB::table($examMarksTable)
            ->join($subjectsTable, "$subjectsTable.id", '=', "$examMarksTable.subject_id")
            ->join($examsTable,     "$examsTable.id",     '=', "$examMarksTable.exam_id")
            ->whereBetween("$examsTable.$examDateCol", [$filters['from'], $filters['to']])
            ->select("$subjectsTable.name as subject", DB::raw("AVG($scoreExpr) as avg"))
            ->groupBy("$subjectsTable.name")
            ->orderBy('subject')
            ->limit(8)
            ->get();

        $exam = [
            'labels' => $examAgg->pluck('subject'),
            'avgs'   => $examAgg->pluck('avg')->map(fn($v) => round((float)$v, 1)),
        ];

        // ---- Tables / Lists
        // Absence alerts (last 7 days)
        $absenceAlerts = Attendance::selectRaw('student_id, COUNT(*) as days_absent')
            ->where('status', 'absent')
            ->whereBetween('date', [now()->subDays(7)->toDateString(), now()->toDateString()])
            ->groupBy('student_id')
            ->orderByDesc('days_absent')
            ->take(6)
            ->get()
            ->map(function ($r) {
                $s = Student::find($r->student_id);
                return (object)[
                    'student_id'   => $s?->id,
                    'student_name' => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                    'classroom'    => optional($s?->classroom)->name,
                    'days_absent'  => $r->days_absent,
                ];
            });

        // Invoices list (compute paid/balance; order by created_at)
        $invoices = ($role === 'admin')
            ? Invoice::with('student')
                ->withSum('payments as paid_amount', 'amount')
                ->whereIn('status', ['unpaid', 'partial'])
                ->orderByDesc('created_at')
                ->take(6)
                ->get()
                ->map(function ($i) {
                    $paid    = (float)($i->paid_amount ?? 0);
                    $balance = max((float)$i->total - $paid, 0);
                    return (object)[
                        'id'           => $i->id,
                        'number'       => $i->invoice_number,
                        'student_name' => trim(($i->student->first_name ?? '') . ' ' . ($i->student->last_name ?? '')),
                        'total'        => (float)$i->total,
                        'paid'         => $paid,
                        'balance'      => $balance,
                        'status'       => $i->status, // unpaid | partial
                    ];
                })
            : collect();

        // Announcements (is_active / expires_at)
        $announcements = Announcement::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest()
            ->take(5)
            ->get();

        // Upcoming: Exams (starts_on) + Birthdays (no Event model)
        $upcoming = collect()
            ->merge(
                Exam::select('name as title', 'starts_on as date')
                    ->whereNotNull('starts_on')
                    ->where('starts_on', '>=', now()->toDateString())
                    ->orderBy('starts_on')
                    ->get()
                    ->map(fn($e) => ['title' => 'Exam: ' . $e->title, 'date' => $e->date, 'meta' => 'Exam'])
            )
            ->merge(
                Student::whereNotNull('dob')->get()
                    ->filter(function ($s) {
                        $d = Carbon::parse($s->dob)->setYear(now()->year);
                        return $d->between(now(), now()->addDays(14));
                    })
                    ->map(fn($s) => [
                        'title' => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                        'date'  => Carbon::parse($s->dob)->setYear(now()->year),
                        'meta'  => 'Birthday'
                    ])
            )
            ->sortBy('date')
            ->take(8)
            ->values();

        // Transport snapshot (columns you have)
        $transport = [
            'trips_last_30' => Trip::where('created_at', '>=', now()->subDays(30))->count(),
            'vehicles'      => Vehicle::count(),
        ];

        // Behaviour pulse (schema uses severity, not points)
        $behaviour = [
            // last 7 days by severity
            'minor'    => \App\Models\Academics\StudentBehaviour::whereBetween('date', [now()->subDays(7), now()])
                            ->where('severity', 'minor')->count(),
            'moderate' => \App\Models\Academics\StudentBehaviour::whereBetween('date', [now()->subDays(7), now()])
                            ->where('severity', 'moderate')->count(),
            'major'    => \App\Models\Academics\StudentBehaviour::whereBetween('date', [now()->subDays(7), now()])
                            ->where('severity', 'major')->count(),

            // recent items
            'recent' => \App\Models\Academics\StudentBehaviour::with('student','behaviour')
                        ->latest()->take(5)->get()
                        ->map(function ($b) {
                            return (object) [
                                'student_name' => trim(($b->student->first_name ?? '') . ' ' . ($b->student->last_name ?? '')),
                                'behaviour'    => $b->behaviour?->name ?? 'Behaviour',
                                'severity'     => $b->severity,
                                'date'         => $b->date,
                                'note'         => $b->note,
                            ];
                        }),
        ];

        // System "health" — use backup_settings (no backups table)
        $backupSettings = DB::table('backup_settings')->latest('updated_at')->first();
        $health = [
            'queue_ok'               => true,
            'gateway_ok'             => true,
            'last_backup_for_humans' => $backupSettings?->updated_at
                ? Carbon::parse($backupSettings->updated_at)->diffForHumans()
                : 'Unknown',
        ];

        return [
            // filter lists
            'years'      => AcademicYear::all(),
            'terms'      => Term::all(),
            'classrooms' => Classroom::all(),
            'streams'    => Stream::all(),

            // data
            'filters'       => $filters,
            'kpis'          => $kpis,
            'charts'        => compact('attendance', 'enrolment', 'finance', 'exam'),
            'absenceAlerts' => $absenceAlerts,
            'invoices'      => $invoices,
            'announcements' => $announcements,
            'upcoming'      => $upcoming,
            'transport'     => $transport,
            'behaviour'     => $behaviour,
            'health'        => $health,

            'role'          => $role,
        ];
    }

    public function parentDashboard(Request $request)
    {
        $data = $this->buildDashboardData($request, 'parent');
        return view('dashboard.parent', $data + ['role' => 'parent']);
    }

    public function studentDashboard(Request $request)
    {
        $data = $this->buildDashboardData($request, 'student');
        return view('dashboard.student', $data + ['role' => 'student']);
    }

    public function financeDashboard(Request $request)
    {
        $data = $this->buildDashboardData($request, 'finance');
        return view('dashboard.finance', $data + ['role' => 'finance']);
    }

    public function transportDashboard(Request $request)
    {
        $data = $this->buildDashboardData($request, 'transport');
        return view('dashboard.transport', $data + ['role' => 'transport']);
    }
}
