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
use App\Support\AcademicContext;
use App\Models\Academics\ExamMark;

class SeniorTeacherController extends Controller
{
    /**
     * Display the senior teacher dashboard
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        // Ensure user is a senior teacher
        if (! $user->hasAnyRole(['Senior Teacher', 'Deputy Senior Teacher'])) {
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
        $defaultYearId = AcademicContext::resolveYearId(null);
        $yearId = (int) (AcademicContext::resolveYearId(
            $request->filled('year_id') ? (int) $request->get('year_id') : null
        ) ?: 0);
        $defaultTermId = $yearId ? (int) (AcademicContext::resolveTermId($yearId, null) ?: 0) : 0;
        $termId = $yearId
            ? (int) (AcademicContext::resolveTermId(
                $yearId,
                $request->filled('term_id') ? (int) $request->get('term_id') : null
            ) ?: 0)
            : 0;
        $selectedTerm = $termId ? Term::find($termId) : null;
        $hasExplicitDates = $request->filled('from') && $request->filled('to');
        if ($hasExplicitDates) {
            $from = $request->get('from');
            $to = $request->get('to');
        } elseif ($selectedTerm?->opening_date && $selectedTerm?->closing_date) {
            $from = $selectedTerm->opening_date->toDateString();
            $close = $selectedTerm->closing_date->toDateString();
            $todayStr = now()->toDateString();
            $to = ($todayStr >= $from && $todayStr <= $close) ? $todayStr : $close;
        } else {
            $from = now()->subDays(30)->toDateString();
            $to = now()->toDateString();
        }

        $filters = [
            'year_id'      => $yearId,
            'term_id'      => $termId,
            'from'         => $from,
            'to'           => $to,
            'classroom_id' => $request->get('classroom_id'),
            'stream_id'    => $request->get('stream_id'),
        ];
        $today = now()->toDateString();

        if ($request->filled('classroom_id')) {
            $classroomIds = array_values(array_intersect($classroomIds, [(int) $request->get('classroom_id')]));
        }

        // Students in supervised/assigned classrooms
        $students = empty($classroomIds) 
            ? Student::whereRaw('1 = 0') 
            : Student::whereIn('classroom_id', $classroomIds)
                ->when($filters['stream_id'], fn ($q) => $q->where('stream_id', $filters['stream_id']));
        
        $totalStudents = (clone $students)->where('status', 'active')->count();
        $activeStudents = $totalStudents;
        
        // Supervised classrooms (from assigned campus), with stream-level rows for display
        $supervisedClassroomIds = $user->getSupervisedClassroomIds();
        $supervisedClassrooms = empty($supervisedClassroomIds)
            ? collect()
            : Classroom::whereIn('id', $supervisedClassroomIds)
                ->withCount('students')
                ->with(['primaryStreams'])
                ->get();

        $supervisedStreamRows = collect();
        if ($supervisedClassrooms->isNotEmpty()) {
            $classIds = $supervisedClassrooms->pluck('id');
            $countRows = Student::query()
                ->selectRaw('classroom_id, stream_id, COUNT(*) as c')
                ->whereIn('classroom_id', $classIds)
                ->groupBy('classroom_id', 'stream_id')
                ->get();
            $countMap = [];
            foreach ($countRows as $row) {
                $countMap[$row->classroom_id][$row->stream_id ?? 0] = (int) $row->c;
            }

            foreach ($supervisedClassrooms as $classroom) {
                $streams = $classroom->primaryStreams;
                if ($streams->isNotEmpty()) {
                    foreach ($streams as $stream) {
                        $supervisedStreamRows->push((object) [
                            'classroom' => $classroom,
                            'stream' => $stream,
                            'student_count' => $countMap[$classroom->id][$stream->id] ?? 0,
                        ]);
                    }
                } else {
                    $supervisedStreamRows->push((object) [
                        'classroom' => $classroom,
                        'stream' => null,
                        'student_count' => array_sum($countMap[$classroom->id] ?? []),
                    ]);
                }
            }
        }

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
                ->whereHas('student', function ($q) use ($classroomIds) {
                    $q->whereIn('classroom_id', $classroomIds);
                })
                ->select('student_id', 'status')
                ->get()
                ->unique('student_id');

            $todayAttendance['present'] = $attendanceToday->where('status', Attendance::STATUS_PRESENT)->count();
            $todayAttendance['absent'] = $attendanceToday->where('status', Attendance::STATUS_ABSENT)->count();
            $todayAttendance['late'] = $attendanceToday->where('status', Attendance::STATUS_LATE)->count();
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
        $feeBalances = $this->calculateFeeBalances($classroomIds, (int) $filters['term_id']);

        // Recent announcements
        $announcementQuery = Announcement::query();
        if (\Illuminate\Support\Facades\Schema::hasColumn('announcements', 'is_active')) {
            $announcementQuery->where('is_active', 1);
        } elseif (\Illuminate\Support\Facades\Schema::hasColumn('announcements', 'active')) {
            $announcementQuery->where('active', 1);
        }
        $announcements = $announcementQuery->latest()->take(5)->get();

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

        $classSnapshots = $this->classSnapshots($supervisedStreamRows, $today, $filters['term_id']);
        if (! empty($filters['stream_id'])) {
            $classSnapshots = $classSnapshots->filter(fn ($row) => (int) ($row->stream?->id) === (int) $filters['stream_id'])->values();
        }

        $missingMarks = $this->missingMarks($classroomIds, (int) $filters['term_id']);
        $attendanceConcerns = $this->attendanceConcerns($classroomIds, $today);
        $interventionLearners = $this->interventionLearners($classroomIds, $today);
        $teacherActivity = $this->teacherActivityToday($supervisedStaff, $today);

        return [
            'filters' => $filters,
            'kpis' => $kpis,
            'supervisedClassrooms' => $supervisedClassrooms,
            'supervisedStreamRows' => $supervisedStreamRows,
            'classSnapshots' => $classSnapshots,
            'missingMarks' => $missingMarks,
            'attendanceConcerns' => $attendanceConcerns,
            'interventionLearners' => $interventionLearners,
            'teacherActivity' => $teacherActivity,
            'supervisedStaff' => $supervisedStaff,
            'assignedClassrooms' => $assignedClassrooms,
            'todayAttendance' => $todayAttendance,
            'recentBehaviours' => $recentBehaviours,
            'pendingHomework' => $pendingHomework,
            'feeBalances' => $feeBalances,
            'announcements' => $announcements,
            'upcomingExams' => $upcomingExams,
            'attendanceTrends' => $attendanceTrends,
            'years' => AcademicContext::years(),
            'terms' => AcademicContext::allTermsForSelect(),
            'defaultYearId' => $defaultYearId,
            'defaultTermId' => $defaultTermId,
            'classrooms' => empty($classroomIds) ? collect() : Classroom::whereIn('id', $classroomIds)->get(),
            'streams' => empty($classroomIds) ? collect() : Stream::whereIn('classroom_id', $classroomIds)->orderBy('name')->get(),
            'role' => 'senior_teacher',
        ];
    }

    /**
     * Live class cards: attendance today, assessment completion and average only when marks exist.
     */
    private function classSnapshots($streamRows, string $today, int $termId): \Illuminate\Support\Collection
    {
        if ($streamRows->isEmpty()) {
            return collect();
        }

        $classroomIds = $streamRows->pluck('classroom.id')->unique()->values();
        $students = Student::query()
            ->whereIn('classroom_id', $classroomIds)
            ->where('status', 'active')
            ->get(['id', 'classroom_id', 'stream_id']);

        $attendance = Attendance::whereDate('date', $today)
            ->whereIn('student_id', $students->pluck('id'))
            ->get(['student_id', 'status'])
            ->unique('student_id')
            ->keyBy('student_id');

        $examQuery = Exam::query()->whereIn('classroom_id', $classroomIds);
        if ($termId > 0) {
            $examQuery->where('term_id', $termId);
        }
        $exams = $examQuery->get(['id', 'classroom_id']);
        $marks = collect();
        if ($exams->isNotEmpty()) {
            $marks = ExamMark::query()
                ->whereIn('exam_id', $exams->pluck('id'))
                ->whereIn('student_id', $students->pluck('id'))
                ->get(['exam_id', 'student_id', 'score_raw', 'score_moderated', 'endterm_score', 'midterm_score', 'opener_score']);
        }

        return $streamRows->map(function ($row) use ($students, $attendance, $exams, $marks) {
            $classStudents = $students->where('classroom_id', $row->classroom->id);
            if ($row->stream) {
                $classStudents = $classStudents->where('stream_id', $row->stream->id);
            }
            $studentIds = $classStudents->pluck('id');
            $learnerCount = $studentIds->count();
            $present = $studentIds->filter(fn ($id) => optional($attendance->get($id))->status === Attendance::STATUS_PRESENT)->count();
            $attendancePct = $learnerCount > 0 ? round(($present / $learnerCount) * 100, 1) : null;

            $classExamIds = $exams->where('classroom_id', $row->classroom->id)->pluck('id');
            $classMarks = $marks->whereIn('exam_id', $classExamIds)->whereIn('student_id', $studentIds);
            $completion = null;
            $average = null;
            if ($classMarks->isNotEmpty()) {
                $entered = $classMarks->filter(function ($mark) {
                    return $mark->score_moderated !== null
                        || $mark->score_raw !== null
                        || $mark->endterm_score !== null
                        || $mark->midterm_score !== null
                        || $mark->opener_score !== null;
                });
                $completion = round(($entered->count() / $classMarks->count()) * 100, 1);
                $scored = $entered->map(function ($mark) {
                    foreach (['score_moderated', 'score_raw', 'endterm_score', 'midterm_score', 'opener_score'] as $col) {
                        if ($mark->{$col} !== null) {
                            return (float) $mark->{$col};
                        }
                    }
                    return null;
                })->filter(fn ($v) => $v !== null);
                $average = $scored->isNotEmpty() ? round($scored->avg(), 1) : null;
            }

            return (object) [
                'classroom' => $row->classroom,
                'stream' => $row->stream,
                'student_count' => $learnerCount ?: $row->student_count,
                'attendance_pct' => $attendancePct,
                'assessment_completion' => $completion,
                'average' => $average,
                'url' => route('senior_teacher.students.index', array_filter([
                    'classroom_id' => $row->classroom->id,
                    'stream_id' => $row->stream?->id,
                ])),
            ];
        });
    }

    /**
     * Calculate fee balances for supervised students
     */
    private function calculateFeeBalances(array $classroomIds, int $termId = 0): array
    {
        $empty = [
            'total_invoiced' => 0,
            'total_paid' => 0,
            'total_balance' => 0,
            'students_with_balance' => 0,
            'overdue' => 0,
        ];

        if (empty($classroomIds)) {
            return $empty;
        }

        $studentIds = Student::whereIn('classroom_id', $classroomIds)->pluck('id');
        if ($studentIds->isEmpty()) {
            return $empty;
        }

        $query = Invoice::query()
            ->whereIn('student_id', $studentIds)
            ->whereNull('reversed_at');
        if ($termId > 0) {
            $query->where('term_id', $termId);
        }

        $open = (clone $query)->whereIn('status', ['unpaid', 'partial']);

        return [
            'total_invoiced' => (float) (clone $query)->sum('total'),
            'total_paid' => (float) (clone $query)->sum('paid_amount'),
            'total_balance' => (float) (clone $open)->sum('balance'),
            'students_with_balance' => (int) (clone $open)->where('balance', '>', 0)->distinct()->count('student_id'),
            'overdue' => (float) (clone $open)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->sum('balance'),
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

        $startDate = now()->subDays($days - 1)->toDateString();
        $endDate = now()->toDateString();
        $rows = Attendance::query()
            ->selectRaw('date, status, COUNT(DISTINCT student_id) as cnt')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_ABSENT, Attendance::STATUS_LATE])
            ->whereHas('student', fn ($q) => $q->whereIn('classroom_id', $classroomIds))
            ->groupBy('date', 'status')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        $trends = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - 1 - $i)->toDateString();
            $dayRows = $rows->get($date, collect());
            $trends[] = [
                'date' => $date,
                'present' => (int) optional($dayRows->firstWhere('status', Attendance::STATUS_PRESENT))->cnt,
                'absent' => (int) optional($dayRows->firstWhere('status', Attendance::STATUS_ABSENT))->cnt,
                'late' => (int) optional($dayRows->firstWhere('status', Attendance::STATUS_LATE))->cnt,
            ];
        }

        return $trends;
    }

    private function missingMarks(array $classroomIds, int $termId)
    {
        if (empty($classroomIds)) {
            return collect();
        }

        return Exam::query()
            ->whereIn('classroom_id', $classroomIds)
            ->when($termId > 0, fn ($q) => $q->where('term_id', $termId))
            ->where('status', 'open')
            ->with('classroom')
            ->withCount(['marks as missing_count' => function ($q) {
                $q->whereNull('score_raw')
                    ->whereNull('score_moderated')
                    ->whereNull('opener_score')
                    ->whereNull('midterm_score')
                    ->whereNull('endterm_score');
            }])
            ->latest()
            ->take(20)
            ->get()
            ->filter(fn ($exam) => (int) $exam->missing_count > 0)
            ->sortByDesc('missing_count')
            ->take(8)
            ->values();
    }

    private function attendanceConcerns(array $classroomIds, string $today)
    {
        if (empty($classroomIds)) {
            return collect();
        }

        $absentIds = Attendance::query()
            ->whereDate('date', $today)
            ->where('status', Attendance::STATUS_ABSENT)
            ->whereHas('student', fn ($q) => $q->whereIn('classroom_id', $classroomIds)->where('status', 'active'))
            ->distinct()
            ->pluck('student_id');

        return Student::with(['classroom', 'stream'])
            ->whereIn('id', $absentIds)
            ->orderBy('first_name')
            ->take(8)
            ->get();
    }

    private function interventionLearners(array $classroomIds, string $today)
    {
        if (empty($classroomIds)) {
            return collect();
        }

        $rows = Attendance::query()
            ->selectRaw('student_id, COUNT(DISTINCT date) as days_absent')
            ->where('status', Attendance::STATUS_ABSENT)
            ->whereBetween('date', [now()->subDays(7)->toDateString(), $today])
            ->whereHas('student', fn ($q) => $q->whereIn('classroom_id', $classroomIds)->where('status', 'active'))
            ->groupBy('student_id')
            ->having('days_absent', '>=', 3)
            ->orderByDesc('days_absent')
            ->take(8)
            ->get();

        $students = Student::with(['classroom', 'stream'])->whereIn('id', $rows->pluck('student_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($students) {
            $student = $students->get($row->student_id);

            return (object) [
                'student' => $student,
                'days_absent' => (int) $row->days_absent,
                'url' => route('senior_teacher.students.index', array_filter([
                    'classroom_id' => $student?->classroom_id,
                    'stream_id' => $student?->stream_id,
                ])),
            ];
        })->filter(fn ($row) => $row->student);
    }

    private function teacherActivityToday($supervisedStaff, string $today): array
    {
        $userIds = $supervisedStaff->pluck('user_id')->filter();
        $marked = 0;
        if ($userIds->isNotEmpty()) {
            $marked = Attendance::query()
                ->whereDate('date', $today)
                ->whereIn('marked_by', $userIds)
                ->distinct()
                ->count('marked_by');
        }

        return [
            'supervised' => $supervisedStaff->count(),
            'marked_attendance' => $marked,
        ];
    }

    /**
     * Show all supervised classrooms (from assigned campus), grouped by streams.
     * Displays Class | Stream | Students so senior teachers see stream-level view.
     */
    public function supervisedClassrooms()
    {
        $user = auth()->user();
        $classroomIds = $user->getSupervisedClassroomIds();
        $classrooms = Classroom::whereIn('id', $classroomIds)
            ->with(['teachers', 'primaryStreams'])
            ->get();

        // Build stream-level rows: (classroom, stream|null, student_count)
        $streamRows = collect();
        foreach ($classrooms as $classroom) {
            $streams = $classroom->primaryStreams;
            if ($streams->isNotEmpty()) {
                foreach ($streams as $stream) {
                    $count = Student::where('classroom_id', $classroom->id)->where('stream_id', $stream->id)->count();
                    $streamRows->push((object)[
                        'classroom' => $classroom,
                        'stream' => $stream,
                        'student_count' => $count,
                    ]);
                }
            } else {
                // Classroom with no streams - show as single row
                $streamRows->push((object)[
                    'classroom' => $classroom,
                    'stream' => null,
                    'student_count' => $classroom->students()->count(),
                ]);
            }
        }

        return view('senior_teacher.supervised_classrooms', compact('classrooms', 'streamRows'));
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
        $classrooms = Classroom::whereIn('id', $classroomIds)->orderBy('name')->get();
        $streams = Stream::whereIn('classroom_id', $classroomIds)->orderBy('name')->get();
        
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
        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->stream_id);
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
        
        $classrooms = Classroom::whereIn('id', $classroomIds)->orderBy('name')->get();
        $streams = Stream::whereIn('classroom_id', $classroomIds)->orderBy('name')->get();
        
        return view('senior_teacher.fee_balances', compact('students', 'classrooms', 'streams'));
    }
}

