<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\Student;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationLog;
use App\Services\AttendanceReportService;
use App\Services\AttendanceAnalyticsService;
use App\Services\SMSService;
use App\Models\AttendanceReasonCode;
use App\Models\Academics\Subject;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected SMSService $smsService;
    protected AttendanceReportService $reportService;
    protected AttendanceAnalyticsService $analyticsService;

    public function __construct(
        SMSService $smsService, 
        AttendanceReportService $reportService,
        AttendanceAnalyticsService $analyticsService
    ) {
        $this->smsService = $smsService;
        $this->reportService = $reportService;
        $this->analyticsService = $analyticsService;
    }

    // -------------------- MARKING FORM --------------------
    public function markForm(Request $request)
    {
        $selectedClass  = $request->get('class');
        $selectedStream = $request->get('stream');
        $selectedDate   = $request->get('date', Carbon::today()->toDateString());
        $q              = trim((string)$request->get('q'));

        $classes = Classroom::pluck('name', 'id');
        $streams = $selectedClass
            ? Stream::where('classroom_id', $selectedClass)->pluck('name', 'id')
            : collect();

        $students = Student::query()
            ->when($selectedClass, fn($q2) => $q2->where('classroom_id', $selectedClass))
            ->when($selectedStream, fn($q2) => $q2->where('stream_id', $selectedStream))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('first_name', 'like', "%$q%")
                       ->orWhere('middle_name', 'like', "%$q%")
                       ->orWhere('last_name', 'like', "%$q%")
                       ->orWhere('admission_number', 'like', "%$q%");
                });
            })
            ->orderBy('first_name')
            ->get();

        $attendanceRecords = Attendance::whereDate('date', $selectedDate)
            ->with('reasonCode', 'markedBy')
            ->get()
            ->keyBy('student_id');

        $unmarkedCount = max(0, $students->count() - $attendanceRecords->count());
        
        // Get reason codes and subjects for the form
        $reasonCodes = AttendanceReasonCode::active()->get();
        $subjects = Subject::orderBy('name')->get();

        return view('attendance.mark', compact(
            'classes', 'streams', 'students', 'attendanceRecords',
            'selectedClass', 'selectedStream', 'selectedDate', 'q', 'unmarkedCount',
            'reasonCodes', 'subjects'
        ));
    }

    // -------------------- MARK ATTENDANCE --------------------
public function mark(Request $request)
{
    $dateIn = $request->input('date', now()->toDateString());
    $date   = Carbon::parse($dateIn)->toDateString();

    if (Carbon::parse($date)->isFuture()) {
        return back()->with('error', 'You cannot mark attendance for a future date.');
    }

    foreach ($request->all() as $key => $value) {
        if (!str_starts_with((string)$key, 'status_')) continue;

        $studentId = (int)str_replace('status_', '', (string)$key);
        $status    = $value; // present|absent|late
        $reason    = $request->input('reason_' . $studentId);
        $reasonCodeId = $request->input('reason_code_' . $studentId);
        $isExcused = $request->has('is_excused_' . $studentId);
        $isMedicalLeave = $request->has('is_medical_leave_' . $studentId);
        $excuseNotes = $request->input('excuse_notes_' . $studentId);
        $subjectId = $request->input('subject_id_' . $studentId);
        $periodNumber = $request->input('period_number_' . $studentId);
        $periodName = $request->input('period_name_' . $studentId);

        $attendance = Attendance::firstOrNew([
            'student_id' => $studentId,
            'date'       => $date,
        ]);

        $oldStatus = $attendance->status;
        
        // Update all fields
        $attendance->status = $status;
        $attendance->reason = $status === 'present' ? null : $reason;
        $attendance->reason_code_id = $reasonCodeId;
        $attendance->is_excused = $isExcused;
        $attendance->is_medical_leave = $isMedicalLeave;
        $attendance->excuse_notes = $excuseNotes;
        $attendance->subject_id = $subjectId;
        $attendance->period_number = $periodNumber;
        $attendance->period_name = $periodName;
        $attendance->marked_by = auth()->id();
        $attendance->marked_at = now();
        
        // Auto-set medical leave if reason code is medical
        if ($reasonCodeId) {
            $reasonCode = AttendanceReasonCode::find($reasonCodeId);
            if ($reasonCode && $reasonCode->is_medical) {
                $attendance->is_medical_leave = true;
                $attendance->is_excused = true;
            }
        }
        
        $attendance->save();
        
        // Update consecutive absence count
        $consecutive = $this->analyticsService->getConsecutiveAbsences($attendance->student, $date);
        $attendance->update(['consecutive_absence_count' => $consecutive]);

        // ---- Trigger communications if status changed ----
        try {
            if ($oldStatus !== $status) {
                $student = $attendance->student()->with('parent')->first();
                if (!$student || !$student->parent) continue;

                $humanDate = Carbon::parse($date)->isToday()
                    ? 'today'
                    : Carbon::parse($date)->format('d M Y');

                if ($status === 'absent') {
                    $this->notifyWithTemplate('attendance_absent', $student, $humanDate, $attendance->reason);
                } elseif ($status === 'late') {
                    $this->notifyWithTemplate('attendance_late', $student, $humanDate, $attendance->reason);
                } elseif ($oldStatus === 'absent' && $status === 'present') {
                    $this->notifyWithTemplate('attendance_corrected', $student, $humanDate, $attendance->reason);
                }
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    return back()->with('success', 'Attendance updated successfully.');
}

// -------------------- TEMPLATE NOTIFY --------------------
private function notifyWithTemplate(string $code, Student $student, string $humanDate, ?string $reason = null)
{
    $tpl = CommunicationTemplate::whereRaw('LOWER(code) = ?', [strtolower($code)])->first();

    $message = $tpl
        ? $this->applyPlaceholders($tpl->content, $student, $humanDate, $reason)
        : "Your child {$student->full_name} attendance update for {$humanDate}. Reason: {$reason}";

    $phones = array_filter([
        $student->parent->father_phone ?? null,
        $student->parent->mother_phone ?? null,
        $student->parent->guardian_phone ?? null,
    ]);

    foreach ($phones as $phone) {
        try {
            $response = $this->smsService->sendSMS($phone, $message);

            CommunicationLog::create([
                'recipient_type' => 'parent',
                'recipient_id'   => $student->parent->id ?? null,
                'contact'        => $phone,
                'channel'        => 'sms',
                'message'        => $message,
                'status'         => 'sent',
                'response'       => json_encode($response),
                'title'          => $tpl->title ?? $code,
                'target'         => 'attendance',
                'type'           => 'sms',
                'sent_at'        => now(),
            ]);
        } catch (\Exception $e) {
            CommunicationLog::create([
                'recipient_type' => 'parent',
                'recipient_id'   => $student->parent->id ?? null,
                'contact'        => $phone,
                'channel'        => 'sms',
                'message'        => $message,
                'status'         => 'failed',
                'response'       => $e->getMessage(),
                'title'          => $tpl->title ?? $code,
                'target'         => 'attendance',
                'type'           => 'sms',
                'sent_at'        => now(),
            ]);
        }
    }
}

private function applyPlaceholders(string $content, Student $student, string $humanDate, ?string $reason = null): string
{
    $replacements = [
        '{student_name}' => $student->full_name,
        '{class}'        => $student->classrooms->name ?? '',
        '{admission_no}' => $student->admission_number ?? '',
        '{date}'         => $humanDate,
        '{parent_name}'  => $student->parent->father_name
            ?? $student->parent->mother_name
            ?? $student->parent->guardian_name
            ?? '',
        '{reason}'       => $reason ?? '',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $content);
}


    // -------------------- REPORTS --------------------
    public function index()
    {
        $records = Attendance::latest()->paginate(50);
        return view('attendance.index', compact('records'));
    }

    public function reportForm()
    {
        $classes = Classroom::pluck('name', 'id');
        return view('attendance.report_form', compact('classes'));
    }

    public function generateReport(Request $request)
    {
        return $this->reportService->generate($request);
    }
    
    public function records(Request $request)
{
    $selectedClass  = $request->get('class');
    $selectedStream = $request->get('stream');
    $startDate      = $request->get('start', Carbon::today()->toDateString());
    $endDate        = $request->get('end', Carbon::today()->toDateString());
    $studentId      = $request->get('student_id');

    $classes = Classroom::pluck('name', 'id');
    $streams = $selectedClass
        ? Stream::where('classroom_id', $selectedClass)->pluck('name', 'id')
        : collect();

    $students = Student::query()
        ->when($selectedClass, fn($q) => $q->where('classroom_id', $selectedClass))
        ->when($selectedStream, fn($q) => $q->where('stream_id', $selectedStream))
        ->orderBy('first_name')
        ->get();

    $attendanceRecords = Attendance::whereBetween('date', [$startDate, $endDate])
        ->with('student.classroom', 'student.stream', 'reasonCode', 'markedBy')
        ->get();

    // Build summary counts
    $summary = [
        'totals' => [
            'all'     => $attendanceRecords->count(),
            'present' => $attendanceRecords->where('status', 'present')->count(),
            'absent'  => $attendanceRecords->where('status', 'absent')->count(),
            'late'    => $attendanceRecords->where('status', 'late')->count(),
        ],
        'gender' => [
            'male'   => [
                'present' => $attendanceRecords->filter(fn($a) => $a->status === 'present' && $a->student && $a->student->gender === 'Male')->count(),
                'absent'  => $attendanceRecords->filter(fn($a) => $a->status === 'absent' && $a->student && $a->student->gender === 'Male')->count(),
                'late'    => $attendanceRecords->filter(fn($a) => $a->status === 'late' && $a->student && $a->student->gender === 'Male')->count(),
            ],
            'female' => [
                'present' => $attendanceRecords->filter(fn($a) => $a->status === 'present' && $a->student && $a->student->gender === 'Female')->count(),
                'absent'  => $attendanceRecords->filter(fn($a) => $a->status === 'absent' && $a->student && $a->student->gender === 'Female')->count(),
                'late'    => $attendanceRecords->filter(fn($a) => $a->status === 'late' && $a->student && $a->student->gender === 'Female')->count(),
            ],
            'other' => [
                'present' => $attendanceRecords->filter(fn($a) => $a->status === 'present' && $a->student && !in_array($a->student->gender, ['Male','Female']))->count(),
                'absent'  => $attendanceRecords->filter(fn($a) => $a->status === 'absent' && $a->student && !in_array($a->student->gender, ['Male','Female']))->count(),
                'late'    => $attendanceRecords->filter(fn($a) => $a->status === 'late' && $a->student && !in_array($a->student->gender, ['Male','Female']))->count(),
            ],
        ]
    ];

    // Group records by date
    $groupedByDate = $attendanceRecords->groupBy('date');

    // Student-specific tab
    $student = null;
    $studentRecords = collect();
    $studentStats = ['present'=>0,'absent'=>0,'late'=>0,'percent'=>0];

    if ($studentId) {
        $student = Student::with('classroom','stream')->find($studentId);
        if ($student) {
            $studentRecords = Attendance::where('student_id', $student->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->with('reasonCode', 'markedBy')
                ->orderBy('date', 'desc')
                ->get();

            $total = max(1, $studentRecords->count());
            $present = $studentRecords->where('status', 'present')->count();
            $absent  = $studentRecords->where('status', 'absent')->count();
            $late    = $studentRecords->where('status', 'late')->count();
            $studentStats = [
                'present' => $present,
                'absent'  => $absent,
                'late'    => $late,
                'percent' => round(($present / $total) * 100, 1),
            ];
        }
    }

    return view('attendance.reports', compact(
        'classes',
        'streams',
        'students',
        'attendanceRecords',
        'selectedClass',
        'selectedStream',
        'startDate',
        'endDate',
        'summary',
        'groupedByDate',
        'student',
        'studentRecords',
        'studentStats'
    ));
}

    /**
     * Show at-risk students (low attendance)
     */
    public function atRiskStudents(Request $request)
    {
        $selectedClass = $request->get('class');
        $selectedStream = $request->get('stream');
        $threshold = (float) $request->get('threshold', 75.0);
        $startDate = $request->get('start', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end', Carbon::now()->endOfMonth()->toDateString());

        $classes = Classroom::pluck('name', 'id');
        $streams = $selectedClass
            ? Stream::where('classroom_id', $selectedClass)->pluck('name', 'id')
            : collect();

        $atRiskStudents = $this->analyticsService->getAtRiskStudents(
            $selectedClass,
            $selectedStream,
            $threshold,
            $startDate,
            $endDate
        );

        return view('attendance.at_risk', compact(
            'classes', 'streams', 'atRiskStudents',
            'selectedClass', 'selectedStream', 'threshold', 'startDate', 'endDate'
        ));
    }

    /**
     * Show students with consecutive absences
     */
    public function consecutiveAbsences(Request $request)
    {
        $selectedClass = $request->get('class');
        $selectedStream = $request->get('stream');
        $threshold = (int) $request->get('threshold', 3);

        $classes = Classroom::pluck('name', 'id');
        $streams = $selectedClass
            ? Stream::where('classroom_id', $selectedClass)->pluck('name', 'id')
            : collect();

        $students = $this->analyticsService->getStudentsWithConsecutiveAbsences(
            $threshold,
            $selectedClass,
            $selectedStream
        );

        return view('attendance.consecutive_absences', compact(
            'classes', 'streams', 'students',
            'selectedClass', 'selectedStream', 'threshold'
        ));
    }

    /**
     * Show student attendance analytics
     */
    public function studentAnalytics(Request $request, Student $student)
    {
        $startDate = $request->get('start', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end', Carbon::now()->endOfMonth()->toDateString());
        $months = (int) $request->get('months', 6);

        $percentage = $this->analyticsService->calculateAttendancePercentage($student, $startDate, $endDate);
        $trends = $this->analyticsService->getAttendanceTrends($student, $months);
        $consecutive = $this->analyticsService->getConsecutiveAbsences($student);

        // Get subject-wise attendance if subjects exist
        $subjectStats = [];
        if (Schema::hasTable('subjects')) {
            $subjects = Subject::all();
            foreach ($subjects as $subject) {
                $stats = $this->analyticsService->getSubjectAttendanceStats(
                    $student->id,
                    $subject->id,
                    $startDate,
                    $endDate
                );
                if ($stats['total'] > 0) {
                    $subjectStats[] = array_merge(['subject' => $subject], $stats);
                }
            }
        }

        return view('attendance.student_analytics', compact(
            'student', 'percentage', 'trends', 'consecutive', 'subjectStats',
            'startDate', 'endDate', 'months'
        ));
    }

    /**
     * Update consecutive absence counts (can be run via cron)
     */
    public function updateConsecutiveCounts()
    {
        $this->analyticsService->updateConsecutiveAbsenceCounts();
        return back()->with('success', 'Consecutive absence counts updated successfully.');
    }

    /**
     * Send notifications for consecutive absences
     */
    public function notifyConsecutiveAbsences(Request $request)
    {
        $threshold = (int) $request->get('threshold', 3);
        $students = $this->analyticsService->getStudentsWithConsecutiveAbsences($threshold);

        $notified = 0;
        foreach ($students as $item) {
            $student = $item['student'];
            $consecutive = $item['consecutive_absences'];

            if ($student->parent) {
                $message = "Alert: {$student->full_name} has been absent for {$consecutive} consecutive day(s). Please contact the school.";
                
                $phones = array_filter([
                    $student->parent->father_phone ?? null,
                    $student->parent->mother_phone ?? null,
                    $student->parent->guardian_phone ?? null,
                ]);

                foreach ($phones as $phone) {
                    try {
                        $this->smsService->sendSMS($phone, $message);
                        $notified++;
                    } catch (\Exception $e) {
                        report($e);
                    }
                }
            }
        }

        return back()->with('success', "Notifications sent to {$notified} parent(s) for consecutive absences.");
    }
}
