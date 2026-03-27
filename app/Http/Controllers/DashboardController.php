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

class DashboardController extends Controller
{
    public function adminDashboard(Request $request)
    {
        try {
            $data = $this->buildDashboardData($request, 'admin', null, null);
            return view('dashboard.admin', $data);
        } catch (\Exception $e) {
            \Log::error('Admin dashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);
            return redirect()->route('home')->with('error', 'Unable to load dashboard. Please try again.');
        }
    }

    public function teacherDashboard(Request $request)
    {
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
        if (!is_supervisor() || auth()->user()->hasAnyRole(['Admin', 'Super Admin'])) {
            abort(403, 'Access denied. This dashboard is for supervisors only.');
        }
        
        $data = $this->buildDashboardData($request, 'supervisor');
        $supervisorData = $this->buildSupervisorSpecificData($request);
        return view('dashboard.supervisor', $data + $supervisorData + ['role' => 'supervisor']);
    }

    private function buildDashboardData(Request $request, string $role = 'admin', array $assignedClassroomIds = null, array $streamAssignments = null): array
    {
        // ---- Filters (year-scoped terms; defaults = active year + current term + term date range)
        $defaultYearId = $this->resolveDefaultAcademicYearId();
        $yearId = (int) ($request->get('year_id') ?: $defaultYearId ?: 0);

        $termsForYear = $yearId > 0
            ? Term::where('academic_year_id', $yearId)->orderBy('opening_date')->orderBy('id')->get()
            : collect();

        $defaultTerm = $yearId > 0 ? $this->resolveDefaultTermForYear($yearId) : null;
        $defaultTermId = $defaultTerm?->id ?? 0;

        $requestedTermId = (int) ($request->get('term_id') ?: 0);
        $termId = $requestedTermId;
        if ($termId && ! $termsForYear->contains('id', $termId)) {
            $termId = 0;
        }
        if (! $termId) {
            $termId = $defaultTermId;
        }

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
        ];
        $today = now()->toDateString();

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

        $swimmingVoteheadIds = \App\Models\Votehead::where(function ($q) {
            $q->where('name', 'like', '%swim%')->orWhere('code', 'like', '%SWIM%');
        })->pluck('id')->toArray();

        $totalInvoiced = 0.0;
        $feesCollected = 0.0;
        $feesOutstanding = 0.0;
        $financeScope = 'date_range'; // 'date_range' or 'term'

        // When a term is selected, use term-based totals so KPIs match Fee Balances and Fees Comparison
        if ($filters['term_id'] > 0) {
            $term = Term::find($filters['term_id']);
            if ($term) {
                $termStart = $term->opening_date ? $term->opening_date->toDateString() : $filters['from'];
                $termEnd = $term->closing_date ? $term->closing_date->toDateString() : $filters['to'];

                $termInvoiceIds = Invoice::where('term_id', $filters['term_id'])
                    ->whereNull('reversed_at')
                    ->whereNull('deleted_at')
                    ->pluck('id');

                if ($termInvoiceIds->isNotEmpty()) {
                    $totalInvoiced = (float) InvoiceItem::whereIn('invoice_id', $termInvoiceIds)
                        ->where('status', 'active')
                        ->when(!empty($swimmingVoteheadIds), fn ($q) => $q->whereNotIn('votehead_id', $swimmingVoteheadIds))
                        ->get()
                        ->sum(fn ($item) => (float) $item->amount - (float) ($item->discount_amount ?? 0));

                    // Use allocations to this term's invoices (matches Fee Balances and Fees Comparison)
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

                    // Only include invoices that are due (exclude not-yet-due, e.g. next term)
                    $feesOutstanding = (float) Invoice::whereIn('id', $termInvoiceIds)
                        ->whereIn('status', ['unpaid', 'partial'])
                        ->where(function ($q) {
                            $q->whereNull('due_date')
                              ->orWhereDate('due_date', '<=', now()->toDateString());
                        })
                        ->sum('balance');
                    $financeScope = 'term';
                }
            }
        }

        if ($financeScope === 'date_range') {
            $feesCollected = (float) Payment::whereBetween('payment_date', [$filters['from'], $filters['to']])
                ->where('reversed', false)
                ->where($excludeSwimmingPayments)
                ->sum('amount');

            $totalInvoiced = (float) \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($filters) {
                $q->whereBetween('created_at', [$filters['from'], $filters['to']])
                    ->whereNull('reversed_at');
            })
                ->where('status', 'active')
                ->when(!empty($swimmingVoteheadIds), fn ($q) => $q->whereNotIn('votehead_id', $swimmingVoteheadIds))
                ->get()
                ->sum(fn ($item) => (float) $item->amount - (float) ($item->discount_amount ?? 0));

            $feesOutstanding = \App\Services\StudentBalanceService::getTotalOutstandingFeesExcludingSwimming(true);
        }

        // Votehead breakdown - total invoiced per votehead
        $voteheadBreakdown = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($filters) {
                $q->whereBetween('created_at', [$filters['from'], $filters['to']])
                  ->whereNull('reversed_at');
            })
            ->where('status', 'active')
            ->with('votehead')
            ->select('votehead_id', DB::raw('SUM(amount - COALESCE(discount_amount, 0)) as total_amount'))
            ->groupBy('votehead_id')
            ->get()
            ->map(function($item) {
                return [
                    'votehead_id' => $item->votehead_id,
                    'votehead_name' => $item->votehead->name ?? 'Unknown',
                    'votehead_code' => $item->votehead->code ?? '',
                    'total_amount' => (float)$item->total_amount,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        // Weekly payment categorization for the current term
        $weeklyPayments = [];
        if ($filters['term_id']) {
            $term = Term::find($filters['term_id']);
            if ($term) {
                // Get term start and end dates
                $termStart = $term->opening_date ? $term->opening_date->toDateString() : $filters['from'];
                $termEnd = $term->closing_date ? $term->closing_date->toDateString() : $filters['to'];
                
                // Group payments by week within the term (exclude swimming)
                $payments = Payment::whereBetween('payment_date', [$termStart, $termEnd])
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
                    ->map(function($item) {
                        return [
                            'week' => $item->week,
                            'week_label' => Carbon::parse($item->week_start)->format('M d') . ' - ' . 
                                          Carbon::parse($item->week_start)->addDays(6)->format('M d, Y'),
                            'total_amount' => (float)$item->total_amount,
                            'payment_count' => (int)$item->payment_count,
                        ];
                    });
                
                $weeklyPayments = $payments->toArray();
            }
        }

        $teachersOnLeave = class_exists('\App\Models\Staff\Leave')
            ? \App\Models\Staff\Leave::whereDate('start', '<=', $today)->whereDate('end', '>=', $today)->count()
            : 0;

        $kpis = [
            'students'          => $totalStudents,
            'students_delta'    => 0.0,
            'present_today'     => $presentToday,
            'absent_today'      => $absentToday,
            'attendance_delta'  => 0.0,
            'total_invoiced'    => in_array($role, ['admin', 'finance']) ? $totalInvoiced : 0,
            'fees_collected'    => in_array($role, ['admin', 'finance']) ? $feesCollected : 0,
            'fees_outstanding'  => in_array($role, ['admin', 'finance']) ? $feesOutstanding : 0,
            'finance_scope'     => $financeScope ?? 'date_range',
            'fees_delta'        => 0.0,
            'teachers_on_leave' => $teachersOnLeave,
        ];

        // ---- Charts
        // 30-day attendance trend
        $days = collect(range(0, 29))->map(fn($i) => now()->subDays(29 - $i)->startOfDay());
        
        $attendancePresent = [];
        $attendanceAbsent = [];
        
        foreach ($days as $day) {
            $dayQuery = Attendance::whereDate('date', $day);
            
            if ($role === 'teacher' && $assignedClassroomIds !== null) {
                $dayQuery->whereHas('student', function ($q) {
                    auth()->user()->applyTeacherStudentFilter($q);
                });
            }

            if (! SchoolDay::isSchoolDay($day->toDateString())) {
                $attendancePresent[] = 0;
                $attendanceAbsent[] = 0;
            } else {
                $attendancePresent[] = $this->countDistinctAttendanceStudents($dayQuery, 'present');
                $attendanceAbsent[] = $this->countDistinctAttendanceStudents($dayQuery, 'absent');
            }
        }
        
        $attendance = [
            'labels'  => $days->map->format('d M')->toArray(),
            'present' => $attendancePresent,
            'absent'  => $attendanceAbsent,
        ];

        // 12-month enrolment trend (only for admin, teachers don't need this)
        $months = collect(range(0, 11))->map(fn($i) => now()->subMonths(11 - $i)->startOfMonth());
        $enrolment = [
            'labels' => $months->map->format('M Y')->toArray(),
            'counts' => $role === 'teacher' 
                ? array_fill(0, 12, 0) // Teachers don't see enrolment trends
                : $months->map(fn($m) => Student::whereDate('created_at', '<=', $m->copy()->endOfMonth())->count())->toArray(),
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

        $examAggQuery = DB::table($examMarksTable)
            ->join($subjectsTable, "$subjectsTable.id", '=', "$examMarksTable.subject_id")
            ->join($examsTable,     "$examsTable.id",     '=', "$examMarksTable.exam_id")
            ->whereBetween("$examsTable.$examDateCol", [$filters['from'], $filters['to']]);
        
        // For teachers, filter by assigned classrooms/streams
        if ($role === 'teacher' && $assignedClassroomIds !== null) {
            if (!empty($streamAssignments)) {
                $streamClassroomIds = array_column($streamAssignments, 'classroom_id');
                $streamIds = array_column($streamAssignments, 'stream_id');
                
                $examAggQuery->whereIn("$examsTable.classroom_id", $streamClassroomIds)
                    ->whereIn("$examMarksTable.student_id", function($q) use ($streamAssignments) {
                        $q->select('id')->from('students')->where(function($subQ) use ($streamAssignments) {
                            foreach ($streamAssignments as $assignment) {
                                $subQ->orWhere(function($s) use ($assignment) {
                                    $s->where('classroom_id', $assignment->classroom_id)
                                      ->where('stream_id', $assignment->stream_id);
                                });
                            }
                        });
                    });
            } else {
                $examAggQuery->whereIn("$examsTable.classroom_id", $assignedClassroomIds);
            }
        } else {
            $examAggQuery->when($filters['classroom_id'], fn($q) => $q->where("$examsTable.classroom_id", $filters['classroom_id']))
                         ->when($filters['stream_id'], fn($q) => $q->whereIn("$examMarksTable.student_id", 
                             function($subQ) use ($filters) {
                                 $subQ->select('id')->from('students')->where('stream_id', $filters['stream_id']);
                             }));
        }
        
        $examAgg = $examAggQuery
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
        $absenceAlertsQuery = Attendance::selectRaw('student_id, COUNT(*) as days_absent')
            ->where('status', 'absent')
            ->whereBetween('date', [now()->subDays(7)->toDateString(), now()->toDateString()]);
        
        if ($role === 'teacher' && $assignedClassroomIds !== null) {
            $absenceAlertsQuery->whereHas('student', function ($q) {
                auth()->user()->applyTeacherStudentFilter($q);
            });
        }
        
        $absenceAlerts = $absenceAlertsQuery
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

                    // Try common due-date columns, fall back to null
                    $dueDate = $i->due_date
                        ?? $i->due_at
                        ?? $i->due_on
                        ?? null;

                    $isOverdue = false;
                    if ($dueDate) {
                        try {
                            $isOverdue = $balance > 0 && \Carbon\Carbon::parse($dueDate)->isPast();
                        } catch (\Throwable $e) {
                            $isOverdue = false;
                        }
                    }

                    return (object)[
                        'id'           => $i->id,
                        'number'       => $i->invoice_number,
                        'student_name' => trim(($i->student->first_name ?? '') . ' ' . ($i->student->last_name ?? '')),
                        'total'        => (float)$i->total,
                        'paid'         => $paid,
                        'balance'      => $balance,
                        'status'       => $i->status,  // unpaid | partial
                        'is_overdue'   => $isOverdue,  // <-- added for the Blade
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
        // Teachers: birthdays only for students in assigned classrooms/streams (same scope as KPIs)
        $birthdayStudentQuery = ($role === 'teacher' && $assignedClassroomIds !== null)
            ? (clone $studentBase)->whereNotNull('dob')
            : Student::query()->whereNotNull('dob');

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
                $birthdayStudentQuery->get()
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
            'years'      => AcademicYear::orderByDesc('id')->get(),
            'terms'      => $termsForYear,
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
            'voteheadBreakdown' => $voteheadBreakdown,
            'weeklyPayments' => $weeklyPayments,

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

    /**
     * Count distinct students for an attendance query (avoids per-period rows inflating totals).
     */
    private function countDistinctAttendanceStudents($query, string $status): int
    {
        // Pluck + unique is reliable across DB drivers; Eloquent aggregate + selectRaw can mis-count with soft deletes / selects
        return (int) (clone $query)
            ->where('status', $status)
            ->pluck('student_id')
            ->unique()
            ->count();
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
                'totalStudents' => 0,
                'upcomingLessons' => collect(),
                'pendingAttendance' => collect(),
                'pendingMarks' => collect(),
                'pendingHomework' => collect(),
            ];
        }

        $yearId = $this->resolveDefaultAcademicYearId();
        $currentYear = $yearId ? AcademicYear::find($yearId) : null;
        $currentTerm = $currentYear ? $this->resolveDefaultTermForYear($currentYear->id) : null;
        
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
        $pendingAttendance = $assignedClasses->filter(function($classroom) use ($today) {
            $marked = Attendance::whereDate('date', $today)
                ->whereHas('student', fn($q) => $q->where('classroom_id', $classroom->id))
                ->exists();
            return !$marked;
        });
        
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
            $to = min($close, now()->toDateString());

            return [$from, $to];
        }

        return [now()->subDays(30)->toDateString(), now()->toDateString()];
    }
}
