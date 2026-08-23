<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use App\Models\Student;
use App\Models\Attendance;
use App\Models\SchoolDay;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;

use App\Models\Academics\ExamMark;
use App\Models\Academics\Exam;
use App\Models\Academics\Subject;

use App\Models\Academics\StudentBehaviour;
use App\Models\Announcement;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Support\AcademicContext;
use App\Support\NavAccess;
use App\Services\DashboardInsightsService;

class DashboardController extends Controller
{
    public function adminDashboard(Request $request)
    {
        $this->authorizeDashboard('admin.dashboard');
        $data = $this->buildDashboardData($request, 'admin', null, null);
        return view('dashboard.admin', $data);
    }

    public function teacherDashboard(Request $request)
    {
        $this->authorizeDashboard('teacher.dashboard');
        // For teachers, build dashboard data filtered to their assigned classes/streams
        $user = auth()->user();
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        $streamAssignments = $user->getStreamAssignments();
        
        // Set filters to teacher's assigned classrooms
        $request->merge([
            'classroom_id' => $request->get('classroom_id') ?: (count($assignedClassroomIds) == 1 ? $assignedClassroomIds[0] : null),
        ]);
        
        $data = $this->buildDashboardData($request, 'teacher', $assignedClassroomIds, $streamAssignments);
        $teacherData = $this->buildTeacherSpecificData($request);
        return view('dashboard.teacher', $data + $teacherData + ['role' => 'teacher']);
    }

    public function supervisorDashboard(Request $request)
    {
        $this->authorizeDashboard('supervisor.dashboard');
        if (!is_supervisor() || auth()->user()->hasAnyRole(['Admin', 'Super Admin'])) {
            abort(403, 'Access denied. This dashboard is for supervisors only.');
        }
        
        $data = $this->buildDashboardData($request, 'supervisor');
        $supervisorData = $this->buildSupervisorSpecificData($request);
        return view('dashboard.supervisor', $data + $supervisorData + ['role' => 'supervisor']);
    }

    private function buildDashboardData(Request $request, string $role = 'admin', array $assignedClassroomIds = null, array $streamAssignments = null): array
    {
        // ---- Filters (defaults = current active year + term; query params keep user changes)
        $defaultYearId = AcademicContext::resolveYearId(null);
        $yearId = (int) (AcademicContext::resolveYearId(
            $request->filled('year_id') ? (int) $request->get('year_id') : null
        ) ?: 0);

        $defaultTermId = $defaultYearId
            ? (int) (AcademicContext::resolveTermId((int) $defaultYearId, null) ?: 0)
            : 0;
        $termId = $yearId > 0
            ? (int) (AcademicContext::resolveTermId(
                $yearId,
                $request->filled('term_id') ? (int) $request->get('term_id') : null
            ) ?: 0)
            : 0;

        $selectedTerm = $termId ? Term::find($termId) : null;
        $hasExplicitDates = $request->filled('from') && $request->filled('to');
        [$fromDate, $toDate] = $hasExplicitDates
            ? [$request->get('from'), $request->get('to')]
            : $this->defaultDateRangeForTerm($selectedTerm);

        $filters = [
            'year_id'      => $yearId,
            'term_id'      => $termId,
            'from'         => $fromDate,
            'to'           => $toDate,
            'classroom_id' => $request->get('classroom_id'),
            'stream_id'    => $request->get('stream_id'),
            'status'       => $request->get('status'),
        ];
        $today = now()->toDateString();
        $needsFinance = in_array($role, ['admin', 'finance'], true);
        $needsAttendanceTrend = $role === 'admin';
        $needsEnrolment = $role === 'admin';
        $needsExam = $role === 'admin';
        $needsTransport = in_array($role, ['admin', 'transport'], true);
        $needsBehaviour = in_array($role, ['admin', 'supervisor'], true);

        // ---- Students base & counts
        $studentBase = Student::query();

        if ($role === 'teacher' && $assignedClassroomIds !== null) {
            // Single source of truth with stream + subject + senior-supervision rules (see User::applyTeacherStudentFilter)
            auth()->user()->applyTeacherStudentFilter($studentBase);
        } else {
            $studentBase->when($filters['classroom_id'], fn($q) => $q->where('classroom_id', $filters['classroom_id']))
                ->when($filters['stream_id'], fn($q) => $q->where('stream_id', $filters['stream_id']));
        }
        
        $totalStudents = (clone $studentBase)->count();

        // ---- Attendance KPIs (present/absent today)
        $attendanceQuery = Attendance::whereDate('date', $today);

        if ($role === 'teacher' && $assignedClassroomIds !== null) {
            $attendanceQuery->whereHas('student', function ($q) {
                auth()->user()->applyTeacherStudentFilter($q);
            });
        } else {
            $attendanceQuery->when($filters['classroom_id'], fn($q) => $q->whereHas('student', fn($s) => $s->where('classroom_id', $filters['classroom_id'])))
                ->when($filters['stream_id'], fn($q) => $q->whereHas('student', fn($s) => $s->where('stream_id', $filters['stream_id'])));
        }
        
        if (SchoolDay::isSchoolDay($today)) {
            // Count distinct students (attendance can have multiple rows per student per day, e.g. per period/subject)
            $presentToday = $this->countDistinctAttendanceStudents($attendanceQuery, 'present');
            $absentToday = $this->countDistinctAttendanceStudents($attendanceQuery, 'absent');
        } else {
            $presentToday = 0;
            $absentToday = 0;
        }

        // ---- Finance KPIs (exclude swimming - managed separately in Swimming module)
        $excludeSwimmingPayments = function ($query) {
            $query->whereRaw("COALESCE(receipt_number, '') NOT LIKE 'SWIM-%'")
                ->whereRaw("(COALESCE(narration, '') NOT LIKE '%Swimming%' AND COALESCE(narration, '') NOT LIKE '%(Swimming)%')");
        };

        $totalInvoiced = 0.0;
        $feesCollected = 0.0;
        $feesOutstanding = 0.0;
        $feesOverdue = 0.0;
        $overdueInvoiceCount = 0;
        $financeScope = 'date_range';
        $voteheadBreakdown = collect();
        $weeklyPayments = [];

        $termInvoiceIds = collect();
        if ($needsFinance) {
            $swimmingVoteheadIds = \App\Models\Votehead::where(function ($q) {
                $q->where('name', 'like', '%swim%')->orWhere('code', 'like', '%SWIM%');
            })->pluck('id')->toArray();

            if ($filters['term_id'] > 0) {
                $term = $selectedTerm ?: Term::find($filters['term_id']);
                if ($term) {
                    $termInvoiceQuery = Invoice::where('term_id', $filters['term_id'])
                        ->whereNull('reversed_at')
                        ->whereNull('deleted_at');
                    $this->scopeInvoicesToStudentFilters($termInvoiceQuery, $filters);
                    $termInvoiceIds = $termInvoiceQuery->pluck('id');

                    if ($termInvoiceIds->isNotEmpty()) {
                        $totalInvoiced = $this->sumActiveInvoiceItems($termInvoiceIds, $swimmingVoteheadIds);

                        $feesCollected = (float) PaymentAllocation::whereHas('invoiceItem', function ($q) use ($termInvoiceIds, $swimmingVoteheadIds) {
                            $q->whereIn('invoice_id', $termInvoiceIds)
                                ->where('status', 'active');
                            if (!empty($swimmingVoteheadIds)) {
                                $q->whereNotIn('votehead_id', $swimmingVoteheadIds);
                            }
                        })
                            ->whereHas('payment', function ($q) use ($excludeSwimmingPayments) {
                                $q->where('reversed', false);
                                $excludeSwimmingPayments($q);
                            })
                            ->sum('amount');

                        $openInvoices = Invoice::whereIn('id', $termInvoiceIds)
                            ->whereIn('status', ['unpaid', 'partial']);

                        $feesOutstanding = (float) (clone $openInvoices)->sum('balance');
                        $overdueQuery = (clone $openInvoices)
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', $today);
                        $feesOverdue = (float) $overdueQuery->sum('balance');
                        $overdueInvoiceCount = (int) (clone $openInvoices)
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', $today)
                            ->count();
                        $financeScope = 'term';
                    }
                }
            }

            if ($financeScope === 'date_range') {
                $feesCollectedQuery = Payment::whereBetween('payment_date', [$filters['from'], $filters['to']])
                    ->where('reversed', false)
                    ->where($excludeSwimmingPayments);
                $this->scopePaymentsToStudentFilters($feesCollectedQuery, $filters);
                $feesCollected = (float) $feesCollectedQuery->sum('amount');

                $totalInvoiced = (float) InvoiceItem::whereHas('invoice', function ($q) use ($filters) {
                    $q->whereBetween('created_at', [$filters['from'], $filters['to']])
                        ->whereNull('reversed_at');
                    $this->scopeInvoicesToStudentFilters($q, $filters);
                })
                    ->where('status', 'active')
                    ->when(!empty($swimmingVoteheadIds), fn ($q) => $q->whereNotIn('votehead_id', $swimmingVoteheadIds))
                    ->selectRaw('COALESCE(SUM(amount - COALESCE(discount_amount, 0)), 0) as total')
                    ->value('total');

                $openInvoices = Invoice::query()
                    ->whereNull('reversed_at')
                    ->whereIn('status', ['unpaid', 'partial']);
                $this->scopeInvoicesToStudentFilters($openInvoices, $filters);
                $feesOutstanding = (float) (clone $openInvoices)->sum('balance');
                $feesOverdue = (float) (clone $openInvoices)
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', $today)
                    ->sum('balance');
                $overdueInvoiceCount = (int) (clone $openInvoices)
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', $today)
                    ->count();
            }

            $voteheadQuery = InvoiceItem::query()
                ->where('status', 'active')
                ->when(!empty($swimmingVoteheadIds), fn ($q) => $q->whereNotIn('votehead_id', $swimmingVoteheadIds));
            if ($financeScope === 'term' && $termInvoiceIds->isNotEmpty()) {
                $voteheadQuery->whereIn('invoice_id', $termInvoiceIds);
            } else {
                $voteheadQuery->whereHas('invoice', function ($q) use ($filters) {
                    $q->whereBetween('created_at', [$filters['from'], $filters['to']])
                        ->whereNull('reversed_at');
                    $this->scopeInvoicesToStudentFilters($q, $filters);
                });
            }
            $voteheadBreakdown = $voteheadQuery
                ->with('votehead')
                ->select('votehead_id', DB::raw('SUM(amount - COALESCE(discount_amount, 0)) as total_amount'))
                ->groupBy('votehead_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'votehead_id' => $item->votehead_id,
                        'votehead_name' => $item->votehead->name ?? 'Unknown',
                        'votehead_code' => $item->votehead->code ?? '',
                        'total_amount' => (float) $item->total_amount,
                    ];
                })
                ->sortByDesc('total_amount')
                ->values();

            if ($filters['term_id']) {
                $term = $selectedTerm ?: Term::find($filters['term_id']);
                if ($term) {
                    $termStart = $term->opening_date ? $term->opening_date->toDateString() : $filters['from'];
                    $termEnd = $term->closing_date ? $term->closing_date->toDateString() : $filters['to'];
                    $weeklyPayments = Payment::whereBetween('payment_date', [$termStart, $termEnd])
                        ->where('reversed', false)
                        ->where($excludeSwimmingPayments)
                        ->select(
                            DB::raw('YEARWEEK(payment_date, 1) as week'),
                            DB::raw('MIN(payment_date) as week_start'),
                            DB::raw('SUM(amount) as total_amount'),
                            DB::raw('COUNT(*) as payment_count')
                        )
                        ->groupBy('week')
                        ->orderBy('week')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'week' => $item->week,
                                'week_label' => Carbon::parse($item->week_start)->format('M d').' - '.
                                    Carbon::parse($item->week_start)->addDays(6)->format('M d, Y'),
                                'total_amount' => (float) $item->total_amount,
                                'payment_count' => (int) $item->payment_count,
                            ];
                        })
                        ->toArray();
                }
            }
        }

        $lateToday = 0;
        if (SchoolDay::isSchoolDay($today)) {
            $lateToday = $this->countDistinctAttendanceStudents($attendanceQuery, 'late');
        }

        $insights = app(DashboardInsightsService::class);
        $staffActive = in_array($role, ['admin', 'supervisor'], true) ? $insights->activeStaffCount() : 0;
        $teachersOnLeave = in_array($role, ['admin', 'supervisor'], true) ? $insights->staffOnLeaveToday($today) : 0;
        $pendingApprovals = in_array($role, ['admin', 'supervisor'], true)
            ? $insights->pendingApprovals()
            : ['total' => 0, 'leave' => 0, 'lesson_plans' => 0, 'requisitions' => 0, 'admissions' => 0];

        $markedToday = $presentToday + $absentToday + $lateToday;
        $attendancePct = ($totalStudents > 0 && SchoolDay::isSchoolDay($today))
            ? round(($presentToday / $totalStudents) * 100, 1)
            : null;
        $unmarkedToday = SchoolDay::isSchoolDay($today)
            ? max($totalStudents - $markedToday, 0)
            : 0;
        $collectionRate = ($totalInvoiced > 0 && $needsFinance)
            ? round(($feesCollected / $totalInvoiced) * 100, 1)
            : null;
        $paymentsToday = 0;
        if ($needsFinance) {
            $paymentsTodayQuery = Payment::whereDate('payment_date', $today)
                ->where('reversed', false)
                ->where($excludeSwimmingPayments);
            $this->scopePaymentsToStudentFilters($paymentsTodayQuery, $filters);
            $paymentsToday = (float) $paymentsTodayQuery->sum('amount');
        }
        $owingStudents = 0;
        if ($needsFinance && $filters['term_id'] > 0) {
            $owingQuery = Invoice::query()
                ->where('term_id', $filters['term_id'])
                ->whereNull('reversed_at')
                ->whereIn('status', ['unpaid', 'partial'])
                ->where('balance', '>', 0);
            $this->scopeInvoicesToStudentFilters($owingQuery, $filters);
            $owingStudents = $owingQuery->distinct()->count('student_id');
        }

        $kpis = [
            'students'          => $totalStudents,
            'students_delta'    => $role === 'admin' ? $insights->studentsDelta($selectedTerm, $totalStudents) : null,
            'present_today'     => $presentToday,
            'absent_today'      => $absentToday,
            'late_today'        => $lateToday,
            'unmarked_today'    => $unmarkedToday,
            'attendance_pct'    => $attendancePct,
            'attendance_delta'  => null,
            'staff_active'      => $staffActive,
            'pending_approvals' => $pendingApprovals['total'],
            'total_invoiced'    => $needsFinance ? $totalInvoiced : 0,
            'fees_collected'    => $needsFinance ? $feesCollected : 0,
            'fees_outstanding'  => $needsFinance ? $feesOutstanding : 0,
            'fees_overdue'      => $needsFinance ? $feesOverdue : 0,
            'overdue_invoice_count' => $overdueInvoiceCount,
            'collection_rate'   => $collectionRate,
            'payments_today'    => $paymentsToday,
            'owing_students'    => $owingStudents,
            'finance_scope'     => $financeScope,
            'fees_delta'        => null,
            'teachers_on_leave' => $teachersOnLeave,
            'is_school_day'     => SchoolDay::isSchoolDay($today),
        ];

        // ---- Charts
        $days = collect(range(0, 29))->map(fn ($i) => now()->subDays(29 - $i)->startOfDay());
        $attendancePresent = array_fill(0, 30, 0);
        $attendanceAbsent = array_fill(0, 30, 0);

        if ($needsAttendanceTrend) {
            $fromChart = $days->first()->toDateString();
            $toChart = $days->last()->toDateString();
            $rows = Attendance::query()
                ->selectRaw('date, status, COUNT(DISTINCT student_id) as cnt')
                ->whereBetween('date', [$fromChart, $toChart])
                ->whereIn('status', ['present', 'absent'])
                ->groupBy('date', 'status')
                ->get()
                ->groupBy(fn ($row) => Carbon::parse($row->date)->toDateString());

            foreach ($days as $i => $day) {
                $key = $day->toDateString();
                if (! SchoolDay::isSchoolDay($key)) {
                    continue;
                }
                $dayRows = $rows->get($key, collect());
                $attendancePresent[$i] = (int) optional($dayRows->firstWhere('status', 'present'))->cnt;
                $attendanceAbsent[$i] = (int) optional($dayRows->firstWhere('status', 'absent'))->cnt;
            }
        }

        $attendance = [
            'labels'  => $days->map->format('d M')->toArray(),
            'present' => $attendancePresent,
            'absent'  => $attendanceAbsent,
        ];

        $months = collect(range(0, 11))->map(fn ($i) => now()->subMonths(11 - $i)->startOfMonth());
        $enrolmentCounts = array_fill(0, 12, 0);
        if ($needsEnrolment) {
            $monthlyCreated = Student::query()
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as c")
                ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
                ->pluck('c', 'ym');
            $cumulative = [];
            $running = 0;
            foreach ($monthlyCreated->sortKeys() as $ym => $count) {
                $running += (int) $count;
                $cumulative[$ym] = $running;
            }
            $enrolmentCounts = $months->map(function ($month) use ($cumulative) {
                $target = $month->format('Y-m');
                $best = 0;
                foreach ($cumulative as $ym => $total) {
                    if ($ym <= $target) {
                        $best = $total;
                    }
                }

                return $best;
            })->toArray();
        }
        $enrolment = [
            'labels' => $months->map->format('M Y')->toArray(),
            'counts' => $enrolmentCounts,
        ];

        // Finance donut
        $finance = [
            'labels' => ['Collected', 'Outstanding'],
            'data'   => [$kpis['fees_collected'], $kpis['fees_outstanding']],
        ];

        $exam = ['labels' => collect(), 'avgs' => collect()];
        if ($needsExam) {
            $examMarksTable = (new ExamMark)->getTable();
            $examsTable     = (new Exam)->getTable();
            $subjectsTable  = (new Subject)->getTable();

            $examDateCol = collect(['starts_on', 'ends_on', 'start_date', 'exam_date', 'date', 'created_at'])
                ->first(fn ($c) => Schema::hasColumn($examsTable, $c)) ?? 'created_at';

            $scoreExpr = collect(['final_score', 'score_moderated', 'score_raw', 'endterm_score', 'midterm_score', 'opener_score'])
                ->filter(fn ($c) => Schema::hasColumn($examMarksTable, $c))
                ->pipe(fn ($cols) => $cols->isEmpty() ? '0' : 'COALESCE('.implode(',', $cols->all()).')');

            $examAgg = DB::table($examMarksTable)
                ->join($subjectsTable, "$subjectsTable.id", '=', "$examMarksTable.subject_id")
                ->join($examsTable, "$examsTable.id", '=', "$examMarksTable.exam_id")
                ->whereBetween("$examsTable.$examDateCol", [$filters['from'], $filters['to']])
                ->when($filters['classroom_id'], fn ($q) => $q->where("$examsTable.classroom_id", $filters['classroom_id']))
                ->when($filters['stream_id'], fn ($q) => $q->whereIn("$examMarksTable.student_id", function ($subQ) use ($filters) {
                    $subQ->select('id')->from('students')->where('stream_id', $filters['stream_id']);
                }))
                ->select("$subjectsTable.name as subject", DB::raw("AVG($scoreExpr) as avg"))
                ->groupBy("$subjectsTable.name")
                ->orderBy('subject')
                ->limit(8)
                ->get();

            $exam = [
                'labels' => $examAgg->pluck('subject'),
                'avgs'   => $examAgg->pluck('avg')->map(fn ($v) => round((float) $v, 1)),
            ];
        }

        $absenceAlerts = collect();
        if (in_array($role, ['admin', 'teacher'], true)) {
            $absenceRows = Attendance::selectRaw('student_id, COUNT(DISTINCT date) as days_absent')
                ->where('status', 'absent')
                ->whereBetween('date', [now()->subDays(7)->toDateString(), now()->toDateString()]);
            if ($role === 'teacher' && $assignedClassroomIds !== null) {
                $absenceRows->whereHas('student', function ($q) {
                    auth()->user()->applyTeacherStudentFilter($q);
                });
            }
            $absenceRows = $absenceRows->groupBy('student_id')->orderByDesc('days_absent')->take(6)->get();
            $absenceStudents = Student::with('classroom')->whereIn('id', $absenceRows->pluck('student_id'))->get()->keyBy('id');
            $absenceAlerts = $absenceRows->map(function ($r) use ($absenceStudents) {
                $s = $absenceStudents->get($r->student_id);
                return (object) [
                    'student_id' => $s?->id,
                    'student_name' => $s?->full_name ?: trim(($s->first_name ?? '').' '.($s->last_name ?? '')),
                    'classroom' => optional($s?->classroom)->name,
                    'days_absent' => $r->days_absent,
                ];
            });
        }

        $invoices = collect();
        $outstandingStudents = collect();
        if ($needsFinance) {
            $invoiceQuery = Invoice::with(['student.classroom'])
                ->whereNull('reversed_at')
                ->whereIn('status', ['unpaid', 'partial']);
            if ($filters['term_id'] > 0) {
                $invoiceQuery->where('term_id', $filters['term_id']);
            }
            $this->scopeInvoicesToStudentFilters($invoiceQuery, $filters);
            $invoices = $invoiceQuery
                ->orderByRaw('CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 0 ELSE 1 END', [$today])
                ->orderBy('due_date')
                ->take(8)
                ->get()
                ->map(function ($i) use ($today) {
                    $balance = (float) ($i->balance ?? max((float) $i->total - (float) $i->paid_amount, 0));
                    $dueDate = $i->due_date;
                    $isOverdue = $balance > 0 && $dueDate && $dueDate->toDateString() < $today;
                    $statusLabel = $isOverdue ? 'overdue' : ($i->status === 'partial' ? 'partial' : 'due');

                    return (object) [
                        'id' => $i->id,
                        'number' => $i->invoice_number,
                        'student_id' => $i->student_id,
                        'student_name' => $i->student?->full_name,
                        'classroom' => $i->student?->classroom?->name,
                        'total' => (float) $i->total,
                        'paid' => (float) $i->paid_amount,
                        'balance' => $balance,
                        'due_date' => $dueDate,
                        'status' => $i->status,
                        'status_label' => $statusLabel,
                        'is_overdue' => $isOverdue,
                    ];
                });

            $outstandingStudents = $insights->outstandingStudents($filters, 25);
        }

        $announcementQuery = Announcement::query();
        if (Schema::hasColumn('announcements', 'is_active')) {
            $announcementQuery->where('is_active', 1);
        } elseif (Schema::hasColumn('announcements', 'active')) {
            $announcementQuery->where('active', 1);
        }
        $announcements = $announcementQuery
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest()
            ->take(5)
            ->get();

        $mdStart = now()->format('m-d');
        $mdEnd = now()->addDays(14)->format('m-d');
        $birthdayStudentQuery = ($role === 'teacher' && $assignedClassroomIds !== null)
            ? (clone $studentBase)->whereNotNull('dob')
            : Student::query()->whereNotNull('dob');
        if ($mdStart <= $mdEnd) {
            $birthdayStudentQuery->whereRaw("DATE_FORMAT(dob, '%m-%d') BETWEEN ? AND ?", [$mdStart, $mdEnd]);
        } else {
            $birthdayStudentQuery->where(function ($q) use ($mdStart, $mdEnd) {
                $q->whereRaw("DATE_FORMAT(dob, '%m-%d') >= ?", [$mdStart])
                    ->orWhereRaw("DATE_FORMAT(dob, '%m-%d') <= ?", [$mdEnd]);
            });
        }

        $upcoming = collect()
            ->merge(
                Exam::select('name as title', 'starts_on as date')
                    ->whereNotNull('starts_on')
                    ->where('starts_on', '>=', now()->toDateString())
                    ->orderBy('starts_on')
                    ->take(8)
                    ->get()
                    ->map(fn ($e) => ['title' => 'Exam: '.$e->title, 'date' => $e->date, 'meta' => 'Exam'])
            )
            ->merge(
                $birthdayStudentQuery->limit(20)->get()->map(fn ($s) => [
                    'title' => $s->full_name ?: trim(($s->first_name ?? '').' '.($s->last_name ?? '')),
                    'date' => Carbon::parse($s->dob)->setYear(now()->year),
                    'meta' => 'Birthday',
                ])
            )
            ->sortBy('date')
            ->take(8)
            ->values();

        $transport = $needsTransport
            ? $insights->transportOps($today)
            : ['vehicles' => 0, 'trips_last_30' => 0, 'alerts' => [], 'today_trips' => collect()];
        $transport['trips_last_30'] = $transport['trips_total'] ?? 0;

        $behaviourCounts = $needsBehaviour
            ? $insights->behaviourSummary($filters['from'], $filters['to'])
            : ['positive' => 0, 'minor' => 0, 'moderate' => 0, 'major' => 0];
        $behaviour = $behaviourCounts + [
            'recent' => $needsBehaviour
                ? StudentBehaviour::with('student', 'behaviour')->latest()->take(5)->get()->map(function ($b) {
                    return (object) [
                        'student_name' => $b->student?->full_name ?? 'Student',
                        'behaviour' => $b->behaviour?->name ?? 'Behaviour',
                        'severity' => $b->severity,
                        'date' => $b->date,
                        'note' => $b->note,
                    ];
                })
                : collect(),
        ];

        $backupSettings = Schema::hasTable('backup_settings')
            ? DB::table('backup_settings')->latest('updated_at')->first()
            : null;
        $health = [
            'queue_ok' => true,
            'gateway_ok' => true,
            'last_backup_for_humans' => $backupSettings?->updated_at
                ? Carbon::parse($backupSettings->updated_at)->diffForHumans()
                : 'Unknown',
        ];

        $overview = [];
        $recentAdmissions = collect();
        $recentPayments = collect();
        $paymentMethods = collect();
        $activity = collect();
        $operationalAlerts = [];
        $widgetError = false;
        try {
            if ($role === 'admin') {
                $overview = $insights->overviewCounts();
                $recentAdmissions = $insights->recentAdmissions();
            }
            if ($needsFinance) {
                $recentPayments = $insights->recentPayments($filters['from'], $filters['to']);
                $paymentMethods = $insights->paymentMethodBreakdown($filters['from'], $filters['to']);
            }
            if (in_array($role, ['admin', 'finance'], true)) {
                $activity = $insights->recentActivity($needsFinance);
            }
            if ($role === 'admin') {
                $operationalAlerts = $insights->operationalAlerts($kpis, $transport, $pendingApprovals);
            }
        } catch (\Throwable $e) {
            $widgetError = true;
            \Log::warning('Dashboard extra widgets failed: '.$e->getMessage());
        }

        return [
            'years' => AcademicContext::years(),
            'terms' => AcademicContext::allTermsForSelect(),
            'classrooms' => in_array($role, ['teacher', 'transport', 'parent', 'student'], true)
                ? collect()
                : Classroom::orderBy('name')->get(),
            'streams' => in_array($role, ['teacher', 'transport', 'parent', 'student'], true)
                ? collect()
                : Stream::orderBy('name')->get(),
            'defaultYearId' => $defaultYearId,
            'defaultTermId' => $defaultTermId,
            'filters' => $filters,
            'kpis' => $kpis,
            'charts' => compact('attendance', 'enrolment', 'finance', 'exam'),
            'absenceAlerts' => $absenceAlerts,
            'invoices' => $invoices,
            'outstandingStudents' => $outstandingStudents,
            'announcements' => $announcements,
            'upcoming' => $upcoming,
            'transport' => $transport,
            'behaviour' => $behaviour,
            'health' => $health,
            'voteheadBreakdown' => $voteheadBreakdown,
            'weeklyPayments' => $weeklyPayments,
            'overview' => $overview,
            'recentAdmissions' => $recentAdmissions,
            'recentPayments' => $recentPayments,
            'paymentMethods' => $paymentMethods,
            'activity' => $activity,
            'operationalAlerts' => $operationalAlerts,
            'widgetError' => $widgetError,
            'pendingApprovalsDetail' => $pendingApprovals,
            'selectedTerm' => $selectedTerm,
            'selectedYear' => $yearId ? AcademicYear::find($yearId) : null,
            'schoolName' => function_exists('setting') ? setting('school_name', config('app.name')) : config('app.name'),
            'greeting' => (int) now()->format('G') < 12 ? 'Good morning' : ((int) now()->format('G') < 17 ? 'Good afternoon' : 'Good evening'),
            'role' => $role,
        ];
    }

    public function parentDashboard(Request $request)
    {
        $this->authorizeDashboard('parent.dashboard');
        $data = $this->buildDashboardData($request, 'parent');
        return view('dashboard.parent', $data + ['role' => 'parent']);
    }

    public function studentDashboard(Request $request)
    {
        $this->authorizeDashboard('student.dashboard');
        $data = $this->buildDashboardData($request, 'student');
        return view('dashboard.student', $data + ['role' => 'student']);
    }

    public function financeDashboard(Request $request)
    {
        $this->authorizeDashboard('finance.dashboard');
        $data = $this->buildDashboardData($request, 'finance');
        return view('dashboard.finance', $data + ['role' => 'finance']);
    }

    public function transportDashboard(Request $request)
    {
        $this->authorizeDashboard('transport.dashboard');
        $data = $this->buildDashboardData($request, 'transport');
        return view('dashboard.transport', $data + ['role' => 'transport']);
    }

    private function authorizeDashboard(string $routeName): void
    {
        if (! NavAccess::canDashboard($routeName)) {
            abort(403, 'You do not have access to this dashboard.');
        }
    }

    private function sumActiveInvoiceItems($invoiceIds, array $swimmingVoteheadIds): float
    {
        if ($invoiceIds instanceof \Illuminate\Support\Collection && $invoiceIds->isEmpty()) {
            return 0.0;
        }

        return (float) InvoiceItem::whereIn('invoice_id', $invoiceIds)
            ->where('status', 'active')
            ->when(! empty($swimmingVoteheadIds), fn ($q) => $q->whereNotIn('votehead_id', $swimmingVoteheadIds))
            ->selectRaw('COALESCE(SUM(amount - COALESCE(discount_amount, 0)), 0) as total')
            ->value('total');
    }

    /**
     * Count distinct students for an attendance query (avoids per-period rows inflating totals).
     */
    private function countDistinctAttendanceStudents($query, string $status): int
    {
        return (int) (clone $query)->where('status', $status)->distinct()->count('student_id');
    }

    private function scopeInvoicesToStudentFilters($query, array $filters)
    {
        return $query
            ->when(! empty($filters['classroom_id']), function ($q) use ($filters) {
                $q->whereHas('student', fn ($s) => $s->where('classroom_id', $filters['classroom_id']));
            })
            ->when(! empty($filters['stream_id']), function ($q) use ($filters) {
                $q->whereHas('student', fn ($s) => $s->where('stream_id', $filters['stream_id']));
            });
    }

    private function scopePaymentsToStudentFilters($query, array $filters)
    {
        return $query
            ->when(! empty($filters['classroom_id']) || ! empty($filters['stream_id']), function ($q) use ($filters) {
                $q->whereHas('student', function ($s) use ($filters) {
                    $s->when(! empty($filters['classroom_id']), fn ($qq) => $qq->where('classroom_id', $filters['classroom_id']))
                        ->when(! empty($filters['stream_id']), fn ($qq) => $qq->where('stream_id', $filters['stream_id']));
                });
            });
    }

    /**
     * Build teacher-specific dashboard data
     */
    private function buildTeacherSpecificData(Request $request): array
    {
        $user = auth()->user();
        $staff = \App\Models\Staff::where('user_id', $user->id)->first();
        
        if (!$staff) {
            return [
                'assignedClasses' => collect(),
                'assignedSubjects' => collect(),
                'assignments' => collect(),
                'totalStudents' => 0,
                'upcomingLessons' => collect(),
                'pendingAttendance' => collect(),
                'pendingMarks' => collect(),
                'pendingHomework' => collect(),
                'recentHomework' => collect(),
                'studentsByClass' => collect(),
                'streamAssignments' => collect(),
                'streamNamesByClass' => [],
                'upcomingAssessments' => collect(),
                'staff' => null,
            ];
        }

        $yearId = AcademicContext::resolveYearId(null);
        $currentYear = $yearId ? AcademicYear::find($yearId) : null;
        $currentTerm = $currentYear ? AcademicContext::defaultTermForYear($currentYear->id) : null;
        
        // Get assigned classes and subjects from classroom_subjects
        // Include rows scoped to current year/term OR legacy rows with NULL year/term, or year without term
        // (strict year+term filter alone often hides all rows when academic_year_id/term_id were not set)
        $assignmentsQuery = \App\Models\Academics\ClassroomSubject::query()
            ->where('staff_id', $staff->id)
            ->with(['classroom', 'subject', 'stream']);

        if ($currentYear && $currentTerm) {
            $assignmentsQuery->where(function ($q) use ($currentYear, $currentTerm) {
                $q->where(function ($q2) use ($currentYear, $currentTerm) {
                    $q2->where('academic_year_id', $currentYear->id)
                        ->where('term_id', $currentTerm->id);
                })->orWhere(function ($q2) {
                    $q2->whereNull('academic_year_id')->whereNull('term_id');
                })->orWhere(function ($q2) use ($currentYear) {
                    $q2->where('academic_year_id', $currentYear->id)->whereNull('term_id');
                });
            });
        } elseif ($currentYear) {
            $assignmentsQuery->where(function ($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear->id)->orWhereNull('academic_year_id');
            });
        }

        $assignments = $assignmentsQuery->get();

        // Also get classes directly assigned via classroom_teacher table
        $directClassrooms = \App\Models\Academics\Classroom::whereHas('teachers', function($q) use ($user) {
            $q->where('users.id', $user->id);
        })->get();

        // Get stream assignments with their classroom_id and stream_id
        $streamAssignments = collect(\Illuminate\Support\Facades\DB::table('stream_teacher')
            ->where('teacher_id', $user->id)
            ->whereNotNull('classroom_id')
            ->select('classroom_id', 'stream_id')
            ->get());
        
        $streamClassroomIds = $streamAssignments->pluck('classroom_id')->unique()->toArray();
        $streamClassrooms = \App\Models\Academics\Classroom::whereIn('id', $streamClassroomIds)->get();

        // Merge classes from all sources
        $classesFromAssignments = $assignments->pluck('classroom')->unique('id')->filter();
        $assignedClasses = $classesFromAssignments->merge($directClassrooms)->merge($streamClassrooms)->unique('id');
        
        // Get unique subjects
        $assignedSubjects = $assignments->pluck('subject')->unique('id')->filter();
        
        $classroomIds = $assignedClasses->pluck('id')->toArray();

        // Match teacher dashboard KPIs: same scope as User::applyTeacherStudentFilter (streams, not whole grade population)
        $totalStudentsQuery = Student::query();
        $user->applyTeacherStudentFilter($totalStudentsQuery);
        $totalStudents = $totalStudentsQuery->count();
        
        // Get upcoming lessons (today's schedule)
        $upcomingLessons = collect();
        $today = now();
        $dayName = strtolower($today->format('l')); // monday, tuesday, etc.
        
        // Try to get timetable data for today
        if ($currentYear && $currentTerm) {
            try {
                $teacherTimetable = \App\Services\TimetableService::generateForTeacher(
                    $staff->id, 
                    $currentYear->id, 
                    $currentTerm->id
                );
                
                if (isset($teacherTimetable['schedule'])) {
                    $upcomingLessons = collect($teacherTimetable['schedule'])
                        ->filter(function($lesson) use ($dayName) {
                            return strtolower($lesson['day']) === $dayName;
                        })
                        ->sortBy('period')
                        ->take(5);
                }
            } catch (\Exception $e) {
                // If timetable service fails, continue without it
            }
        }
        
        // Get pending attendance (classes not marked today)
        $pendingAttendance = $assignedClasses;
        if ($assignedClasses->isNotEmpty()) {
            $markedStudentIds = Attendance::whereDate('date', $today)->distinct()->pluck('student_id');
            $markedIds = $markedStudentIds->isEmpty()
                ? collect()
                : Student::whereIn('id', $markedStudentIds)->whereIn('classroom_id', $classroomIds)->distinct()->pluck('classroom_id');
            $pendingAttendance = $assignedClasses->reject(fn ($classroom) => $markedIds->contains($classroom->id))->values();
        }
        
        // Get pending marks (exams that need marks entry)
        // Get exams for assigned classes that are open and need marks
        $pendingMarks = \App\Models\Academics\Exam::where('status', 'open')
            ->whereHas('marks', function($q) use ($classroomIds) {
                $q->whereHas('student', function($q2) use ($classroomIds) {
                    $q2->whereIn('classroom_id', $classroomIds);
                })
                ->where(function($q3) {
                    $q3->whereNull('score_raw')
                       ->whereNull('score_moderated')
                       ->where(function($q4) {
                           $q4->whereNull('opener_score')
                              ->whereNull('midterm_score')
                              ->whereNull('endterm_score');
                       });
                });
            })
            ->with(['academicYear', 'term'])
            ->latest()
            ->take(5)
            ->get();
        
        // Get pending homework (submissions to review)
        $pendingHomework = \App\Models\Academics\Homework::whereIn('classroom_id', $classroomIds)
            ->whereHas('homeworkDiary', function($q) {
                $q->where('status', 'submitted')
                  ->whereNull('score');
            })
            ->with(['subject', 'classroom'])
            ->latest()
            ->take(5)
            ->get();
        
        // Get recent homework assignments
        $recentHomework = \App\Models\Academics\Homework::whereIn('classroom_id', $classroomIds)
            ->with(['subject', 'classroom'])
            ->latest()
            ->take(5)
            ->get();
        
        // Same teaching scope as KPIs / totalStudents (streams, not entire grade)
        $studentsByClassQuery = Student::query()->with('classroom');
        $user->applyTeacherStudentFilter($studentsByClassQuery);
        $studentsByClass = $studentsByClassQuery->get()->groupBy('classroom_id');

        $streamNamesByClass = [];
        if ($streamAssignments->isNotEmpty()) {
            $namedStreams = Stream::whereIn('id', $streamAssignments->pluck('stream_id')->filter()->unique())->pluck('name', 'id');
            foreach ($streamAssignments as $assignment) {
                $name = $namedStreams[$assignment->stream_id] ?? null;
                if ($name) {
                    $streamNamesByClass[$assignment->classroom_id][] = $name;
                }
            }
            $streamNamesByClass = array_map(fn ($names) => array_values(array_unique($names)), $streamNamesByClass);
        }

        $upcomingAssessments = collect();
        if (! empty($classroomIds)) {
            $upcomingAssessments = Exam::query()
                ->whereIn('classroom_id', $classroomIds)
                ->whereNotNull('starts_on')
                ->whereDate('starts_on', '>=', $today->toDateString())
                ->orderBy('starts_on')
                ->take(6)
                ->get();
        }
        
        return [
            'assignedClasses' => $assignedClasses,
            'assignedSubjects' => $assignedSubjects,
            'assignments' => $assignments,
            'totalStudents' => $totalStudents,
            'upcomingLessons' => $upcomingLessons,
            'pendingAttendance' => $pendingAttendance,
            'pendingMarks' => $pendingMarks,
            'pendingHomework' => $pendingHomework,
            'recentHomework' => $recentHomework,
            'studentsByClass' => $studentsByClass,
            'streamAssignments' => $streamAssignments,
            'streamNamesByClass' => $streamNamesByClass,
            'upcomingAssessments' => $upcomingAssessments,
            'staff' => $staff,
        ];
    }

    private function buildSupervisorSpecificData(Request $request): array
    {
        $user = auth()->user();
        $staff = \App\Models\Staff::where('user_id', $user->id)->first();
        
        if (!$staff) {
            return [
                'subordinates' => collect(),
                'subordinateClassrooms' => collect(),
                'pendingLessonPlans' => collect(),
                'pendingLeaveRequests' => collect(),
                'recentAttendance' => collect(),
                'subordinateStats' => [],
            ];
        }

        // Get subordinates
        $subordinates = $staff->subordinates()->with('user')->get();
        $subordinateIds = $subordinates->pluck('id')->toArray();
        
        // Get classrooms assigned to subordinates
        $subordinateClassroomIds = get_subordinate_classroom_ids();
        $subordinateClassrooms = \App\Models\Academics\Classroom::whereIn('id', $subordinateClassroomIds)->get();
        
        // Get pending lesson plans from subordinates
        $pendingLessonPlans = \App\Models\Academics\LessonPlan::whereIn('classroom_id', $subordinateClassroomIds)
            ->whereNull('approved_at')
            ->with(['classroom', 'subject', 'creator'])
            ->latest('planned_date')
            ->take(10)
            ->get();
        
        // Get pending leave requests from subordinates
        $pendingLeaveRequests = \App\Models\LeaveRequest::whereIn('staff_id', $subordinateIds)
            ->where('status', 'pending')
            ->with(['staff', 'leaveType'])
            ->latest()
            ->take(10)
            ->get();
        
        // Get recent attendance records for subordinates' classes
        $recentAttendance = \App\Models\Attendance::whereHas('student', function($q) use ($subordinateClassroomIds) {
                $q->whereIn('classroom_id', $subordinateClassroomIds);
            })
            ->whereDate('date', '>=', now()->subDays(7))
            ->with(['student.classroom'])
            ->latest('date')
            ->take(20)
            ->get();
        
        // Calculate stats for subordinates
        $subordinateStats = [
            'total' => $subordinates->count(),
            'active' => $subordinates->where('status', 'active')->count(),
            'totalClasses' => $subordinateClassrooms->count(),
            'pendingApprovals' => $pendingLessonPlans->count(),
            'pendingLeaves' => $pendingLeaveRequests->count(),
        ];
        
        // Get recent activity (lesson plans, exams, etc.)
        $recentLessonPlans = \App\Models\Academics\LessonPlan::whereIn('classroom_id', $subordinateClassroomIds)
            ->with(['classroom', 'subject', 'creator'])
            ->latest('created_at')
            ->take(5)
            ->get();
        
        $recentExams = \App\Models\Academics\Exam::whereIn('classroom_id', $subordinateClassroomIds)
            ->with(['classroom', 'subject', 'creator'])
            ->latest('created_at')
            ->take(5)
            ->get();

        return [
            'subordinates' => $subordinates,
            'subordinateClassrooms' => $subordinateClassrooms,
            'pendingLessonPlans' => $pendingLessonPlans,
            'pendingLeaveRequests' => $pendingLeaveRequests,
            'recentAttendance' => $recentAttendance,
            'subordinateStats' => $subordinateStats,
            'recentLessonPlans' => $recentLessonPlans,
            'recentExams' => $recentExams,
            'staff' => $staff,
        ];
    }

    private function resolveDefaultAcademicYearId(): ?int
    {
        $active = AcademicYear::where('is_active', true)->orderByDesc('id')->first();
        if ($active) {
            return $active->id;
        }

        return AcademicYear::latest('id')->value('id');
    }

    /**
     * Prefer flagged current term, else the term whose dates contain today, else first/last term of the year.
     */
    private function resolveDefaultTermForYear(int $yearId): ?Term
    {
        $terms = Term::where('academic_year_id', $yearId)->orderBy('opening_date')->orderBy('id')->get();
        if ($terms->isEmpty()) {
            return null;
        }

        $byFlag = $terms->firstWhere('is_current', true);
        if ($byFlag) {
            return $byFlag;
        }

        $today = now()->toDateString();
        foreach ($terms as $t) {
            if ($t->opening_date && $t->closing_date) {
                $open = $t->opening_date->toDateString();
                $close = $t->closing_date->toDateString();
                if ($today >= $open && $today <= $close) {
                    return $t;
                }
            }
        }

        $first = $terms->first();
        $last = $terms->last();
        if ($first?->opening_date && $today < $first->opening_date->toDateString()) {
            return $first;
        }
        if ($last?->closing_date && $today > $last->closing_date->toDateString()) {
            return $last;
        }

        return $last;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function defaultDateRangeForTerm(?Term $term): array
    {
        if ($term && $term->opening_date && $term->closing_date) {
            $from = $term->opening_date->toDateString();
            $close = $term->closing_date->toDateString();
            $today = now()->toDateString();
            $to = ($today >= $from && $today <= $close) ? $today : $close;

            return [$from, $to];
        }

        return [now()->subDays(30)->toDateString(), now()->toDateString()];
    }
}
