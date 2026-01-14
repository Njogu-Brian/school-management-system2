<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\FeePaymentPlan;
use App\Models\Term;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FeeBalanceController extends Controller
{
    /**
     * Display fee balance report with attendance tracking
     */
    public function index(Request $request)
    {
        // Get current term
        $currentTerm = Term::where('is_current', true)->first();
        
        // Get filters
        $classroomId = $request->input('classroom_id');
        $balanceStatus = $request->input('balance_status');
        $attendanceFilter = $request->input('attendance_filter');
        $paymentPlanFilter = $request->input('payment_plan_filter');
        $year = $request->input('year', now()->year);
        $termNumber = $request->input('term', $currentTerm ? $this->extractTermNumber($currentTerm->name) : 1);
        
        // Build student query
        $studentsQuery = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->with(['classroom', 'stream', 'parent']);
        
        // Apply classroom filter
        if ($classroomId) {
            $studentsQuery->where('classroom_id', $classroomId);
        }
        
        // Get students
        $students = $studentsQuery->get();
        
        // Enrich each student with financial and attendance data
        $enrichedStudents = $students->map(function ($student) use ($year, $termNumber, $currentTerm, $balanceStatus, $attendanceFilter, $paymentPlanFilter) {
            // Get invoice for this term
            $invoice = Invoice::where('student_id', $student->id)
                ->where('year', $year)
                ->where('term', $termNumber)
                ->first();
            
            $totalInvoiced = $invoice ? $invoice->total : 0;
            $totalPaid = $invoice ? $invoice->paid_amount : 0;
            $balance = $invoice ? $invoice->balance : 0;
            
            // Get attendance data since term start
            $termStartDate = $currentTerm ? $currentTerm->opening_date : Carbon::now()->startOfMonth();
            $attendanceData = $this->getAttendanceStats($student->id, $termStartDate);
            
            // Get payment plan status
            $paymentPlan = $invoice ? FeePaymentPlan::where('invoice_id', $invoice->id)
                ->where('status', 'active')
                ->with('installments')
                ->first() : null;
            
            $paymentPlanStatus = $this->getPaymentPlanStatus($paymentPlan);
            
            return [
                'id' => $student->id,
                'admission_number' => $student->admission_number,
                'full_name' => $student->full_name,
                'classroom' => $student->classroom ? $student->classroom->name : 'N/A',
                'stream' => $student->stream ? $student->stream->name : null,
                'parent_phone' => $student->parent ? ($student->parent->father_phone ?? $student->parent->mother_phone ?? $student->parent->guardian_phone ?? 'N/A') : 'N/A',
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'balance' => $balance,
                'balance_percentage' => $totalInvoiced > 0 ? round(($balance / $totalInvoiced) * 100, 1) : 0,
                'payment_status' => $this->getPaymentStatus($totalInvoiced, $totalPaid, $balance),
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
            ];
        });
        
        // Apply filters
        $filteredStudents = $enrichedStudents;
        
        // Balance status filter
        if ($balanceStatus) {
            $filteredStudents = $filteredStudents->filter(function ($student) use ($balanceStatus) {
                switch ($balanceStatus) {
                    case 'with_balance':
                        return $student['balance'] > 0;
                    case 'cleared':
                        return $student['balance'] <= 0 && $student['total_invoiced'] > 0;
                    case 'overpaid':
                        return $student['balance'] < 0;
                    case 'not_invoiced':
                        return $student['total_invoiced'] == 0;
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
        
        // Categorize students
        $clearedStudents = $filteredStudents->filter(function ($s) {
            return $s['balance'] <= 0 && $s['total_invoiced'] > 0;
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
        
        // Get view filter (all, cleared, partial, unpaid-present, unpaid-absent)
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
            default:
                // Show all - combine all categories
                $displayStudents = $filteredStudents;
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
        ];
        
        // Calculate counts for tabs
        $counts = [
            'all' => $filteredStudents->count(),
            'cleared' => $clearedStudents->count(),
            'partial' => $partialStudents->count(),
            'unpaid-present' => $unpaidPresent->count(),
            'unpaid-absent' => $unpaidAbsent->count(),
        ];
        
        // Sort displayed students
        $sortBy = $request->input('sort_by', 'balance');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $displayStudents = $displayStudents->sortBy($sortBy, SORT_REGULAR, $sortOrder === 'desc')->values();
        
        // Get classrooms for filter
        $classrooms = Classroom::orderBy('name')->get();
        
        return view('finance.fee_balances.index', [
            'students' => $displayStudents,
            'summary' => $summary,
            'counts' => $counts,
            'view' => $view,
            'classrooms' => $classrooms,
            'currentTerm' => $currentTerm,
            'filters' => $request->all(),
        ]);
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
     * Extract term number from term name
     */
    private function extractTermNumber($termName): int
    {
        preg_match('/\d+/', $termName, $matches);
        return isset($matches[0]) ? (int)$matches[0] : 1;
    }
    
    /**
     * Export fee balance report
     */
    public function export(Request $request)
    {
        // Reuse the same logic as index but export to Excel/CSV
        // This will be similar to index() but return a download response
        
        return response()->streamDownload(function () use ($request) {
            $data = $this->index($request)->getData();
            $students = $data['students'];
            
            $handle = fopen('php://output', 'w');
            
            // Headers
            fputcsv($handle, [
                'Admission No',
                'Student Name',
                'Class',
                'Stream',
                'Parent Phone',
                'Total Invoiced',
                'Total Paid',
                'Balance',
                'Balance %',
                'Payment Status',
                'Days in School',
                'Days Present',
                'Days Absent',
                'Attendance %',
                'In School',
                'Has Payment Plan',
                'Plan Status',
                'Next Installment',
            ]);
            
            // Data rows
            foreach ($students as $student) {
                fputcsv($handle, [
                    $student['admission_number'],
                    $student['full_name'],
                    $student['classroom'],
                    $student['stream'] ?? '',
                    $student['parent_phone'],
                    number_format($student['total_invoiced'], 2),
                    number_format($student['total_paid'], 2),
                    number_format($student['balance'], 2),
                    $student['balance_percentage'] . '%',
                    ucfirst(str_replace('_', ' ', $student['payment_status'])),
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
            
            fclose($handle);
        }, 'fee_balance_report_' . now()->format('Y-m-d_His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}

