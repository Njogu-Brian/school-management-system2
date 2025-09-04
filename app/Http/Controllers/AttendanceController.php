<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Stream;
use App\Models\Student;
use App\Models\CommunicationTemplate;
use App\Services\AttendanceReportService;
use App\Services\SMSService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected SMSService $smsService;
    protected AttendanceReportService $reportService;

    public function __construct(SMSService $smsService, AttendanceReportService $reportService)
    {
        $this->smsService = $smsService;
        $this->reportService = $reportService;
    }

    // -------------------- MARKING --------------------

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
            ->get()
            ->keyBy('student_id');

        $unmarkedCount = max(0, $students->count() - $attendanceRecords->count());

        return view('attendance.mark', compact(
            'classes', 'streams', 'students', 'attendanceRecords',
            'selectedClass', 'selectedStream', 'selectedDate', 'q', 'unmarkedCount'
        ));
    }

    public function mark(Request $request)
    {
        $dateIn = $request->input('date', now()->toDateString());
        $date   = Carbon::parse($dateIn)->toDateString();

        // ✋ No future dates
        if (Carbon::parse($date)->isFuture()) {
            return back()->with('error', 'You cannot mark attendance for a future date.');
        }

        foreach ($request->all() as $key => $value) {
            if (!str_starts_with((string)$key, 'status_')) continue;

            $studentId = (int)str_replace('status_', '', (string)$key);
            $status    = $value; // 'present'|'absent'|'late'
            $reason    = $request->input('reason_' . $studentId);

            $attendance = Attendance::firstOrNew([
                'student_id' => $studentId,
                'date'       => $date,
            ]);

            $oldStatus         = $attendance->status; // before change
            $attendance->status = $status;
            $attendance->reason = $status === 'present' ? null : $reason;
            $attendance->save();

            // ---- SMS hooks only if status changed ----
            try {
                if ($oldStatus !== $status) {
                    $student = $attendance->student()->with('parent')->first();
                    if (!$student || !$student->parent) {
                        continue;
                    }

                    $humanDate = Carbon::parse($date)->isToday()
                        ? 'today'
                        : Carbon::parse($date)->format('d M Y');

                    if ($status === 'absent') {
                        $tpl = CommunicationTemplate::where('code', 'attendance_absent')->first();
                        $msg = $tpl
                            ? str_replace(['{name}', '{date}'], [$student->full_name, $humanDate], $tpl->content)
                            : "Your child {$student->full_name} has been marked ABSENT for {$humanDate}.";
                        $this->notifyParents($student->parent, $msg);
                    } elseif ($status === 'late') {
                        $tpl = CommunicationTemplate::where('code', 'attendance_late')->first();
                        $msg = $tpl
                            ? str_replace(['{name}', '{date}'], [$student->full_name, $humanDate], $tpl->content)
                            : "Your child {$student->full_name} was marked LATE on {$humanDate}.";
                        $this->notifyParents($student->parent, $msg);
                    } elseif ($oldStatus === 'absent' && $status === 'present') {
                        $tpl = CommunicationTemplate::where('code', 'attendance_corrected')->first();
                        $msg = $tpl
                            ? str_replace(['{name}', '{date}'], [$student->full_name, $humanDate], $tpl->content)
                            : "Correction: {$student->full_name} has been marked PRESENT for {$humanDate} (was absent).";
                        $this->notifyParents($student->parent, $msg);
                    }
                }
            } catch (\Throwable $e) {
                Log::error("Attendance SMS failed: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Attendance saved successfully.');
    }

    // -------------------- EDIT SINGLE RECORD --------------------

    public function edit($id)
    {
        $attendance = Attendance::with('student')->findOrFail($id);
        return view('attendance.edit', compact('attendance'));
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::with('student.parent')->findOrFail($id);
        $oldStatus  = $attendance->status;

        $attendance->status = $request->status;
        $attendance->reason = $request->status === 'present' ? null : $request->reason;
        $attendance->save();

        // optional: message when corrected in Edit screen
        if ($oldStatus !== $attendance->status && $attendance->student && $attendance->student->parent) {
            try {
                $humanDate = Carbon::parse($attendance->date)->isToday()
                    ? 'today'
                    : Carbon::parse($attendance->date)->format('d M Y');

                if ($attendance->status === 'present' && $oldStatus === 'absent') {
                    $tpl = CommunicationTemplate::where('code', 'attendance_corrected')->first();
                    $msg = $tpl
                        ? str_replace(['{name}', '{date}'], [$attendance->student->full_name, $humanDate], $tpl->content)
                        : "Correction: {$attendance->student->full_name} has been marked PRESENT for {$humanDate} (was absent).";
                    $this->notifyParents($attendance->student->parent, $msg);
                }
            } catch (\Throwable $e) {
                Log::error("Attendance edit SMS failed: " . $e->getMessage());
            }
        }

        return redirect()->route('attendance.records')->with('success', 'Attendance updated.');
    }

    // -------------------- REPORTS (Class/Stream + Student tabs) --------------------

    public function records(Request $request)
    {
        // filters
        $selectedClass  = $request->get('class');
        $selectedStream = $request->get('stream');
        $startDate      = $request->get('start', Carbon::today()->startOfMonth()->toDateString());
        $endDate        = $request->get('end',   Carbon::today()->toDateString());

        $classes = Classroom::pluck('name', 'id');
        $streams = $selectedClass
            ? Stream::where('classroom_id', $selectedClass)->pluck('name', 'id')
            : collect();

        // class/stream report
        $groupedByDate = $this->reportService->recordsGroupedByDate(
            $selectedClass,
            $selectedStream,
            $startDate,
            $endDate
        );

        $summary = $this->reportService->summary(
            $selectedClass,
            $selectedStream,
            $startDate,
            $endDate
        ); // totals + gender split

        // student tab (loaded when params present)
        $studentId       = (int)$request->get('student_id', 0);
        $student         = $studentId ? Student::with('classroom','stream')->find($studentId) : null;
        $studentRecords  = $student
            ? Attendance::where('student_id', $student->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'desc')
                ->get()
            : collect();

        $studentStats = $this->reportService->studentStats($student, $startDate, $endDate);

        return view('attendance.reports', compact(
            'classes', 'streams',
            'selectedClass', 'selectedStream',
            'startDate', 'endDate',
            'groupedByDate', 'summary',
            'student', 'studentRecords', 'studentStats'
        ));
    }

    // -------------------- helpers --------------------

    private function notifyParents($parent, string $message): void
    {
        foreach ([$parent->father_phone, $parent->mother_phone, $parent->guardian_phone] as $phone) {
            if ($phone) {
                $this->smsService->sendSMS($phone, $message);
            }
        }
    }
}
