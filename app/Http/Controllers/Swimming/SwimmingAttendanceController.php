<?php

namespace App\Http\Controllers\Swimming;

use App\Http\Controllers\Controller;
use App\Models\{
    SwimmingAttendance, Student
};
use App\Models\Academics\Classroom;
use App\Services\SwimmingAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SwimmingAttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(SwimmingAttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Show attendance form for a class
     */
    public function create(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $date = $request->get('date', now()->toDateString());
        
        $user = Auth::user();
        
        // Get accessible classrooms
        $classrooms = $this->getAccessibleClassrooms($user);
        
        if (empty($classrooms->pluck('id')->toArray())) {
            abort(403, 'You do not have access to any classrooms.');
        }
        
        // Default to first accessible classroom if none selected
        if (!$classroomId) {
            $classroomId = $classrooms->first()->id;
        }
        
        $classroom = Classroom::findOrFail($classroomId);
        
        // Verify access
        if (!$this->canAccessClassroom($user, $classroom->id)) {
            abort(403, 'You do not have access to this classroom.');
        }
        
        // Get students in class
        $students = Student::where('classroom_id', $classroom->id)
            ->where('archive', 0)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        
        // Get existing attendance
        $attendanceRecords = SwimmingAttendance::where('classroom_id', $classroom->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');
        
        return view('swimming.attendance.create', [
            'classrooms' => $classrooms,
            'selected_classroom' => $classroom,
            'students' => $students,
            'attendance_records' => $attendanceRecords,
            'selected_date' => $date,
            'per_visit_cost' => $this->attendanceService->getPerVisitCost(),
        ]);
    }

    /**
     * Store attendance (supports both initial marking and updates)
     * Uses sync method to handle marking new students and unmarking removed students
     */
    public function store(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'date' => 'required|date',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'notes' => 'nullable|string',
        ]);
        
        $user = Auth::user();
        $classroom = Classroom::findOrFail($request->classroom_id);
        
        // Verify access
        if (!$this->canAccessClassroom($user, $classroom->id)) {
            abort(403, 'You do not have access to this classroom.');
        }
        
        $date = Carbon::parse($request->date);
        // student_ids can be empty if all students are being unmarked
        $studentIds = $request->student_ids ?? [];
        
        try {
            // Use sync method to handle both marking new and unmarking removed students
            $results = $this->attendanceService->syncBulkAttendance(
                $classroom,
                $date,
                $studentIds,
                $user
            );
            
            $markedCount = count($results['marked']);
            $unmarkedCount = count($results['unmarked']);
            $alreadyMarkedCount = count($results['already_marked']);
            $failedCount = count($results['failed']);
            
            // Build success message
            $messages = [];
            if ($markedCount > 0) {
                $messages[] = "{$markedCount} student(s) newly marked and billed";
            }
            if ($unmarkedCount > 0) {
                $totalRefunded = array_sum(array_column($results['unmarked'], 'refunded_amount'));
                $messages[] = "{$unmarkedCount} student(s) unmarked (Ksh " . number_format($totalRefunded, 2) . " refunded to wallets)";
            }
            if ($alreadyMarkedCount > 0) {
                $messages[] = "{$alreadyMarkedCount} student(s) already marked (no change)";
            }
            
            $successMessage = !empty($messages) ? implode('. ', $messages) . '.' : 'Attendance saved.';
            
            if ($failedCount > 0) {
                return redirect()->route('swimming.attendance.create', [
                    'classroom_id' => $classroom->id,
                    'date' => $request->date,
                ])->with('warning', "{$successMessage} {$failedCount} operation(s) failed.")
                  ->with('failed_students', $results['failed']);
            }
            
            return redirect()->route('swimming.attendance.create', [
                'classroom_id' => $classroom->id,
                'date' => $request->date,
            ])->with('success', $successMessage);
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to save attendance: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * View attendance records - merged with daily report
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $classrooms = $this->getAccessibleClassrooms($user);
        
        // Check if viewing a single date (daily report mode)
        $viewMode = $request->get('view_mode', 'list'); // 'list' or 'daily'
        $date = $request->get('date', $request->get('date_from'));
        $classroomId = $request->get('classroom_id');
        
        // If date is provided and date_from == date_to (or only date provided), use daily report mode
        if ($date && (!$request->filled('date_to') || $request->date_from == $request->date_to)) {
            $viewMode = 'daily';
            
            $query = SwimmingAttendance::with(['student', 'classroom'])
                ->join('students', 'swimming_attendance.student_id', '=', 'students.id')
                ->whereDate('swimming_attendance.attendance_date', $date);
            
            // Filter by classroom access
            if (!$user->hasAnyRole(['Super Admin', 'Admin'])) {
                $classroomIds = $this->getAccessibleClassroomIds($user);
                if (empty($classroomIds)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('swimming_attendance.classroom_id', $classroomIds);
                }
            }
            
            if ($classroomId && $this->canAccessClassroom($user, $classroomId)) {
                $query->where('swimming_attendance.classroom_id', $classroomId);
            }
            
            $attendance = $query->select('swimming_attendance.*')
                ->orderBy('swimming_attendance.classroom_id')
                ->orderBy('students.first_name')
                ->get();
            
            // Load wallet balances for all students to determine actual payment status
            $studentIds = $attendance->pluck('student_id')->unique();
            $wallets = \App\Models\SwimmingWallet::whereIn('student_id', $studentIds)
                ->pluck('balance', 'student_id');
            
            // Add wallet balance to each attendance record for the view
            $attendance->each(function($record) use ($wallets) {
                $walletBalance = $wallets->get($record->student_id, 0);
                $record->wallet_balance = $walletBalance;
                $record->is_actually_paid = $record->payment_status === 'paid' && $walletBalance >= 0;
            });
            
            $attendance = $attendance->groupBy('classroom_id');
            
            return view('swimming.attendance.index', [
                'attendance' => $attendance,
                'classrooms' => $classrooms,
                'filters' => $request->only(['classroom_id', 'date', 'date_from', 'date_to', 'payment_status']),
                'view_mode' => $viewMode,
                'selected_date' => $date,
                'selected_classroom_id' => $classroomId,
            ]);
        }
        
        // List mode - paginated records
        $query = SwimmingAttendance::with(['student', 'classroom', 'markedBy']);
        
        // Filter by classroom access
        if (!$user->hasAnyRole(['Super Admin', 'Admin'])) {
            $classroomIds = $this->getAccessibleClassroomIds($user);
            if (empty($classroomIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('classroom_id', $classroomIds);
            }
        }
        
        // Filters
        if ($classroomId && $this->canAccessClassroom($user, $classroomId)) {
            $query->where('classroom_id', $classroomId);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }
        
        // Note: payment_status filter based on wallet balance is done in view
        // since we need to join with wallet data for accurate filtering
        
        $attendance = $query->orderBy('attendance_date', 'desc')
            ->orderBy('classroom_id')
            ->paginate(50);
        
        // Load wallet balances for accurate payment status display
        $studentIds = $attendance->pluck('student_id')->unique();
        $wallets = \App\Models\SwimmingWallet::whereIn('student_id', $studentIds)
            ->pluck('balance', 'student_id');
        
        // Add wallet balance to each attendance record
        $attendance->each(function($record) use ($wallets) {
            $record->wallet_balance = $wallets->get($record->student_id, 0);
        });
        
        return view('swimming.attendance.index', [
            'attendance' => $attendance,
            'classrooms' => $classrooms,
            'filters' => $request->only(['classroom_id', 'date', 'date_from', 'date_to', 'payment_status']),
            'view_mode' => 'list',
            'selected_date' => null,
            'selected_classroom_id' => $classroomId,
        ]);
    }

    /**
     * Retry payment for unpaid attendance
     */
    public function retryPayment(SwimmingAttendance $attendance)
    {
        $user = Auth::user();
        
        // Verify access
        if (!$this->canAccessClassroom($user, $attendance->classroom_id)) {
            abort(403, 'You do not have access to this attendance record.');
        }
        
        try {
            $success = $this->attendanceService->retryPayment($attendance);
            
            if ($success) {
                return redirect()->back()->with('success', 'Payment processed successfully.');
            } else {
                return redirect()->back()->with('error', 'Insufficient wallet balance or payment failed.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to process payment: ' . $e->getMessage());
        }
    }

    /**
     * Bulk retry payments for unpaid attendance
     */
    public function bulkRetryPayments(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Only administrators can bulk retry payments.');
        }
        
        $attendanceIds = $request->input('attendance_ids', []);
        
        try {
            $results = $this->attendanceService->bulkRetryPayments(!empty($attendanceIds) ? $attendanceIds : null);
            
            $message = "Processed {$results['processed']} attendance record(s).";
            if ($results['insufficient'] > 0) {
                $message .= " {$results['insufficient']} had insufficient wallet balance.";
            }
            if ($results['failed'] > 0) {
                $message .= " {$results['failed']} failed.";
            }
            
            return redirect()->back()
                ->with($results['failed'] > 0 ? 'warning' : 'success', $message);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to bulk retry payments: ' . $e->getMessage());
        }
    }

    /**
     * Send payment reminders for unpaid swimming attendance
     */
    public function sendPaymentReminders(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Only administrators can send payment reminders.');
        }
        
        $request->validate([
            'channels' => 'required|array',
            'channels.*' => 'in:sms,email,whatsapp',
            'date' => 'nullable|date',
            'classroom_id' => 'nullable|exists:classrooms,id',
        ]);
        
        $channels = $request->input('channels', []);
        $date = $request->input('date');
        $classroomId = $request->input('classroom_id');
        
        try {
            $results = $this->attendanceService->sendPaymentReminders($channels, $date, $classroomId);
            
            $message = "Payment reminders sent via " . implode(' and ', $channels) . ". ";
            $message .= "Sent to {$results['sent']} parent(s). ";
            if ($results['failed'] > 0) {
                $message .= "{$results['failed']} failed (no contact info or errors).";
            }
            
            return redirect()->back()
                ->with($results['failed'] > 0 ? 'warning' : 'success', $message);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to send payment reminders: ' . $e->getMessage());
        }
    }

    /**
     * Get accessible classrooms for user
     */
    protected function getAccessibleClassrooms($user)
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return Classroom::orderBy('name')->get();
        }
        
        $classroomIds = $this->getAccessibleClassroomIds($user);
        
        if (empty($classroomIds)) {
            return collect();
        }
        
        return Classroom::whereIn('id', $classroomIds)->orderBy('name')->get();
    }

    /**
     * Get accessible classroom IDs for user
     */
    protected function getAccessibleClassroomIds($user): array
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return Classroom::pluck('id')->toArray();
        }
        
        $assignedIds = $user->getAssignedClassroomIds();
        $supervisedIds = $user->getSupervisedClassroomIds();
        
        return array_unique(array_merge($assignedIds, $supervisedIds));
    }

    /**
     * Check if user can access classroom
     */
    protected function canAccessClassroom($user, int $classroomId): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }
        
        $accessibleIds = $this->getAccessibleClassroomIds($user);
        return in_array($classroomId, $accessibleIds);
    }
}
