<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\FeePaymentPlan;
use App\Models\FinancialNote;
use App\Models\Term;
use App\Models\Academics\Classroom;
use App\Models\Votehead;
use App\Models\InvoiceItem;
use App\Services\StudentBalanceService;
use App\Services\StudentFeeStatementService;
use App\Services\PDFExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FeeBalanceController extends Controller
{
    /**
     * Display fee balance report with attendance tracking
     */
    public function index(Request $request)
    {
        // Get current term - use term_id for alignment with Admin Dashboard
        $currentTerm = Term::where('is_current', true)->with('academicYear')->first();
        $termId = $request->input('term_id') ?: $currentTerm?->id;
        $year = $request->input('year', $currentTerm?->academicYear?->year ?? now()->year);
        $termNumber = $request->input('term', $currentTerm ? $this->extractTermNumber($currentTerm->name) : 1);
        
        // Resolve term_id from year+term if not set (for alignment with dashboard)
        if (!$termId && $year && $termNumber) {
            $resolvedTerm = Term::whereHas('academicYear', fn($q) => $q->where('year', $year))
                ->where(function ($q) use ($termNumber) {
                    $q->where('name', 'like', '%Term ' . $termNumber . '%')
                      ->orWhere('name', 'like', '% ' . $termNumber);
                })
                ->first();
            $termId = $resolvedTerm?->id;
        }
        
        $selectedTerm = $termId ? Term::with('academicYear')->find($termId) : $currentTerm;
        
        // Get filters
        $classroomId = $request->input('classroom_id');
        $balanceStatus = $request->input('balance_status');
        $attendanceFilter = $request->input('attendance_filter');
        $paymentPlanFilter = $request->input('payment_plan_filter');
        $bbfFilter = $request->input('bbf_filter');
        $fiscalTaskFilter = $request->input('fiscal_task_filter');
        
        // Build student query (exclude staff-category children — they are not billed like regular fee balances)
        $studentsQuery = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->with(['classroom', 'stream', 'parent', 'family', 'category'])
            ->where(function ($q) {
                $q->whereDoesntHave('category', function ($cq) {
                    $cq->whereRaw('LOWER(name) = ?', ['staff']);
                })->orWhereNull('category_id');
            });
        
        // Also exclude children linked via staff user parent_id
        $staffChildIds = $this->getStaffChildStudentIds();
        if (!empty($staffChildIds)) {
            $studentsQuery->whereNotIn('id', $staffChildIds);
        }
        
        // Apply classroom filter
        if ($classroomId) {
            $studentsQuery->where('classroom_id', $classroomId);
        }
        
        // Get students
        $students = $studentsQuery->get();

        $promiseMaps = $this->loadLastPromiseDates($students);
        $paymentStats = $this->loadPaymentActivityStats($students, $selectedTerm);
        
        // Get balance brought forward votehead
        $balanceBroughtForwardVotehead = Votehead::where('code', 'BAL_BF')->first();
        
        $statementService = app(StudentFeeStatementService::class);

        // Enrich each student with financial and attendance data
        $enrichedStudents = $students->map(function ($student) use ($year, $termNumber, $termId, $selectedTerm, $balanceBroughtForwardVotehead, $statementService, $promiseMaps, $paymentStats) {
            // Term invoice (for payment plans / BBF status context)
            $invoice = Invoice::where('student_id', $student->id)
                ->when($termId, fn($q) => $q->where('term_id', $termId))
                ->when(!$termId, fn($q) => $q->where('year', $year)->where('term', $termNumber))
                ->first();

            // Year-wide figures must match Student Statements (not single-term invoice only).
            // Prior-term arrears + overpayments were previously invisible here (e.g. Alex Kirika).
            $yearPack = $statementService->forStudent($student, (int) $year, null);
            // Outstanding balance = all fees still owing (year-wide statement).
            $yearInvoiced = (float) ($yearPack['total_charges'] ?? 0);
            $yearPaid = (float) ($yearPack['total_payments'] ?? 0);
            $balance = (float) ($yearPack['closing_balance'] ?? 0);

            // Invoiced / paid columns are for the selected term only.
            $termInvoiced = $invoice ? (float) $invoice->total : 0;
            $termPaid = $invoice ? (float) $invoice->paid_amount : 0;
            $termBalance = $invoice ? (float) $invoice->balance : 0;
            
            // Get balance brought forward information
            $balanceBroughtForwardData = $this->getBalanceBroughtForwardData($student, $balanceBroughtForwardVotehead, $invoice, $termBalance);
            
            // Get attendance data since term start
            $termStartDate = $selectedTerm?->opening_date ?? Carbon::now()->startOfMonth();
            $attendanceData = $this->getAttendanceStats($student->id, $termStartDate);
            
            // Get payment plan status
            $paymentPlan = $invoice ? FeePaymentPlan::where('invoice_id', $invoice->id)
                ->where('status', 'active')
                ->with('installments')
                ->first() : null;
            
            $paymentPlanStatus = $this->getPaymentPlanStatus($paymentPlan);

            $studentPromise = $promiseMaps['by_student'][$student->id] ?? null;
            $familyPromise = $student->family_id
                ? ($promiseMaps['by_family'][$student->family_id] ?? null)
                : null;
            $lastPromised = $this->resolveLatestPromiseDate($studentPromise, $familyPromise);
            $fiscalTask = $this->computeFiscalTask($balance, $lastPromised);
            $payStats = $paymentStats[$student->id] ?? [
                'last_payment_date' => null,
                'last_payment_amount' => 0.0,
                'paid_in_term_period' => 0.0,
            ];

            $parentPhone = $this->cleanDisplayValue(
                $student->parent
                    ? ($student->parent->father_phone ?? $student->parent->mother_phone ?? $student->parent->guardian_phone)
                    : null
            );
            
            return [
                'id' => $student->id,
                'family_id' => $student->family_id,
                'admission_number' => $student->admission_number,
                'full_name' => $student->full_name,
                'classroom' => $student->classroom ? $student->classroom->name : null,
                'classroom_id' => $student->classroom_id,
                'stream' => $student->stream ? $student->stream->name : null,
                'parent_phone' => $parentPhone,
                'father_name' => $this->cleanDisplayValue($student->parent?->father_name),
                'father_phone' => $this->cleanDisplayValue($student->parent?->father_phone),
                'mother_name' => $this->cleanDisplayValue($student->parent?->mother_name),
                'mother_phone' => $this->cleanDisplayValue($student->parent?->mother_phone),
                'guardian_name' => $this->cleanDisplayValue($student->parent?->guardian_name),
                'guardian_phone' => $this->cleanDisplayValue($student->parent?->guardian_phone),
                // Primary UI amounts = selected term invoice
                'total_invoiced' => $termInvoiced,
                'total_paid' => $termPaid,
                'term_invoiced' => $termInvoiced,
                'term_paid' => $termPaid,
                'term_balance' => $termBalance,
                // Cash collected during the selected term window (even if applied to prior dues)
                'paid_in_term_period' => (float) ($payStats['paid_in_term_period'] ?? 0),
                'last_payment_date' => $payStats['last_payment_date'] ?? null,
                'last_payment_amount' => (float) ($payStats['last_payment_amount'] ?? 0),
                // Outstanding = combined all fees still owing (year statement)
                'balance' => $balance,
                'year_invoiced' => $yearInvoiced,
                'year_paid' => $yearPaid,
                'balance_percentage' => $yearInvoiced > 0 ? round((max($balance, 0) / $yearInvoiced) * 100, 1) : 0,
                'payment_status' => $this->getPaymentStatus($yearInvoiced, $yearPaid, $balance),
                'attendance_days' => $attendanceData['total_days'],
                'days_present' => $attendanceData['present'],
                'days_absent' => $attendanceData['absent'],
                'days_late' => $attendanceData['late'],
                'attendance_rate' => $attendanceData['attendance_rate'],
                'is_in_school' => $attendanceData['present'] > 0,
                'has_payment_plan' => $paymentPlan !== null,
                'payment_plan_status' => $paymentPlanStatus['status'],
                'payment_plan_progress' => $paymentPlanStatus['progress'],
                'next_installment_date' => $paymentPlanStatus['next_due_date'],
                'invoice_id' => $invoice ? $invoice->id : null,
                // Balance brought forward data (UI only shows when outstanding)
                'balance_brought_forward' => $balanceBroughtForwardData['amount'],
                'balance_brought_forward_paid' => $balanceBroughtForwardData['paid'],
                'balance_brought_forward_balance' => $balanceBroughtForwardData['balance'],
                'bbf_payment_status' => $balanceBroughtForwardData['payment_status'],
                'has_uncleared_bbf' => ($balanceBroughtForwardData['balance'] ?? 0) > 0.009,
                'last_promised' => $lastPromised,
                'fiscal_task' => $fiscalTask,
            ];
        });
        
        // Apply filters
        $filteredStudents = $enrichedStudents;
        
        // Balance status filter (outstanding uses year-wide balance)
        if ($balanceStatus) {
            $filteredStudents = $filteredStudents->filter(function ($student) use ($balanceStatus) {
                switch ($balanceStatus) {
                    case 'with_balance':
                        return $student['balance'] > 0;
                    case 'cleared':
                        return $student['balance'] <= 0 && ($student['year_invoiced'] ?? 0) > 0;
                    case 'overpaid':
                        return $student['balance'] < 0;
                    case 'not_invoiced':
                        return ($student['year_invoiced'] ?? 0) == 0 && ($student['term_invoiced'] ?? 0) == 0;
                    default:
                        return true;
                }
            });
        }
        
        // Attendance filter
        if ($attendanceFilter) {
            $filteredStudents = $filteredStudents->filter(function ($student) use ($attendanceFilter) {
                switch ($attendanceFilter) {
                    case 'in_school':
                        return $student['is_in_school'];
                    case 'not_reported':
                        return !$student['is_in_school'];
                    case 'poor_attendance':
                        return $student['attendance_rate'] < 75;
                    default:
                        return true;
                }
            });
        }
        
        // Payment plan filter
        if ($paymentPlanFilter) {
            $filteredStudents = $filteredStudents->filter(function ($student) use ($paymentPlanFilter) {
                switch ($paymentPlanFilter) {
                    case 'has_plan':
                        return $student['has_payment_plan'];
                    case 'no_plan':
                        return !$student['has_payment_plan'];
                    case 'plan_overdue':
                        return $student['payment_plan_status'] === 'overdue';
                    case 'plan_on_track':
                        return $student['payment_plan_status'] === 'on_track';
                    default:
                        return true;
                }
            });
        }
        
        // Balance brought forward filter
        if ($bbfFilter) {
            $filteredStudents = $filteredStudents->filter(function ($student) use ($bbfFilter) {
                switch ($bbfFilter) {
                    case 'has_bbf':
                        return $student['balance_brought_forward'] > 0;
                    case 'no_bbf':
                        return $student['balance_brought_forward'] == 0;
                    case 'bbf_cleared':
                        return in_array($student['bbf_payment_status'], ['cleared_bbf_and_invoice', 'cleared_bbf_only']);
                    case 'bbf_unpaid':
                        return in_array($student['bbf_payment_status'], ['bbf_unpaid', 'bbf_partial']);
                    default:
                        return true;
                }
            });
        }

        // Fiscal task filter (promise-date traffic light)
        if ($fiscalTaskFilter) {
            $filteredStudents = $filteredStudents->filter(function ($student) use ($fiscalTaskFilter) {
                $task = $student['fiscal_task'] ?? 'none';
                return match ($fiscalTaskFilter) {
                    'green' => $task === 'green',
                    'yellow' => $task === 'yellow',
                    'red' => $task === 'red',
                    'no_promise' => $task === 'red' && empty($student['last_promised']),
                    default => true,
                };
            });
        }
        
        // Categorize students
        $clearedStudents = $filteredStudents->filter(function ($s) {
            return abs((float) ($s['balance'] ?? 0)) <= 0.009 && ($s['year_invoiced'] ?? 0) > 0;
        });
        
        $partialStudents = $filteredStudents->filter(function ($s) {
            return $s['payment_status'] === 'partial';
        });
        
        $unpaidStudents = $filteredStudents->filter(function ($s) {
            return $s['payment_status'] === 'unpaid';
        });
        
        // Split unpaid students by attendance
        $unpaidPresent = $unpaidStudents->filter(function ($s) {
            return $s['is_in_school'];
        });
        
        $unpaidAbsent = $unpaidStudents->filter(function ($s) {
            return !$s['is_in_school'];
        });
        
        // Get view filter (all, cleared, partial, unpaid-present, unpaid-absent, with-bbf)
        $view = $request->input('view', 'all');
        
        // Filter students based on view
        $displayStudents = collect();
        switch ($view) {
            case 'cleared':
                $displayStudents = $clearedStudents;
                break;
            case 'partial':
                $displayStudents = $partialStudents;
                break;
            case 'unpaid-present':
                $displayStudents = $unpaidPresent;
                break;
            case 'unpaid-absent':
                $displayStudents = $unpaidAbsent;
                break;
            case 'with-bbf':
                // Show only students who still owe balance brought forward
                $displayStudents = $filteredStudents->filter(function ($s) {
                    return ($s['balance_brought_forward_balance'] ?? 0) > 0;
                });
                break;
            default:
                // All = anyone still owing or overpaid (hide fully cleared zero-balance rows like Tiffany).
                $displayStudents = $filteredStudents->filter(function ($s) {
                    return abs((float) ($s['balance'] ?? 0)) > 0.009;
                });
        }
        
        // Calculate summary statistics (for all filtered students, not just displayed)
        $summary = [
            'total_students' => $filteredStudents->count(),
            'students_in_school' => $filteredStudents->where('is_in_school', true)->count(),
            'students_not_reported' => $filteredStudents->where('is_in_school', false)->count(),
            'total_invoiced' => $filteredStudents->sum('total_invoiced'),
            'total_paid' => $filteredStudents->sum('total_paid'),
            'total_balance' => $filteredStudents->sum('balance'),
            'students_with_balance' => $filteredStudents->where('balance', '>', 0)->count(),
            'students_cleared' => $clearedStudents->count(),
            'students_partial' => $partialStudents->count(),
            'students_unpaid' => $unpaidStudents->count(),
            'unpaid_present' => $unpaidPresent->count(),
            'unpaid_absent' => $unpaidAbsent->count(),
            'students_with_plans' => $filteredStudents->where('has_payment_plan', true)->count(),
            'in_school_with_balance' => $filteredStudents->filter(function ($s) {
                return $s['is_in_school'] && $s['balance'] > 0;
            })->count(),
            'in_school_balance_amount' => $filteredStudents->filter(function ($s) {
                return $s['is_in_school'] && $s['balance'] > 0;
            })->sum('balance'),
            // Balance brought forward statistics (students_with_bbf = those who still owe BBF)
            'students_with_bbf' => $filteredStudents->filter(fn($s) => ($s['balance_brought_forward_balance'] ?? 0) > 0)->count(),
            'total_bbf_amount' => $filteredStudents->sum('balance_brought_forward'),
            'total_bbf_paid' => $filteredStudents->sum('balance_brought_forward_paid'),
            'total_bbf_balance' => $filteredStudents->sum('balance_brought_forward_balance'),
            'bbf_cleared_count' => $filteredStudents->where('bbf_payment_status', 'cleared_bbf_and_invoice')->count(),
            'bbf_unpaid_count' => $filteredStudents->filter(function ($s) {
                return in_array($s['bbf_payment_status'], ['bbf_unpaid', 'bbf_partial']);
            })->count(),
        ];
        
        // Calculate counts for tabs
        $counts = [
            'all' => $filteredStudents->filter(fn ($s) => abs((float) ($s['balance'] ?? 0)) > 0.009)->count(),
            'cleared' => $clearedStudents->count(),
            'partial' => $partialStudents->count(),
            'unpaid-present' => $unpaidPresent->count(),
            'unpaid-absent' => $unpaidAbsent->count(),
            'with-bbf' => $filteredStudents->filter(function ($s) {
                return ($s['balance_brought_forward_balance'] ?? 0) > 0;
            })->count(),
        ];
        
        // Sort displayed students
        $sortBy = $request->input('sort_by', 'balance');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $displayStudents = $displayStudents->sortBy($sortBy, SORT_REGULAR, $sortOrder === 'desc')->values();
        $familyGroups = $this->buildFamilyGroups($displayStudents);
        
        // Get classrooms and terms for filter (align with Admin Dashboard)
        $classrooms = Classroom::orderBy('name')->get();
        $terms = \App\Support\AcademicContext::allTermsForSelect();
        $years = \App\Models\AcademicYear::orderBy('year', 'desc')->get();
        
        return view('finance.fee_balances.index', [
            'students' => $displayStudents,
            'familyGroups' => $familyGroups,
            'summary' => $summary,
            'counts' => $counts,
            'view' => $view,
            'classrooms' => $classrooms,
            'terms' => $terms,
            'years' => $years,
            'currentTerm' => $currentTerm,
            'selectedTermId' => $termId,
            'selectedYear' => $year,
            'selectedTermNumber' => $termNumber,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Last fee payment + cash collected during the selected term calendar window.
     *
     * @return array<int, array{last_payment_date: ?Carbon, last_payment_amount: float, paid_in_term_period: float}>
     */
    private function loadPaymentActivityStats(Collection $students, $selectedTerm): array
    {
        $studentIds = $students->pluck('id')->filter()->unique()->values()->all();
        if (empty($studentIds)) {
            return [];
        }

        $termStart = $selectedTerm?->opening_date
            ? Carbon::parse($selectedTerm->opening_date)->startOfDay()
            : null;
        $termEnd = $selectedTerm?->closing_date
            ? Carbon::parse($selectedTerm->closing_date)->endOfDay()
            : ($termStart ? $termStart->copy()->endOfYear() : null);

        $payments = Payment::query()
            ->whereIn('student_id', $studentIds)
            ->where(function ($q) {
                $q->where('reversed', false)->orWhereNull('reversed');
            })
            ->where(function ($q) {
                $q->whereNull('receipt_number')->orWhere('receipt_number', 'not like', 'SWIM-%');
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get(['id', 'student_id', 'amount', 'payment_date', 'receipt_number', 'payment_channel', 'reversed']);

        $stats = [];
        foreach ($studentIds as $id) {
            $stats[(int) $id] = [
                'last_payment_date' => null,
                'last_payment_amount' => 0.0,
                'paid_in_term_period' => 0.0,
            ];
        }

        foreach ($payments as $payment) {
            if (!\App\Services\StudentFeeLedgerService::isFeePayment($payment)) {
                continue;
            }
            $sid = (int) $payment->student_id;
            if (!isset($stats[$sid])) {
                continue;
            }
            $payDate = $payment->payment_date ? Carbon::parse($payment->payment_date) : null;
            if ($stats[$sid]['last_payment_date'] === null && $payDate) {
                $stats[$sid]['last_payment_date'] = $payDate->copy()->startOfDay();
                $stats[$sid]['last_payment_amount'] = (float) $payment->amount;
            }
            if ($termStart && $termEnd && $payDate && $payDate->gte($termStart) && $payDate->lte($termEnd)) {
                $stats[$sid]['paid_in_term_period'] += (float) $payment->amount;
            }
        }

        return $stats;
    }

    /**
     * Batch-load latest promise_date per student and per family from financial notes.
     *
     * @return array{by_student: array<int, Carbon>, by_family: array<int, Carbon>}
     */
    private function loadLastPromiseDates(Collection $students): array
    {
        $studentIds = $students->pluck('id')->filter()->unique()->values()->all();
        $familyIds = $students->pluck('family_id')->filter()->unique()->values()->all();

        $byStudent = [];
        if (!empty($studentIds)) {
            $rows = FinancialNote::query()
                ->select('student_id', DB::raw('MAX(promise_date) as last_promised'))
                ->whereIn('student_id', $studentIds)
                ->whereNotNull('promise_date')
                ->groupBy('student_id')
                ->get();
            foreach ($rows as $row) {
                $byStudent[(int) $row->student_id] = Carbon::parse($row->last_promised)->startOfDay();
            }
        }

        $byFamily = [];
        if (!empty($familyIds)) {
            $rows = FinancialNote::query()
                ->select('family_id', DB::raw('MAX(promise_date) as last_promised'))
                ->whereIn('family_id', $familyIds)
                ->whereNotNull('promise_date')
                ->groupBy('family_id')
                ->get();
            foreach ($rows as $row) {
                $byFamily[(int) $row->family_id] = Carbon::parse($row->last_promised)->startOfDay();
            }
        }

        return [
            'by_student' => $byStudent,
            'by_family' => $byFamily,
        ];
    }

    private function resolveLatestPromiseDate(?Carbon $studentPromise, ?Carbon $familyPromise): ?Carbon
    {
        if ($studentPromise && $familyPromise) {
            return $studentPromise->gte($familyPromise) ? $studentPromise->copy() : $familyPromise->copy();
        }

        return $studentPromise?->copy() ?? $familyPromise?->copy();
    }

    /**
     * Traffic light for collection follow-up from last promise date.
     * green = today or future; yellow = overdue ≤7 days; red = older / no promise with balance.
     */
    private function computeFiscalTask(float $balance, ?Carbon $lastPromised): string
    {
        if ($balance <= 0) {
            return 'none';
        }

        if (!$lastPromised) {
            return 'red';
        }

        $today = Carbon::today();
        $promised = $lastPromised->copy()->startOfDay();

        if ($promised->gte($today)) {
            return 'green';
        }

        if ($promised->diffInDays($today) <= 7) {
            return 'yellow';
        }

        return 'red';
    }

    /**
     * Group siblings (same family_id) into family blocks with totals.
     */
    private function buildFamilyGroups(Collection $students): Collection
    {
        $groups = collect();
        $seenFamilies = [];

        foreach ($students as $student) {
            $familyId = $student['family_id'] ?? null;

            if ($familyId) {
                if (isset($seenFamilies[$familyId])) {
                    continue;
                }
                $seenFamilies[$familyId] = true;
                $children = $students->where('family_id', $familyId)->values();
            } else {
                $children = collect([$student]);
            }

            $owingChildren = $children->filter(fn ($c) => ($c['balance'] ?? 0) > 0);
            $fiscalStatuses = $owingChildren->pluck('fiscal_task')->all();
            $lastPromised = $children
                ->pluck('last_promised')
                ->filter()
                ->sortByDesc(fn ($d) => $d instanceof Carbon ? $d->timestamp : strtotime((string) $d))
                ->first();

            $first = $children->first();
            $childNames = $children->map(function ($c) {
                $adm = $c['admission_number'] ?? '';
                $name = $c['full_name'] ?? 'Student';
                return trim($adm ? "{$name} ({$adm})" : $name);
            })->filter()->values()->all();

            $parentParts = collect([
                $first['father_name'] ?? null,
                $first['mother_name'] ?? null,
                $first['guardian_name'] ?? null,
            ])->filter()->unique()->values();

            $groups->push([
                'family_id' => $familyId,
                'is_family' => $familyId && $children->count() > 1,
                'label' => $familyId && $children->count() > 1
                    ? 'Family · ' . $children->count() . ' children'
                    : ($first['full_name'] ?? 'Student'),
                'child_names' => $childNames,
                'parent_phone' => $first['parent_phone'] ?? null,
                'parent_names' => $parentParts->all(),
                'father_name' => $first['father_name'] ?? null,
                'mother_name' => $first['mother_name'] ?? null,
                // Term totals for invoiced/paid; combined outstanding for balance
                'total_invoiced' => (float) $children->sum('term_invoiced'),
                'total_paid' => (float) $children->sum('term_paid'),
                'paid_in_term_period' => (float) $children->sum('paid_in_term_period'),
                'balance' => (float) $children->sum('balance'),
                'last_promised' => $lastPromised,
                'fiscal_task' => $this->worstFiscalTask($fiscalStatuses),
                'children' => $children,
            ]);
        }

        return $groups->values();
    }

    /**
     * Treat blank / placeholder parent fields as empty.
     */
    private function cleanDisplayValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);
        if ($trimmed === '' || strcasecmp($trimmed, 'N/A') === 0 || strcasecmp($trimmed, 'NA') === 0 || $trimmed === '-') {
            return null;
        }

        return $trimmed;
    }

    /**
     * Worst status among owing children: red > yellow > green > none.
     */
    private function worstFiscalTask(array $statuses): string
    {
        $rank = ['red' => 3, 'yellow' => 2, 'green' => 1, 'none' => 0];
        $worst = 'none';
        $worstRank = 0;
        foreach ($statuses as $status) {
            $r = $rank[$status] ?? 0;
            if ($r > $worstRank) {
                $worstRank = $r;
                $worst = $status;
            }
        }

        return $worst;
    }
    
    /**
     * Get attendance statistics for a student since term start
     */
    private function getAttendanceStats($studentId, $termStartDate): array
    {
        $today = Carbon::today();
        
        // Get all attendance records since term start
        $attendanceRecords = Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$termStartDate, $today])
            ->get();
        
        $totalDays = $attendanceRecords->count();
        $present = $attendanceRecords->where('status', Attendance::STATUS_PRESENT)->count();
        $absent = $attendanceRecords->where('status', Attendance::STATUS_ABSENT)->count();
        $late = $attendanceRecords->where('status', Attendance::STATUS_LATE)->count();
        
        $attendanceRate = $totalDays > 0 ? round((($present + $late) / $totalDays) * 100, 1) : 0;
        
        return [
            'total_days' => $totalDays,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'attendance_rate' => $attendanceRate,
        ];
    }
    
    /**
     * Get payment plan status
     */
    private function getPaymentPlanStatus($paymentPlan): array
    {
        if (!$paymentPlan) {
            return [
                'status' => 'none',
                'progress' => 0,
                'next_due_date' => null,
            ];
        }
        
        $installments = $paymentPlan->installments;
        $totalInstallments = $installments->count();
        $paidInstallments = $installments->where('status', 'paid')->count();
        $overdueInstallments = $installments->where('status', 'overdue')->count();
        
        $progress = $totalInstallments > 0 ? round(($paidInstallments / $totalInstallments) * 100, 1) : 0;
        
        // Get next due installment
        $nextInstallment = $installments
            ->whereIn('status', ['pending', 'partial'])
            ->sortBy('due_date')
            ->first();
        
        $status = 'on_track';
        if ($overdueInstallments > 0) {
            $status = 'overdue';
        } elseif ($paidInstallments === $totalInstallments) {
            $status = 'completed';
        }
        
        return [
            'status' => $status,
            'progress' => $progress,
            'next_due_date' => $nextInstallment ? $nextInstallment->due_date : null,
        ];
    }
    
    /**
     * Get payment status label
     */
    private function getPaymentStatus($invoiced, $paid, $balance): string
    {
        if ($invoiced == 0) {
            return 'not_invoiced';
        }
        
        if ($balance <= 0) {
            return 'paid';
        }
        
        if ($paid > 0) {
            return 'partial';
        }
        
        return 'unpaid';
    }
    
    /**
     * Get balance brought forward data for a student
     */
    private function getBalanceBroughtForwardData($student, $balanceBroughtForwardVotehead, $invoice = null, $invoiceBalance = 0): array
    {
        $bbfAmount = 0;
        $bbfPaid = 0;
        $bbfBalance = 0;
        $paymentStatus = 'no_bbf';
        
        // Get balance brought forward from legacy data
        $legacyBf = StudentBalanceService::getBalanceBroughtForward($student);
        
        // Get balance brought forward from invoice items
        $invoiceBfAmount = 0;
        $invoiceBfPaid = 0;
        
        if ($balanceBroughtForwardVotehead) {
            $invoiceItems = InvoiceItem::whereHas('invoice', function($q) use ($student) {
                $q->where('student_id', $student->id)
                  ->where('status', '!=', 'reversed');
            })
            ->where('votehead_id', $balanceBroughtForwardVotehead->id)
            ->where('source', 'balance_brought_forward')
            ->get();
            
            if ($invoiceItems->isNotEmpty()) {
                foreach ($invoiceItems as $item) {
                    $itemPaid = $item->allocations()->sum('amount');
                    $itemBalance = max(0, $item->amount - ($item->discount_amount ?? 0) - $itemPaid);
                    
                    $invoiceBfAmount += $item->amount;
                    $invoiceBfPaid += $itemPaid;
                }
            }
        }
        
        // Use invoice BF if exists, otherwise use legacy BF
        if ($invoiceBfAmount > 0) {
            $bbfAmount = $invoiceBfAmount;
            $bbfPaid = $invoiceBfPaid;
            $bbfBalance = $invoiceBfAmount - $invoiceBfPaid;
        } elseif ($legacyBf > 0) {
            $bbfAmount = $legacyBf;
            // If student has paid (invoice balance <= 0), treat legacy BBF as cleared
            // Payments go to invoices; overpayment/cleared balance implies BBF was paid
            if (abs($invoiceBalance) < 0.01 || $invoiceBalance < 0) {
                $bbfPaid = $legacyBf;
                $bbfBalance = 0;
            } else {
                $bbfPaid = 0;
                $bbfBalance = $legacyBf;
            }
        }
        
        // Determine payment status
        if ($bbfAmount > 0) {
            if ($bbfBalance <= 0) {
                // BBF is cleared, check if invoice (excluding BBF) is also cleared
                // If BBF is cleared, check if invoice (which includes BBF) is also cleared
                // If invoice balance is 0 or less, both BBF and invoice are cleared
                // If invoice balance > 0, only BBF is cleared (invoice still has balance)
                if (abs($invoiceBalance) < 0.01) { // Use small epsilon for float comparison
                    $paymentStatus = 'cleared_bbf_and_invoice';
                } else {
                    $paymentStatus = 'cleared_bbf_only';
                }
            } else {
                // BBF is not fully cleared
                if ($bbfPaid > 0) {
                    $paymentStatus = 'bbf_partial';
                } else {
                    $paymentStatus = 'bbf_unpaid';
                }
            }
        }
        
        return [
            'amount' => $bbfAmount,
            'paid' => $bbfPaid,
            'balance' => $bbfBalance,
            'payment_status' => $paymentStatus,
        ];
    }
    
    /**
     * Extract term number from term name
     */
    private function extractTermNumber($termName): int
    {
        preg_match('/\d+/', $termName, $matches);
        return isset($matches[0]) ? (int)$matches[0] : 1;
    }
    
    /**
     * Export fee balance report (CSV, PDF download, or PDF print)
     * Supports ?format=csv|pdf|print - default is csv
     */
    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');

        if ($format === 'pdf') {
            return $this->exportPdf($request);
        }
        if ($format === 'print') {
            return $this->printPdf($request);
        }

        // CSV export
        $includeAmounts = filter_var($request->query('include_amounts', true), FILTER_VALIDATE_BOOLEAN);
        return response()->streamDownload(function () use ($request, $includeAmounts) {
            $data = $this->index($request)->getData();
            $students = $this->filterStudentsForExport(collect($data['students']), $request);
            
            $handle = fopen('php://output', 'w');
            
            $headers = ['Family ID', 'Admission No', 'Student Name', 'Class', 'Stream', 'Father Name', 'Father Phone', 'Mother Name', 'Mother Phone', 'Last Promised', 'Fiscal Task'];
            if ($includeAmounts) {
                $headers = array_merge($headers, ['Term Invoiced', 'Term Paid', 'Outstanding Balance', 'Balance %', 'Payment Status', 'BBF Outstanding', 'Days in School', 'Days Present', 'Days Absent', 'Attendance %', 'In School', 'Has Payment Plan', 'Plan Status', 'Next Installment']);
            }
            fputcsv($handle, $headers);

            $familyGroups = $this->buildFamilyGroups($students);
            foreach ($familyGroups as $group) {
                if (!empty($group['is_family'])) {
                    $familyRow = [
                        $group['family_id'] ?? '',
                        '',
                        $group['label'],
                        'FAMILY TOTAL',
                        '',
                        $group['father_name'] ?? '',
                        '',
                        $group['mother_name'] ?? '',
                        '',
                        $group['last_promised'] instanceof Carbon ? $group['last_promised']->format('Y-m-d') : '',
                        ucfirst($group['fiscal_task'] ?? 'none'),
                    ];
                    if ($includeAmounts) {
                        $familyRow = array_merge($familyRow, [
                            number_format($group['total_invoiced'], 2),
                            number_format($group['total_paid'], 2),
                            number_format($group['balance'], 2),
                            '',
                            'Family',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                        ]);
                    }
                    fputcsv($handle, $familyRow);
                }

                foreach ($group['children'] as $student) {
                    $lastPromised = $student['last_promised'] ?? null;
                    $row = [
                        $student['family_id'] ?? '',
                        $student['admission_number'],
                        $student['full_name'],
                        $student['classroom'],
                        $student['stream'] ?? '',
                        $student['father_name'] ?? '',
                        $student['father_phone'] ?? '',
                        $student['mother_name'] ?? '',
                        $student['mother_phone'] ?? '',
                        $lastPromised instanceof Carbon ? $lastPromised->format('Y-m-d') : '',
                        ucfirst($student['fiscal_task'] ?? 'none'),
                    ];
                    if ($includeAmounts) {
                        $bbfOutstanding = (($student['balance_brought_forward_balance'] ?? 0) > 0.009)
                            ? number_format($student['balance_brought_forward_balance'], 2)
                            : '';
                        $row = array_merge($row, [
                            number_format($student['term_invoiced'] ?? $student['total_invoiced'], 2),
                            number_format($student['term_paid'] ?? $student['total_paid'], 2),
                            number_format($student['balance'], 2),
                            $student['balance_percentage'] . '%',
                            ucfirst(str_replace('_', ' ', $student['payment_status'])),
                            $bbfOutstanding,
                            $student['attendance_days'],
                            $student['days_present'],
                            $student['days_absent'],
                            $student['attendance_rate'] . '%',
                            $student['is_in_school'] ? 'Yes' : 'No',
                            $student['has_payment_plan'] ? 'Yes' : 'No',
                            ucfirst(str_replace('_', ' ', $student['payment_plan_status'])),
                            $student['next_installment_date'] ? $student['next_installment_date']->format('Y-m-d') : '',
                        ]);
                    }
                    fputcsv($handle, $row);
                }
            }
            
            fclose($handle);
        }, 'fee_balance_report_' . now()->format('Y-m-d_His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Export fee balance list as PDF (download) - learners per class/stream with child name, balance, both parents' contacts
     */
    public function exportPdf(Request $request)
    {
        $data = $this->index($request)->getData();
        $students = $this->filterStudentsForExport(collect($data['students']), $request);
        $selectedTermId = $data['selectedTermId'] ?? null;
        $selectedTerm = $selectedTermId ? Term::with('academicYear')->find($selectedTermId) : Term::where('is_current', true)->with('academicYear')->first();

        $studentsByStream = $this->groupAndSortStudentsByStream($students);
        $includeAmounts = filter_var($request->query('include_amounts', true), FILTER_VALIDATE_BOOLEAN);

        $pdfService = new PDFExportService();
        return $pdfService->generatePDF('finance.fee_balances.pdf', [
            'studentsByStream' => $studentsByStream,
            'selectedTerm' => $selectedTerm,
            'logoBase64' => $this->getSchoolLogoBase64(),
            'includeAmounts' => $includeAmounts,
        ], [
            'filename' => 'fee_balance_list_' . now()->format('Y-m-d_His') . '.pdf',
            'stream' => false,
        ]);
    }

    /**
     * Print fee balance list (open in browser for printing)
     */
    public function printPdf(Request $request)
    {
        $data = $this->index($request)->getData();
        $students = $this->filterStudentsForExport(collect($data['students']), $request);
        $selectedTermId = $data['selectedTermId'] ?? null;
        $selectedTerm = $selectedTermId ? Term::with('academicYear')->find($selectedTermId) : Term::where('is_current', true)->with('academicYear')->first();

        $studentsByStream = $this->groupAndSortStudentsByStream($students);
        $includeAmounts = filter_var($request->query('include_amounts', true), FILTER_VALIDATE_BOOLEAN);

        $pdfService = new PDFExportService();
        return $pdfService->generatePDF('finance.fee_balances.pdf', [
            'studentsByStream' => $studentsByStream,
            'selectedTerm' => $selectedTerm,
            'logoBase64' => $this->getSchoolLogoBase64(),
            'includeAmounts' => $includeAmounts,
        ], [
            'filename' => 'fee_balance_list_' . now()->format('Y-m-d_His') . '.pdf',
            'stream' => true,
        ]);
    }

    /**
     * Filter students for export: exclude staff children and manually excluded IDs
     */
    private function filterStudentsForExport($students, Request $request)
    {
        $staffChildIds = $this->getStaffChildStudentIds();
        $excludeIds = array_filter((array) $request->input('exclude_ids', []));

        return $students->filter(function ($student) use ($staffChildIds, $excludeIds) {
            if (in_array($student['id'], $staffChildIds)) {
                return false;
            }
            if (in_array((string) $student['id'], $excludeIds) || in_array((int) $student['id'], $excludeIds)) {
                return false;
            }
            return true;
        })->values();
    }

    /**
     * Student IDs treated as staff children (category Staff and/or parent linked to a staff user).
     */
    private function getStaffChildStudentIds(): array
    {
        $ids = Student::query()
            ->whereHas('category', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['staff']))
            ->pluck('id');

        $staffParentIds = User::whereHas('staff')
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if (!empty($staffParentIds)) {
            $ids = $ids->merge(
                Student::whereIn('parent_id', $staffParentIds)->pluck('id')
            );
        }

        return $ids->unique()->values()->all();
    }

    /**
     * Group students by class+stream and sort by: Creche, Foundation, PP1, PP2, Grade 1-9
     */
    private function groupAndSortStudentsByStream($students)
    {
        $grouped = $students->groupBy(function ($student) {
            $classroom = $student['classroom'] ?? 'N/A';
            $stream = $student['stream'] ?? 'General';
            return $classroom . ' | ' . $stream;
        });

        return $grouped->sortBy(function ($students, $key) {
            $classroom = explode(' | ', $key)[0] ?? '';
            $name = strtolower(trim($classroom));
            if (strpos($name, 'creche') !== false) return 1;
            if (strpos($name, 'foundation') !== false) return 2;
            if (preg_match('/^pp1/', $name)) return 3;
            if (preg_match('/^pp2/', $name)) return 4;
            if (preg_match('/^grade\s*1(?!\d)/', $name)) return 5;
            if (preg_match('/^grade\s*2(?!\d)/', $name)) return 6;
            if (preg_match('/^grade\s*3(?!\d)/', $name)) return 7;
            if (preg_match('/^grade\s*4(?!\d)/', $name)) return 8;
            if (preg_match('/^grade\s*5(?!\d)/', $name)) return 9;
            if (preg_match('/^grade\s*6(?!\d)/', $name)) return 10;
            if (preg_match('/^grade\s*7(?!\d)/', $name)) return 11;
            if (preg_match('/^grade\s*8(?!\d)/', $name)) return 12;
            if (preg_match('/^grade\s*9(?!\d)/', $name)) return 13;
            return 1000;
        }, SORT_NATURAL);
    }

    /**
     * Get school logo as base64 data URI for PDF embedding
     */
    private function getSchoolLogoBase64(): ?string
    {
        $logo = setting('school_logo');
        $paths = [];
        if ($logo && storage_public()->exists($logo)) {
            $paths[] = storage_path('app/public/' . $logo);
        }
        if ($logo && file_exists(public_path('images/' . $logo))) {
            $paths[] = public_path('images/' . $logo);
        }
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $mime = mime_content_type($path) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }
        return null;
    }
}

