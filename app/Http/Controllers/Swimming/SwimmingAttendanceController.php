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
     * Store attendance
     */
    public function store(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'date' => 'required|date',
            'student_ids' => 'required|array',
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
        $studentIds = $request->student_ids;
        
        try {
            $results = $this->attendanceService->markBulkAttendance(
                $classroom,
                $date,
                $studentIds,
                $user
            );
            
            $successCount = count($results['success']);
            $failedCount = count($results['failed']);
            
            if ($failedCount > 0) {
                return redirect()->back()
                    ->with('warning', "Attendance marked for {$successCount} students. {$failedCount} failed.")
                    ->with('failed_students', $results['failed']);
            }
            
            return redirect()->route('swimming.attendance.create', [
                'classroom_id' => $classroom->id,
                'date' => $request->date,
            ])->with('success', "Attendance marked successfully for {$successCount} students.");
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to mark attendance: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * View attendance records
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
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
        if ($request->filled('classroom_id')) {
            $classroomId = $request->classroom_id;
            if ($this->canAccessClassroom($user, $classroomId)) {
                $query->where('classroom_id', $classroomId);
            }
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }
        
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        $attendance = $query->orderBy('attendance_date', 'desc')
            ->orderBy('classroom_id')
            ->paginate(50);
        
        $classrooms = $this->getAccessibleClassrooms($user);
        
        return view('swimming.attendance.index', [
            'attendance' => $attendance,
            'classrooms' => $classrooms,
            'filters' => $request->only(['classroom_id', 'date_from', 'date_to', 'payment_status']),
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
