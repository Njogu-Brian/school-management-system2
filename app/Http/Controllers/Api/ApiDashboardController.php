<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SchoolDay;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Term;
use App\Models\Academics\ExamMark;
use App\Models\LeaveRequest;
use App\Services\StudentBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiDashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user->load('roles', 'staff');

        if ($user->hasAnyRole(['Parent', 'Guardian'])) {
            return response()->json([
                'success' => true,
                'data' => $this->parentDashboard($user),
            ]);
        }

        if ($user->hasTeacherLikeRole()) {
            return response()->json([
                'success' => true,
                'data' => $this->teacherDashboard($user),
            ]);
        }

        if ($user->hasRole('Student')) {
            return response()->json([
                'success' => true,
                'data' => $this->studentDashboard($user),
            ]);
        }

        if ($user->hasAnyRole(['Accountant', 'Finance'])) {
            return response()->json([
                'success' => true,
                'data' => $this->financeDashboard(),
            ]);
        }

        // Admin, Secretary, Super Admin, etc.
        $yearId = $request->query('academic_year_id') !== null ? (int) $request->query('academic_year_id') : null;
        $termId = $request->query('term_id') !== null ? (int) $request->query('term_id') : null;

        return response()->json([
            'success' => true,
            'data' => $this->adminDashboard($yearId, $termId),
        ]);
    }

    protected function parentDashboard($user): array
    {
        $ids = $user->accessibleStudentIds();
        $totalBalance = 0.0;
        foreach ($ids as $sid) {
            $s = Student::find($sid);
            if ($s) {
                $totalBalance += (float) StudentBalanceService::getTotalOutstandingBalance($s);
            }
        }

        $line = $this->parentAttendanceLine($ids);
        $bar = $this->childrenFeeBalances($ids);

        return [
            'role' => 'parent',
            'children_count' => count($ids),
            'total_fee_balance' => round($totalBalance, 2),
            'charts' => [
                'line' => $line,
                'bar' => $bar,
            ],
        ];
    }

    /**
     * Last 6 weeks: average % present across all children (weekdays only).
     */
    protected function parentAttendanceLine(array $studentIds): array
    {
        if (empty($studentIds)) {
            return ['labels' => [], 'values' => []];
        }

        $labels = [];
        $values = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = Carbon::now()->startOfWeek()->subWeeks($i);
            $end = (clone $start)->endOfWeek();
            $labels[] = $start->format('M j');

            $weekRows = Attendance::query()
                ->whereIn('student_id', $studentIds)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get(['date', 'status']);

            $byDay = $weekRows->groupBy(function ($row) {
                return $row->date;
            });
            $rates = [];
            foreach ($byDay as $dayRows) {
                $total = $dayRows->count();
                $present = $dayRows->where('status', 'present')->count();
                if ($total > 0) {
                    $rates[] = round(100 * $present / $total, 1);
                }
            }
            $values[] = count($rates) > 0 ? round(array_sum($rates) / count($rates), 1) : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Per-child outstanding balance (up to 6 bars).
     */
    protected function childrenFeeBalances(array $studentIds): array
    {
        if (empty($studentIds)) {
            return ['labels' => [], 'values' => []];
        }

        $labels = [];
        $values = [];
        foreach (array_slice($studentIds, 0, 6) as $sid) {
            $s = Student::find($sid);
            if (! $s) {
                continue;
            }
            $bal = (float) StudentBalanceService::getTotalOutstandingBalance($s);
            $labels[] = $s->first_name ?: ('#'.$sid);
            $values[] = round($bal, 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function teacherDashboard($user): array
    {
        $ids = $user->getAssignedClassroomIds();
        $myClasses = count(array_unique($ids));

        $q = Student::query()->where('archive', 0)->where('is_alumni', false);
        $user->applyTeacherStudentFilter($q);
        $totalStudents = (int) $q->count();

        $pendingMarks = 0;
        if ($user->staff) {
            $pendingMarks = (int) ExamMark::query()
                ->where('teacher_id', $user->staff->id)
                ->where(function ($q) {
                    $q->whereNull('score_raw')->whereNull('score_moderated')
                        ->whereNull('opener_score')->whereNull('midterm_score')->whereNull('endterm_score');
                })
                ->count();
        }

        $line = $this->teacherAttendanceLine($user);
        $bar = $this->teacherClassesBar($user);

        return [
            'role' => 'teacher',
            'my_classes' => $myClasses,
            'total_students' => $totalStudents,
            'pending_marks' => $pendingMarks,
            'classes_today' => $this->estimateTodaysClasses($user),
            'charts' => [
                'line' => $line,
                'bar' => $bar,
            ],
        ];
    }

    /**
     * Last 5 school days: count of attendance records marked by this teacher (proxy for activity).
     */
    protected function teacherAttendanceLine($user): array
    {
        $streamIds = $user->getEffectiveStreamIds();
        if (empty($streamIds)) {
            return ['labels' => [], 'values' => []];
        }

        $labels = [];
        $values = [];
        for ($i = 4; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            if (! SchoolDay::isSchoolDay($d->toDateString())) {
                continue;
            }
            $labels[] = $d->format('D');
            $count = Attendance::query()
                ->whereDate('date', $d->toDateString())
                ->whereHas('student', fn ($q) => $q->whereIn('stream_id', $streamIds))
                ->count();
            $values[] = $count;
        }

        if (empty($labels)) {
            return ['labels' => [], 'values' => []];
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Student count per assigned classroom (top 6).
     */
    protected function teacherClassesBar($user): array
    {
        $classroomIds = array_slice($user->getDashboardClassroomIds(), 0, 6);
        if (empty($classroomIds)) {
            return ['labels' => [], 'values' => []];
        }

        $labels = [];
        $values = [];
        foreach ($classroomIds as $cid) {
            $c = \App\Models\Academics\Classroom::find($cid);
            $name = $c ? ($c->name ?? 'Class '.$cid) : (string) $cid;
            $labels[] = \Illuminate\Support\Str::limit($name, 8);
            $sq = Student::query()
                ->where('classroom_id', $cid)
                ->where('archive', 0)
                ->where('is_alumni', false);
            $user->applyTeacherStudentFilter($sq);
            $values[] = (int) $sq->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function estimateTodaysClasses($user): int
    {
        $streamIds = $user->getEffectiveStreamIds();
        if (empty($streamIds)) {
            return 0;
        }
        $today = Carbon::today()->toDateString();
        if (! SchoolDay::isSchoolDay($today)) {
            return 0;
        }

        return count($streamIds);
    }

    protected function studentDashboard($user): array
    {
        $student = null;
        if ($this->studentsTableHasUserId()) {
            $student = Student::query()->where('user_id', $user->id)->first();
        }
        if (! $student) {
            return [
                'role' => 'student',
                'student_id' => null,
                'class_name' => null,
                'attendance_pct' => null,
                'fee_balance' => null,
                'pending_assignments' => 0,
                'charts' => [
                    'line' => ['labels' => [], 'values' => []],
                    'bar' => ['labels' => [], 'values' => []],
                ],
            ];
        }

        $attPct = $this->studentAttendancePercent($student);
        $feeBal = (float) StudentBalanceService::getTotalOutstandingBalance($student);
        $className = $student->stream?->name ?? $student->classroom?->name ?? '';

        $line = $this->studentAttendanceHistory($student);
        $bar = ['labels' => ['Term 1', 'Term 2', 'Term 3'], 'values' => [0, 0, 0]];

        return [
            'role' => 'student',
            'student_id' => $student->id,
            'class_name' => $className,
            'attendance_pct' => $attPct,
            'fee_balance' => round($feeBal, 2),
            'pending_assignments' => 0,
            'charts' => [
                'line' => $line,
                'bar' => $bar,
            ],
        ];
    }

    protected function studentAttendancePercent(Student $student): ?float
    {
        $rows = Attendance::query()
            ->where('student_id', $student->id)
            ->where('date', '>=', Carbon::now()->subMonths(3)->toDateString())
            ->get(['status']);
        if ($rows->isEmpty()) {
            return null;
        }
        $total = $rows->count();
        $present = $rows->where('status', 'present')->count();

        return $total > 0 ? round(100 * $present / $total, 1) : null;
    }

    protected function studentAttendanceHistory(Student $student): array
    {
        $labels = [];
        $values = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = Carbon::now()->startOfWeek()->subWeeks($i);
            $end = (clone $start)->endOfWeek();
            $labels[] = $start->format('M j');
            $week = Attendance::query()
                ->where('student_id', $student->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get(['status']);
            $total = $week->count();
            $present = $week->where('status', 'present')->count();
            $values[] = $total > 0 ? round(100 * $present / $total, 1) : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function financeDashboard(): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::today()->startOfWeek();
        $monthStart = Carbon::today()->startOfMonth();

        $base = Payment::query()->where(function ($q) {
            $q->whereNull('reversed')->orWhere('reversed', false);
        });

        $sumDay = (clone $base)->whereDate('payment_date', $today->toDateString())->sum('amount');
        $sumWeek = (clone $base)->where('payment_date', '>=', $weekStart)->sum('amount');
        $sumMonth = (clone $base)->where('payment_date', '>=', $monthStart)->sum('amount');

        $pendingInvoices = Invoice::query()
            ->where('balance', '>', 0)
            ->where(function ($q) {
                $q->whereNull('reversed_at');
            })
            ->count();

        $overdue = Invoice::query()
            ->where('balance', '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->toDateString())
            ->count();

        $line = $this->financeCollectionLine();
        $bar = $this->financeMethodBar();

        $totalFees = (float) (clone $base)->sum('amount');

        return [
            'role' => 'finance',
            'collections_today' => round((float) $sumDay, 2),
            'collections_week' => round((float) $sumWeek, 2),
            'collections_month' => round((float) $sumMonth, 2),
            'pending_invoices' => $pendingInvoices,
            'overdue_invoices' => $overdue,
            'fees_collected' => round($totalFees, 2),
            'charts' => [
                'line' => $line,
                'bar' => $bar,
            ],
        ];
    }

    protected function financeCollectionLine(): array
    {
        $labels = [];
        $values = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $labels[] = $d->format('D');
            $sum = Payment::query()
                ->where(function ($q) {
                    $q->whereNull('reversed')->orWhere('reversed', false);
                })
                ->whereDate('payment_date', $d->toDateString())
                ->sum('amount');
            $values[] = round((float) $sum, 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function financeMethodBar(): array
    {
        $rows = Payment::query()
            ->where(function ($q) {
                $q->whereNull('reversed')->orWhere('reversed', false);
            })
            ->where('payment_date', '>=', Carbon::today()->subDays(30))
            ->selectRaw('COALESCE(payment_channel, payment_method, "other") as ch, SUM(amount) as total')
            ->groupBy('ch')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = $row->ch ?: 'other';
            $values[] = round((float) $row->total, 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function adminDashboard(?int $yearId = null, ?int $termId = null): array
    {
        $today = now()->toDateString();

        // Resolve window for scoped aggregates. When a term is given we use its
        // opening_date..closing_date; otherwise the whole active year; otherwise all time.
        [$windowStart, $windowEnd, $resolvedYearId, $resolvedTermId] = $this->resolveWindow($yearId, $termId);

        $totalStudents = Student::where('archive', 0)->where('is_alumni', false)->count();
        $totalStaff = Staff::count();
        $presentToday = SchoolDay::isSchoolDay($today)
            ? Attendance::whereDate('date', $today)->where('status', 'present')->count()
            : 0;

        $paymentsQuery = Payment::query()->where(function ($q) {
            $q->whereNull('reversed')->orWhere('reversed', false);
        });
        if ($windowStart && $windowEnd) {
            $paymentsQuery->whereBetween('payment_date', [$windowStart, $windowEnd]);
        }
        $feesCollected = (float) $paymentsQuery->sum('amount');

        $invoiceQuery = Invoice::query();
        if ($resolvedTermId) {
            $invoiceQuery->where('term_id', $resolvedTermId);
        } elseif ($resolvedYearId) {
            $invoiceQuery->where('academic_year_id', $resolvedYearId);
        }
        $totalInvoiced = (float) $invoiceQuery->sum('total');
        $totalBalance = (float) (clone $invoiceQuery)->sum('balance');

        // Filter options for the dropdowns on the UI.
        $years = AcademicYear::orderByDesc('year')->get(['id', 'year', 'is_active']);
        $terms = Term::query()
            ->when($resolvedYearId, fn ($q) => $q->where('academic_year_id', $resolvedYearId))
            ->orderBy('opening_date')
            ->get(['id', 'name', 'academic_year_id', 'opening_date', 'closing_date', 'is_current']);

        return [
            'role' => 'admin',
            'total_students' => $totalStudents,
            'total_staff' => $totalStaff,
            'present_today' => $presentToday,
            'fees_collected' => round($feesCollected, 2),
            'total_invoiced' => round($totalInvoiced, 2),
            'total_payments' => round($feesCollected, 2),
            'outstanding_balance' => round($totalBalance, 2),
            'filters' => [
                'academic_year_id' => $resolvedYearId,
                'term_id' => $resolvedTermId,
                'available_years' => $years,
                'available_terms' => $terms,
            ],
            'charts' => [
                'enrollment' => $this->adminEnrolmentByTerm(),
                'payments' => $this->adminPaymentsByTerm(),
                'invoices' => $this->adminInvoicesByTerm(),
            ],
            'birthdays' => $this->adminUpcomingBirthdays(),
            'teachers_on_leave' => $this->adminTeachersOnLeave(),
        ];
    }

    /**
     * Resolve the date window for a given year/term filter, returning
     * [startCarbon|null, endCarbon|null, yearId|null, termId|null].
     */
    protected function resolveWindow(?int $yearId, ?int $termId): array
    {
        $resolvedTermId = null;
        $resolvedYearId = $yearId;

        if ($termId) {
            $term = Term::find($termId);
            if ($term) {
                $resolvedTermId = $term->id;
                $resolvedYearId = $term->academic_year_id;
                $start = $term->opening_date ? $term->opening_date->copy()->startOfDay() : null;
                $end = $term->closing_date ? $term->closing_date->copy()->endOfDay() : null;
                if ($start && $end) {
                    return [$start, $end, $resolvedYearId, $resolvedTermId];
                }
            }
        }

        if (! $resolvedYearId) {
            $active = AcademicYear::where('is_active', true)->first();
            $resolvedYearId = $active?->id;
        }

        if ($resolvedYearId) {
            $termRange = Term::where('academic_year_id', $resolvedYearId)
                ->orderBy('opening_date')->get();
            $start = $termRange->first()?->opening_date?->copy()->startOfDay();
            $end = $termRange->last()?->closing_date?->copy()->endOfDay();
            if ($start && $end) {
                return [$start, $end, $resolvedYearId, null];
            }
        }

        return [null, null, $resolvedYearId, null];
    }

    /**
     * Terms for the active academic year (fallback: latest terms).
     *
     * @return \Illuminate\Support\Collection<int, Term>
     */
    protected function termsForCharts()
    {
        $year = AcademicYear::where('is_active', true)->first();
        if ($year) {
            $terms = Term::where('academic_year_id', $year->id)->orderBy('opening_date')->get();
            if ($terms->isNotEmpty()) {
                return $terms;
            }
        }

        return Term::orderByDesc('opening_date')->limit(4)->get()->sortBy('opening_date')->values();
    }

    /**
     * New enrolments per term (admission_date in term, else created_at in term).
     */
    protected function adminEnrolmentByTerm(): array
    {
        $labels = [];
        $values = [];
        foreach ($this->termsForCharts() as $term) {
            if (! $term->opening_date || ! $term->closing_date) {
                continue;
            }
            $start = $term->opening_date->copy()->startOfDay();
            $end = $term->closing_date->copy()->endOfDay();
            $labels[] = \Illuminate\Support\Str::limit((string) $term->name, 14);
            $cnt = Student::query()
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->where(function ($q) use ($start, $end) {
                    $q->whereNotNull('admission_date')
                        ->whereBetween('admission_date', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->whereNull('admission_date')
                                ->whereBetween('created_at', [$start, $end]);
                        });
                })
                ->count();
            $values[] = $cnt;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Payment collections per term (payment_date within term opening–closing).
     */
    protected function adminPaymentsByTerm(): array
    {
        $labels = [];
        $values = [];
        foreach ($this->termsForCharts() as $term) {
            if (! $term->opening_date || ! $term->closing_date) {
                continue;
            }
            $start = $term->opening_date->copy()->startOfDay();
            $end = $term->closing_date->copy()->endOfDay();
            $labels[] = \Illuminate\Support\Str::limit((string) $term->name, 14);
            $sum = Payment::query()
                ->where(function ($q) {
                    $q->whereNull('reversed')->orWhere('reversed', false);
                })
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount');
            $values[] = round((float) $sum, 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Invoices per term (issued_date if set, else created_at, within term).
     */
    protected function adminInvoicesByTerm(): array
    {
        $labels = [];
        $values = [];
        foreach ($this->termsForCharts() as $term) {
            if (! $term->opening_date || ! $term->closing_date) {
                continue;
            }
            $start = $term->opening_date->copy()->startOfDay();
            $end = $term->closing_date->copy()->endOfDay();
            $labels[] = \Illuminate\Support\Str::limit((string) $term->name, 14);
            $cnt = Invoice::query()
                ->where(function ($q) use ($start, $end) {
                    $q->whereNotNull('issued_date')
                        ->whereBetween('issued_date', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->whereNull('issued_date')
                                ->whereBetween('created_at', [$start, $end]);
                        });
                })
                ->count();
            $values[] = $cnt;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Students & staff with birthdays in the next 14 days.
     *
     * @return list<array{name: string, date: string, type: string}>
     */
    protected function adminUpcomingBirthdays(): array
    {
        $from = Carbon::today()->startOfDay();
        $through = Carbon::today()->addDays(14)->endOfDay();
        $out = [];

        $students = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereNotNull('dob')
            ->get(['first_name', 'last_name', 'dob']);

        foreach ($students as $s) {
            if (! $s->dob) {
                continue;
            }
            $next = $this->nextBirthdayOccurrence(Carbon::parse($s->dob), Carbon::today());
            if ($next->between($from, $through)) {
                $out[] = [
                    'name' => trim(($s->first_name ?? '').' '.($s->last_name ?? '')) ?: 'Student',
                    'date' => $next->toDateString(),
                    'type' => 'student',
                ];
            }
        }

        $staffMembers = Staff::query()
            ->whereNotNull('date_of_birth')
            ->get(['first_name', 'last_name', 'date_of_birth']);

        foreach ($staffMembers as $st) {
            if (! $st->date_of_birth) {
                continue;
            }
            $next = $this->nextBirthdayOccurrence(Carbon::parse($st->date_of_birth), Carbon::today());
            if ($next->between($from, $through)) {
                $out[] = [
                    'name' => trim(($st->first_name ?? '').' '.($st->last_name ?? '')) ?: 'Staff',
                    'date' => $next->toDateString(),
                    'type' => 'staff',
                ];
            }
        }

        usort($out, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return array_slice($out, 0, 20);
    }

    protected function nextBirthdayOccurrence(Carbon $birthDate, Carbon $from): Carbon
    {
        $thisYear = Carbon::createFromDate($from->year, $birthDate->month, $birthDate->day)->startOfDay();
        if ($thisYear->lt($from->copy()->startOfDay())) {
            return Carbon::createFromDate($from->year + 1, $birthDate->month, $birthDate->day)->startOfDay();
        }

        return $thisYear;
    }

    /**
     * Teachers / senior teachers / supervisors currently on approved leave.
     *
     * @return list<array{name: string, start_date: string, end_date: string, leave_type: string|null}>
     */
    protected function adminTeachersOnLeave(): array
    {
        $today = Carbon::today()->toDateString();

        $rows = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with(['staff.user.roles', 'leaveType'])
            ->orderBy('end_date')
            ->limit(30)
            ->get();

        $out = [];
        foreach ($rows as $req) {
            $staff = $req->staff;
            if (! $staff || ! $staff->user) {
                continue;
            }
            $isTeacher = $staff->user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor', 'teacher', 'senior teacher', 'supervisor']);
            if (! $isTeacher) {
                continue;
            }
            $out[] = [
                'name' => $staff->full_name,
                'start_date' => $req->start_date->toDateString(),
                'end_date' => $req->end_date->toDateString(),
                'leave_type' => $req->leaveType?->name,
            ];
        }

        return $out;
    }

    protected function studentsTableHasUserId(): bool
    {
        try {
            return Schema::hasColumn('students', 'user_id');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
