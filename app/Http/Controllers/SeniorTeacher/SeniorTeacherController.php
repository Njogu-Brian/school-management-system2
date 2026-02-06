<?php

namespace App\Http\Controllers\SeniorTeacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Attendance;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\Academics\StudentBehaviour;
use App\Models\Academics\Homework;
use App\Models\Academics\Exam;
use App\Models\Invoice;
use App\Models\Announcement;

class SeniorTeacherController extends Controller
{
    /**
     * Display the senior teacher dashboard
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        // Ensure user is a senior teacher
        if (!$user->hasRole('Senior Teacher')) {
            abort(403, 'Access denied. This dashboard is for Senior Teachers only.');
        }

        // Get supervised classrooms and staff
        $supervisedClassroomIds = $user->getSupervisedClassroomIds();
        $supervisedStaffIds = $user->getSupervisedStaffIds();
        
        // Also include teacher's own assigned classes
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        
        // Merge both supervised and assigned classrooms
        $allClassroomIds = array_unique(array_merge($supervisedClassroomIds, $assignedClassroomIds));
        
        // Build dashboard data
        $data = $this->buildSeniorTeacherDashboardData($request, $user, $allClassroomIds, $supervisedStaffIds);
        
        return view('senior_teacher.dashboard', $data);
    }

    /**
     * Build dashboard data for senior teacher
     */
    private function buildSeniorTeacherDashboardData(Request $request, User $user, array $classroomIds, array $staffIds): array
    {
        // Filters
        $defaultYearId = AcademicYear::latest('id')->value('id') ?? null;
        $defaultTermId = Term::latest('id')->value('id') ?? null;
        
        $filters = [
            'year_id'      => (int)($request->get('year_id') ?? $defaultYearId ?? 0),
            'term_id'      => (int)($request->get('term_id') ?? $defaultTermId ?? 0),
            'from'         => $request->get('from') ?? now()->subDays(30)->toDateString(),
            'to'           => $request->get('to') ?? now()->toDateString(),
            'classroom_id' => $request->get('classroom_id'),
        ];
        $today = now()->toDateString();

        // Students in supervised/assigned classrooms
        $students = empty($classroomIds) 
            ? Student::whereRaw('1 = 0') 
            : Student::whereIn('classroom_id', $classroomIds);
        
        $totalStudents = $students->count();
        $activeStudents = (clone $students)->where('status', 'Active')->count();
        
        // Supervised classrooms (from assigned campus)
        $supervisedClassroomIds = $user->getSupervisedClassroomIds();
        $supervisedClassrooms = empty($supervisedClassroomIds)
            ? collect()
            : Classroom::whereIn('id', $supervisedClassroomIds)
                ->withCount('students')
                ->get();

        // Supervised staff (from assigned campus)
        $supervisedStaff = empty($staffIds)
            ? collect()
            : Staff::whereIn('id', $staffIds)
                ->with(['user', 'position'])
                ->get();
        
        // Own assigned classes (as a teacher)
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        $assignedClassrooms = empty($assignedClassroomIds)
            ? collect()
            : Classroom::whereIn('id', $assignedClassroomIds)
                ->withCount('students')
                ->get();

        // Attendance stats for today
        $todayAttendance = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'total' => $activeStudents,
        ];
        
        if (!empty($classroomIds)) {
            $attendanceToday = Attendance::whereDate('date', $today)
                ->whereHas('student', function($q) use ($classroomIds) {
                    $q->whereIn('classroom_id', $classroomIds);
                })
                ->get();
            
            $todayAttendance['present'] = $attendanceToday->where('status', 'Present')->count();
            $todayAttendance['absent'] = $attendanceToday->where('status', 'Absent')->count();
            $todayAttendance['late'] = $attendanceToday->where('status', 'Late')->count();
        }

        // Recent student behaviours
        $recentBehaviours = StudentBehaviour::with(['student', 'staff', 'behaviourCategory'])
            ->whereHas('student', function($q) use ($classroomIds) {
                if (!empty($classroomIds)) {
                    $q->whereIn('classroom_id', $classroomIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->latest()
            ->take(10)
            ->get();

        // Pending homework
        $pendingHomework = Homework::with(['classroom', 'subject', 'staff'])
            ->where(function($q) use ($classroomIds) {
                if (!empty($classroomIds)) {
                    $q->whereIn('classroom_id', $classroomIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->where('due_date', '>=', $today)
            ->latest('due_date')
            ->take(10)
            ->get();

        // Fee balance summary
        $feeBalances = $this->calculateFeeBalances($classroomIds);

        // Recent announcements
        $announcements = Announcement::where('active', 1)
            ->latest()
            ->take(5)
            ->get();

        // Upcoming exams
        $upcomingExams = Exam::where('starts_on', '>=', $today)
            ->when($filters['year_id'], fn($q, $v) => $q->where('academic_year_id', $v))
            ->when($filters['term_id'], fn($q, $v) => $q->where('term_id', $v))
            ->orderBy('starts_on')
            ->take(5)
            ->get();

        // Attendance trends (last 7 days)
        $attendanceTrends = $this->getAttendanceTrends($classroomIds, 7);

        // KPIs
        $kpis = [
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'supervised_classrooms' => count($user->getSupervisedClassroomIds()),
            'supervised_staff' => count($staffIds),
            'assigned_classes' => count($user->getAssignedClassroomIds()),
            'attendance_rate' => $activeStudents > 0 
                ? round(($todayAttendance['present'] / $activeStudents) * 100, 1) 
                : 0,
            'pending_homework' => $pendingHomework->count(),
            'recent_behaviours' => $recentBehaviours->count(),
        ];

        return [
            'filters' => $filters,
            'kpis' => $kpis,
            'supervisedClassrooms' => $supervisedClassrooms,
            'supervisedStaff' => $supervisedStaff,
            'assignedClassrooms' => $assignedClassrooms,
            'todayAttendance' => $todayAttendance,
            'recentBehaviours' => $recentBehaviours,
            'pendingHomework' => $pendingHomework,
            'feeBalances' => $feeBalances,
            'announcements' => $announcements,
            'upcomingExams' => $upcomingExams,
            'attendanceTrends' => $attendanceTrends,
            'years' => AcademicYear::all(),
            'terms' => Term::all(),
            'classrooms' => empty($classroomIds) ? collect() : Classroom::whereIn('id', $classroomIds)->get(),
            'role' => 'senior_teacher',
        ];
    }

    /**
     * Calculate fee balances for supervised students
     */
    private function calculateFeeBalances(array $classroomIds): array
    {
        if (empty($classroomIds)) {
            return [
                'total_invoiced' => 0,
                'total_paid' => 0,
                'total_balance' => 0,
                'students_with_balance' => 0,
            ];
        }

        $studentIds = Student::whereIn('classroom_id', $classroomIds)->pluck('id');
        
        $totalInvoiced = Invoice::whereIn('student_id', $studentIds)->sum('total');
        $totalPaid = Invoice::whereIn('student_id', $studentIds)->sum('paid_amount');
        $totalBalance = $totalInvoiced - $totalPaid;
        $studentsWithBalance = Invoice::whereIn('student_id', $studentIds)
            ->whereRaw('total > paid_amount')
            ->distinct('student_id')
            ->count();

        return [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_balance' => $totalBalance,
            'students_with_balance' => $studentsWithBalance,
        ];
    }

    /**
     * Get attendance trends for the last N days
     */
    private function getAttendanceTrends(array $classroomIds, int $days = 7): array
    {
        if (empty($classroomIds)) {
            return [];
        }

        $trends = [];
        $startDate = now()->subDays($days - 1);
        
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->toDateString();
            
            $attendance = Attendance::whereDate('date', $date)
                ->whereHas('student', function($q) use ($classroomIds) {
                    $q->whereIn('classroom_id', $classroomIds);
                })
                ->get();
            
            $trends[] = [
                'date' => $date,
                'present' => $attendance->where('status', 'Present')->count(),
                'absent' => $attendance->where('status', 'Absent')->count(),
                'late' => $attendance->where('status', 'Late')->count(),
            ];
        }
        
        return $trends;
    }

    /**
     * Show all supervised classrooms (from assigned campus)
     */
    public function supervisedClassrooms()
    {
        $user = auth()->user();
        $classroomIds = $user->getSupervisedClassroomIds();
        $classrooms = Classroom::whereIn('id', $classroomIds)
            ->withCount('students')
            ->with(['teachers'])
            ->get();

        return view('senior_teacher.supervised_classrooms', compact('classrooms'));
    }

    /**
     * Show all supervised staff (from assigned campus)
     */
    public function supervisedStaff()
    {
        $user = auth()->user();
        $staffIds = $user->getSupervisedStaffIds();
        $staff = Staff::whereIn('id', $staffIds)
            ->with(['user', 'position', 'department'])
            ->get();

        return view('senior_teacher.supervised_staff', compact('staff'));
    }

    /**
     * Show students in supervised classrooms
     */
    public function students(Request $request)
    {
        $user = auth()->user();
        $classroomIds = array_unique(array_merge(
            $user->getSupervisedClassroomIds(),
            $user->getAssignedClassroomIds()
        ));
        
        $query = Student::whereIn('classroom_id', $classroomIds)
            ->with(['classroom', 'stream', 'parent']);
        
        // Apply filters
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        
        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->stream_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }
        
        $students = $query->orderBy('first_name')->paginate(50);
        $classrooms = Classroom::whereIn('id', $classroomIds)->get();
        $streams = Stream::all();
        
        return view('senior_teacher.students', compact('students', 'classrooms', 'streams'));
    }

    /**
     * Show student details
     */
    public function studentShow($id)
    {
        $user = auth()->user();
        $classroomIds = array_unique(array_merge(
            $user->getSupervisedClassroomIds(),
            $user->getAssignedClassroomIds()
        ));
        
        $student = Student::whereIn('classroom_id', $classroomIds)
            ->with(['classroom', 'stream', 'parent', 'trip', 'assignments'])
            ->findOrFail($id);
        
        // Get student's fee balance
        $feeBalance = [
            'total_invoiced' => Invoice::where('student_id', $id)->sum('total'),
            'total_paid' => Invoice::where('student_id', $id)->sum('paid_amount'),
        ];
        $feeBalance['balance'] = $feeBalance['total_invoiced'] - $feeBalance['total_paid'];
        
        // Get recent attendance
        $recentAttendance = Attendance::where('student_id', $id)
            ->latest('date')
            ->take(30)
            ->get();
        
        // Get recent behaviours
        $recentBehaviours = StudentBehaviour::where('student_id', $id)
            ->with(['staff', 'behaviourCategory'])
            ->latest()
            ->take(10)
            ->get();
        
        return view('senior_teacher.student_show', compact(
            'student', 
            'feeBalance', 
            'recentAttendance', 
            'recentBehaviours'
        ));
    }

    /**
     * View fee balances for supervised students
     */
    public function feeBalances(Request $request)
    {
        $user = auth()->user();
        $classroomIds = array_unique(array_merge(
            $user->getSupervisedClassroomIds(),
            $user->getAssignedClassroomIds()
        ));
        
        $query = Student::whereIn('classroom_id', $classroomIds)
            ->with(['classroom', 'stream']);
        
        // Apply filters
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        
        $allStudents = $query->orderBy('first_name')->get();
        
        // Enrich students with balance data
        $studentsWithBalances = $allStudents->map(function($student) {
            $totalInvoiced = Invoice::where('student_id', $student->id)->sum('total') ?? 0;
            $totalPaid = Invoice::where('student_id', $student->id)->sum('paid_amount') ?? 0;
            $balance = $totalInvoiced - $totalPaid;
            
            $student->total_invoiced = $totalInvoiced;
            $student->total_paid = $totalPaid;
            $student->balance = $balance;
            
            return $student;
        });
        
        // Apply balance status filter
        if ($request->filled('balance_status')) {
            switch ($request->balance_status) {
                case 'with_balance':
                    $studentsWithBalances = $studentsWithBalances->filter(fn($s) => $s->balance > 0);
                    break;
                case 'cleared':
                    $studentsWithBalances = $studentsWithBalances->filter(fn($s) => $s->balance == 0);
                    break;
                case 'overpaid':
                    $studentsWithBalances = $studentsWithBalances->filter(fn($s) => $s->balance < 0);
                    break;
            }
        }
        
        // Paginate manually
        $page = $request->get('page', 1);
        $perPage = 50;
        $total = $studentsWithBalances->count();
        $items = $studentsWithBalances->slice(($page - 1) * $perPage, $perPage)->values();
        $students = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        $classrooms = Classroom::whereIn('id', $classroomIds)->get();
        
        return view('senior_teacher.fee_balances', compact('students', 'classrooms'));
    }
}

