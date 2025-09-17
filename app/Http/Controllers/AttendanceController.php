<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Stream;
use App\Models\Student;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationLog;
use App\Services\AttendanceReportService;
use App\Services\SMSService;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected SMSService $smsService;
    protected AttendanceReportService $reportService;

    public function __construct(SMSService $smsService, AttendanceReportService $reportService)
    {
        $this->smsService = $smsService;
        $this->reportService = $reportService;
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
            ->get()
            ->keyBy('student_id');

        $unmarkedCount = max(0, $students->count() - $attendanceRecords->count());

        return view('attendance.mark', compact(
            'classes', 'streams', 'students', 'attendanceRecords',
            'selectedClass', 'selectedStream', 'selectedDate', 'q', 'unmarkedCount'
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

        $attendance = Attendance::firstOrNew([
            'student_id' => $studentId,
            'date'       => $date,
        ]);

        $oldStatus         = $attendance->status;
        $attendance->status = $status;
        $attendance->reason = $status === 'present' ? null : $reason;
        $attendance->save();

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
    $tpl = CommunicationTemplate::where('code', $code)->first();
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
        '{class}'        => $student->classroom->name ?? '',
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

   
}
