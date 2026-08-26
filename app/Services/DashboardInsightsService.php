<?php

namespace App\Services;

use App\Models\Academics\Classroom;
use App\Models\Academics\ExtraCurricularActivity;
use App\Models\Academics\LessonPlan;
use App\Models\Academics\Stream;
use App\Models\Academics\StudentBehaviour;
use App\Models\Academics\Subject;
use App\Models\Announcement;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\Term;
use App\Models\Trip;
use App\Models\TripRun;
use App\Models\Vehicle;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DashboardInsightsService
{
    private function safe(callable $fn, $fallback)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            \Log::warning('Dashboard insight skipped: '.$e->getMessage());
            return $fallback;
        }
    }

    public function activeStaffCount(): int
    {
        return (int) $this->safe(function () {
        return Staff::query()
            ->where(function ($q) {
                $q->where('employment_status', 'active')
                    ->orWhere(function ($q2) {
                        $q2->whereNull('employment_status')
                            ->where(function ($q3) {
                                $q3->where('status', 'active')->orWhereNull('status');
                            });
                    });
            })
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'archived');
            })
            ->count();
        }, 0);
    }

    public function staffOnLeaveToday(string $today): int
    {
        $fromRequests = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('staff_id');

        $fromStatus = Staff::query()
            ->where('employment_status', 'on_leave')
            ->pluck('id');

        return $fromRequests->merge($fromStatus)->unique()->count();
    }

    public function pendingApprovals(): array
    {
        $leave = LeaveRequest::where('status', 'pending')->count();

        $lessonPlans = 0;
        if (Schema::hasTable('lesson_plans') && Schema::hasColumn('lesson_plans', 'approved_at')) {
            $q = LessonPlan::query()->whereNull('approved_at');
            if (Schema::hasColumn('lesson_plans', 'rejected_at')) {
                $q->whereNull('rejected_at');
            }
            if (Schema::hasColumn('lesson_plans', 'submitted_at')) {
                $q->whereNotNull('submitted_at');
            }
            $lessonPlans = $q->count();
        }

        $requisitions = 0;
        if (class_exists(\App\Models\Requisition::class) && Schema::hasTable('requisitions')) {
            $requisitions = \App\Models\Requisition::query()
                ->whereIn('status', ['pending', 'submitted'])
                ->count();
        }

        $admissions = 0;
        if (class_exists(\App\Models\OnlineAdmission::class) && Schema::hasTable('online_admissions')) {
            $statusCol = Schema::hasColumn('online_admissions', 'application_status')
                ? 'application_status'
                : (Schema::hasColumn('online_admissions', 'status') ? 'status' : null);
            if ($statusCol) {
                $admissions = \App\Models\OnlineAdmission::query()
                    ->whereIn($statusCol, ['pending', 'submitted', 'review'])
                    ->count();
            }
        }

        $total = $leave + $lessonPlans + $requisitions + $admissions;

        return [
            'total' => $total,
            'leave' => $leave,
            'lesson_plans' => $lessonPlans,
            'requisitions' => $requisitions,
            'admissions' => $admissions,
        ];
    }

    public function overviewCounts(): array
    {
        $visitorsToday = 0;
        if (class_exists(VisitorLog::class) && Schema::hasTable('visitor_logs')) {
            $dateCol = Schema::hasColumn('visitor_logs', 'visit_date')
                ? 'visit_date'
                : (Schema::hasColumn('visitor_logs', 'created_at') ? 'created_at' : null);
            if ($dateCol) {
                $visitorsToday = VisitorLog::whereDate($dateCol, now()->toDateString())->count();
            }
        }

        $clubs = 0;
        if (class_exists(ExtraCurricularActivity::class) && Schema::hasTable('extra_curricular_activities')) {
            $clubs = ExtraCurricularActivity::query()->count();
        }

        return [
            'classrooms' => Classroom::count(),
            'streams' => Stream::count(),
            'subjects' => Subject::count(),
            'clubs' => $clubs,
            'vehicles' => class_exists(Vehicle::class) ? Vehicle::count() : 0,
            'trips' => class_exists(Trip::class) ? Trip::count() : 0,
            'visitors_today' => $visitorsToday,
        ];
    }

    public function transportOps(string $today): array
    {
        return $this->safe(function () use ($today) {
            $scheduled = $this->tripsScheduledForDateQuery($today);
            $scheduledToday = $scheduled ? (clone $scheduled)->count() : 0;

            $runsToday = 0;
            $completedToday = 0;
            if (class_exists(TripRun::class) && Schema::hasTable('trip_runs')) {
                $runsToday = TripRun::whereDate('run_date', $today)->count();
                $completedToday = TripRun::whereDate('run_date', $today)
                    ->whereIn('status', ['completed', 'ended', 'finished'])
                    ->count();
            }

            $drivers = 0;
            if (class_exists(Trip::class) && Schema::hasColumn('trips', 'driver_id')) {
                $drivers = Trip::query()->whereNotNull('driver_id')->distinct()->count('driver_id');
            }

            $assignedStudents = 0;
            if (class_exists(StudentAssignment::class) && Schema::hasTable('student_assignments')) {
                $assignedStudents = StudentAssignment::query()->forTerm()->distinct()->count('student_id');
            }

            $routes = 0;
            if (class_exists(\App\Models\Route::class) && Schema::hasTable('routes')) {
                $routes = \App\Models\Route::count();
            }

            return [
                'vehicles' => class_exists(Vehicle::class) ? Vehicle::count() : 0,
                'drivers' => $drivers,
                'routes' => $routes,
                'students_assigned' => $assignedStudents,
                'trips_total' => class_exists(Trip::class) ? Trip::count() : 0,
                'trips_scheduled_today' => $scheduledToday,
                'trip_runs_today' => $runsToday,
                'trip_runs_completed_today' => $completedToday,
                'alerts' => $this->transportAlerts($today),
                'today_trips' => $this->todayTrips($today),
            ];
        }, [
            'vehicles' => 0,
            'drivers' => 0,
            'routes' => 0,
            'students_assigned' => 0,
            'trips_total' => 0,
            'trips_scheduled_today' => 0,
            'trip_runs_today' => 0,
            'trip_runs_completed_today' => 0,
            'alerts' => [],
            'today_trips' => collect(),
        ]);
    }

    public function recentAdmissions(int $limit = 5): Collection
    {
        $dateCol = Schema::hasColumn('students', 'admission_date') ? 'admission_date' : 'created_at';

        return Student::query()
            ->with(['classroom', 'stream'])
            ->orderByDesc($dateCol)
            ->take($limit)
            ->get()
            ->map(function (Student $s) use ($dateCol) {
                return (object) [
                    'id' => $s->id,
                    'name' => $s->full_name,
                    'admission_number' => $s->admission_number,
                    'classroom' => $s->classroom?->name,
                    'status' => $s->status,
                    'admitted_on' => $s->{$dateCol},
                ];
            });
    }

    public function recentPayments(string $from, string $to, int $limit = 8): Collection
    {
        return Payment::query()
            ->with(['student', 'paymentMethod'])
            ->where('reversed', false)
            ->whereBetween('payment_date', [$from, $to])
            ->latest('payment_date')
            ->take($limit)
            ->get()
            ->map(function (Payment $p) {
                return (object) [
                    'id' => $p->id,
                    'receipt_number' => $p->receipt_number ?: $p->transaction_code,
                    'student_name' => $p->student?->full_name,
                    'admission_number' => $p->student?->admission_number,
                    'method' => $p->paymentMethod?->name ?: ($p->payment_method ?: 'Other'),
                    'amount' => (float) $p->amount,
                    'date' => $p->payment_date,
                ];
            });
    }

    public function paymentMethodBreakdown(string $from, string $to): Collection
    {
        $query = Payment::query()
            ->where('payments.reversed', false)
            ->whereBetween('payments.payment_date', [$from, $to])
            ->whereRaw("COALESCE(payments.receipt_number, '') NOT LIKE 'SWIM-%'");

        if (Schema::hasTable('payment_methods')) {
            $rows = $query->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->selectRaw('payment_methods.name as method_name')
                ->selectRaw('payments.payment_method as method_fallback')
                ->selectRaw('SUM(payments.amount) as total')
                ->selectRaw('COUNT(*) as payment_count')
                ->groupBy('payment_methods.name', 'payments.payment_method')
                ->get();

            return $rows->groupBy(function ($row) {
                return $row->method_name ?: ($row->method_fallback ?: 'Other');
            })->map(function ($group, $method) {
                return (object) [
                    'method' => $method,
                    'total' => (float) $group->sum('total'),
                    'payment_count' => (int) $group->sum('payment_count'),
                ];
            })->sortByDesc('total')->values();
        }

        return $query->selectRaw("COALESCE(NULLIF(payment_method, ''), 'Other') as method")
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as payment_count')
            ->groupByRaw("COALESCE(NULLIF(payment_method, ''), 'Other')")
            ->orderByDesc('total')
            ->get();
    }

    public function recentActivity(bool $includeFinance, int $limit = 8): Collection
    {
        $items = collect();

        Student::query()
            ->orderByDesc('created_at')
            ->take(4)
            ->get()
            ->each(function (Student $s) use ($items) {
                $items->push([
                    'title' => $s->full_name.' admitted',
                    'meta' => $s->admission_number,
                    'date' => $s->created_at,
                    'tag' => 'Admission',
                    'url' => route('students.show', $s->id),
                ]);
            });

        Announcement::query()
            ->latest()
            ->take(3)
            ->get()
            ->each(function (Announcement $a) use ($items) {
                $items->push([
                    'title' => $a->title,
                    'meta' => 'Announcement',
                    'date' => $a->created_at,
                    'tag' => 'Announcement',
                    'url' => \Illuminate\Support\Facades\Route::has('announcements.index')
                        ? route('announcements.index')
                        : null,
                ]);
            });

        if ($includeFinance) {
            Payment::query()
                ->with('student')
                ->where('reversed', false)
                ->latest('payment_date')
                ->take(4)
                ->get()
                ->each(function (Payment $p) use ($items) {
                    $items->push([
                        'title' => 'Payment received: '.format_money((float) $p->amount),
                        'meta' => $p->student?->full_name ?: ($p->receipt_number ?: 'Receipt'),
                        'date' => $p->payment_date ?? $p->created_at,
                        'tag' => 'Payment',
                        'url' => \Illuminate\Support\Facades\Route::has('finance.payments.show')
                            ? route('finance.payments.show', $p->id)
                            : null,
                    ]);
                });
        }

        return $items->sortByDesc(function ($row) {
            $date = $row['date'] ?? null;
            if ($date instanceof Carbon) {
                return $date->timestamp;
            }

            return $date ? Carbon::parse($date)->timestamp : 0;
        })->take($limit)->values();
    }

    public function studentsDelta(?Term $term, int $currentCount): ?float
    {
        if (! $term?->opening_date || $currentCount <= 0) {
            return null;
        }

        $atStart = Student::query()
            ->where('created_at', '<', $term->opening_date->startOfDay())
            ->count();

        if ($atStart <= 0) {
            return null;
        }

        return round((($currentCount - $atStart) / $atStart) * 100, 1);
    }

    public function behaviourSummary(string $from, string $to): array
    {
        $base = StudentBehaviour::query()->whereBetween('date', [$from, $to]);

        $positive = 0;
        if (Schema::hasColumn('behaviours', 'type')) {
            $positive = (clone $base)->whereHas('behaviour', fn ($q) => $q->where('type', 'positive'))->count();
        }

        return [
            'positive' => $positive,
            'minor' => (clone $base)->where('severity', 'minor')->count(),
            'moderate' => (clone $base)->where('severity', 'moderate')->count(),
            'major' => (clone $base)->where('severity', 'major')->count(),
        ];
    }

    public function outstandingStudents(array $filters, int $limit = 25): Collection
    {
        return $this->safe(function () use ($filters, $limit) {
            $today = now()->toDateString();
            $statusFilter = $filters['status'] ?? null;

            $query = Invoice::query()
                ->selectRaw('student_id')
                ->selectRaw('SUM(total) as invoiced')
                ->selectRaw('SUM(paid_amount) as paid')
                ->selectRaw('SUM(balance) as balance')
                ->selectRaw('SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? AND balance > 0 THEN 1 ELSE 0 END) as overdue_invoices', [$today])
                ->whereNull('reversed_at')
                ->whereIn('status', ['unpaid', 'partial'])
                ->when(! empty($filters['term_id']), fn ($q) => $q->where('term_id', $filters['term_id']))
                ->when(! empty($filters['classroom_id']) || ! empty($filters['stream_id']), function ($q) use ($filters) {
                    $q->whereHas('student', function ($s) use ($filters) {
                        $s->when(! empty($filters['classroom_id']), fn ($qq) => $qq->where('classroom_id', $filters['classroom_id']))
                            ->when(! empty($filters['stream_id']), fn ($qq) => $qq->where('stream_id', $filters['stream_id']));
                    });
                })
                ->groupBy('student_id')
                ->havingRaw('SUM(balance) > 0')
                ->orderByDesc('balance');

            if ($statusFilter === 'overdue') {
                $query->havingRaw('SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? AND balance > 0 THEN 1 ELSE 0 END) > 0', [$today]);
            } elseif ($statusFilter === 'outstanding') {
                $query->havingRaw('SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? AND balance > 0 THEN 1 ELSE 0 END) = 0', [$today]);
            }

            $rows = $query->take($limit)->get();
            $students = Student::with(['classroom', 'stream'])
                ->whereIn('id', $rows->pluck('student_id'))
                ->get()
                ->keyBy('id');

            return $rows->map(function ($row) use ($students) {
                $student = $students->get($row->student_id);
                $isOverdue = (int) $row->overdue_invoices > 0;

                return (object) [
                    'student_id' => $row->student_id,
                    'student_name' => $student?->full_name ?: 'Student',
                    'admission_number' => $student?->admission_number,
                    'classroom' => $student?->classroom?->name,
                    'stream' => $student?->stream?->name,
                    'invoiced' => (float) $row->invoiced,
                    'paid' => (float) $row->paid,
                    'balance' => (float) $row->balance,
                    'status' => $isOverdue ? 'overdue' : 'outstanding',
                    'statement_url' => Route::has('finance.student-statements.show') && $row->student_id
                        ? route('finance.student-statements.show', $row->student_id)
                        : null,
                ];
            });
        }, collect());
    }

    public function operationalAlerts(array $kpis, array $transport, array $pendingApprovals = []): array
    {
        $alerts = [];

        $absent = (int) ($kpis['absent_today'] ?? 0);
        if ($absent > 0 && Route::has('attendance.records')) {
            $today = now()->toDateString();
            $alerts[] = [
                'label' => $absent.' student'.($absent === 1 ? '' : 's').' absent today',
                'url' => route('attendance.records', ['start' => $today, 'end' => $today]),
                'tone' => 'urgent',
            ];
        }

        $overdueCount = (int) ($kpis['overdue_invoice_count'] ?? 0);
        if ($overdueCount > 0 && Route::has('finance.invoices.index')) {
            $alerts[] = [
                'label' => $overdueCount.' invoice'.($overdueCount === 1 ? '' : 's').' overdue',
                'url' => route('finance.invoices.index', ['overdue' => 1]),
                'tone' => 'urgent',
            ];
        } elseif ($overdueCount > 0 && Route::has('finance.fee-balances.index')) {
            $alerts[] = [
                'label' => $overdueCount.' invoice'.($overdueCount === 1 ? '' : 's').' overdue',
                'url' => route('finance.fee-balances.index', ['balance_status' => 'with_balance']),
                'tone' => 'urgent',
            ];
        }

        $onLeave = (int) ($kpis['teachers_on_leave'] ?? 0);
        if ($onLeave > 0 && Route::has('staff.leave-requests.index')) {
            $alerts[] = [
                'label' => $onLeave.' staff on approved leave today',
                'url' => route('staff.leave-requests.index'),
                'tone' => 'info',
            ];
        }

        $pending = (int) ($pendingApprovals['total'] ?? ($kpis['pending_approvals'] ?? 0));
        if ($pending > 0 && Route::has('staff.leave-requests.index')) {
            $alerts[] = [
                'label' => $pending.' pending approval'.($pending === 1 ? '' : 's'),
                'url' => route('staff.leave-requests.index'),
                'tone' => 'info',
            ];
        }

        foreach ($transport['alerts'] ?? [] as $alert) {
            if ((int) ($alert['count'] ?? 0) <= 0) {
                continue;
            }
            $alerts[] = [
                'label' => $alert['count'].' '.$alert['label'],
                'url' => $alert['url'] ?? (Route::has('transport.trips.index') ? route('transport.trips.index') : null),
                'tone' => 'urgent',
            ];
        }

        return $alerts;
    }

    private function tripsScheduledForDateQuery(string $today)
    {
        if (! class_exists(Trip::class) || ! Schema::hasColumn('trips', 'day_of_week')) {
            return null;
        }

        $day = (int) Carbon::parse($today)->isoWeekday();

        return Trip::query()->where(function ($q) use ($day) {
            $q->whereNull('day_of_week')
                ->orWhereJsonContains('day_of_week', $day)
                ->orWhereJsonContains('day_of_week', (string) $day);
        });
    }

    private function todayTrips(string $today): Collection
    {
        $query = $this->tripsScheduledForDateQuery($today);
        if (! $query) {
            return collect();
        }

        $trips = $query
            ->with([
                'vehicle',
                'driver',
                'stops',
                'runs' => fn ($q) => $q->whereDate('run_date', $today),
            ])
            ->orderBy('trip_name')
            ->get();

        $counts = $this->studentCountsByTrip($trips->pluck('id'));

        return $trips->map(function (Trip $trip) use ($counts) {
            $studentCount = (int) ($counts[$trip->id] ?? 0);
            $run = $trip->runs->first();
            $missingDriver = empty($trip->driver_id);
            $missingVehicle = Schema::hasColumn('trips', 'vehicle_id') && empty($trip->vehicle_id);
            $status = $run?->status
                ?: ($missingDriver ? 'missing_driver' : ($missingVehicle ? 'missing_vehicle' : 'scheduled'));

            $time = optional($trip->stops->first())->estimated_time;
            $timeLabel = $time ? Carbon::parse($time)->format('H:i') : ($trip->type ?: ($trip->direction ?: '—'));

            $url = null;
            if (Route::has('transport.trips.edit')) {
                $url = route('transport.trips.edit', $trip->id);
            } elseif (Route::has('transport.trips.index')) {
                $url = route('transport.trips.index');
            }

            return (object) [
                'id' => $trip->id,
                'time' => $timeLabel,
                'route' => $trip->trip_name ?: ($trip->name ?: 'Trip #'.$trip->id),
                'vehicle' => $trip->vehicle?->vehicle_number,
                'driver' => $trip->driver?->full_name,
                'students' => $studentCount,
                'status' => $status,
                'missing_driver' => $missingDriver,
                'missing_vehicle' => $missingVehicle,
                'url' => $url,
            ];
        });
    }

    private function studentCountsByTrip(Collection $tripIds): array
    {
        if ($tripIds->isEmpty() || ! class_exists(StudentAssignment::class)) {
            return [];
        }

        $counts = [];
        $add = function ($rows) use (&$counts) {
            foreach ($rows as $row) {
                if (! $row->trip_id) {
                    continue;
                }
                $counts[(int) $row->trip_id] = ($counts[(int) $row->trip_id] ?? 0) + (int) $row->c;
            }
        };

        $add(StudentAssignment::query()
            ->forTerm()
            ->selectRaw('morning_trip_id as trip_id, COUNT(DISTINCT student_id) as c')
            ->whereIn('morning_trip_id', $tripIds)
            ->groupBy('morning_trip_id')
            ->get());

        $add(StudentAssignment::query()
            ->forTerm()
            ->selectRaw('evening_trip_id as trip_id, COUNT(DISTINCT student_id) as c')
            ->whereIn('evening_trip_id', $tripIds)
            ->groupBy('evening_trip_id')
            ->get());

        if (Schema::hasColumn('student_assignments', 'trip_id')) {
            $add(StudentAssignment::query()
                ->forTerm()
                ->selectRaw('trip_id, COUNT(DISTINCT student_id) as c')
                ->whereIn('trip_id', $tripIds)
                ->groupBy('trip_id')
                ->get());
        }

        return $counts;
    }

    private function transportAlerts(string $today): array
    {
        $alerts = [];
        $tripsUrl = Route::has('transport.trips.index') ? route('transport.trips.index') : null;
        $assignUrl = Route::has('transport.student-assignments.index')
            ? route('transport.student-assignments.index')
            : $tripsUrl;

        $scheduled = $this->tripsScheduledForDateQuery($today);
        if ($scheduled && Schema::hasColumn('trips', 'driver_id')) {
            $missingDriver = (clone $scheduled)->whereNull('driver_id')->count();
            if ($missingDriver > 0) {
                $alerts[] = [
                    'label' => 'trip'.($missingDriver === 1 ? '' : 's').' missing a driver',
                    'count' => $missingDriver,
                    'url' => $tripsUrl,
                ];
            }
            if (Schema::hasColumn('trips', 'vehicle_id')) {
                $missingVehicle = (clone $scheduled)->whereNull('vehicle_id')->count();
                if ($missingVehicle > 0) {
                    $alerts[] = [
                        'label' => 'trip'.($missingVehicle === 1 ? '' : 's').' missing a vehicle',
                        'count' => $missingVehicle,
                        'url' => $tripsUrl,
                    ];
                }
            }
        }

        if (class_exists(StudentAssignment::class) && Schema::hasTable('student_assignments')) {
            $incomplete = StudentAssignment::query()
                ->forTerm()
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->whereNotNull('morning_trip_id')->whereNull('morning_drop_off_point_id');
                    })->orWhere(function ($q2) {
                        $q2->whereNotNull('evening_trip_id')->whereNull('evening_drop_off_point_id');
                    });
                })
                ->count();
            if ($incomplete > 0) {
                $alerts[] = [
                    'label' => 'assignment'.($incomplete === 1 ? '' : 's').' missing pickup/drop-off',
                    'count' => $incomplete,
                    'url' => $assignUrl,
                ];
            }
        }

        return $alerts;
    }
}
