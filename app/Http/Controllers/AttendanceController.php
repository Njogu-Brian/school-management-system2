<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Services\SMSService;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Stream;
use App\Models\CommunicationTemplate;
use App\Models\Staff;
use App\Models\KitchenRecipient; // <— new tiny model for kitchen recipients

class AttendanceController extends Controller
{
    protected SMSService $smsService;

    // ---- Template codes (centralized to avoid typos) ----
    private const TPL_ABSENT            = 'absent_notice';             // sms, content
    private const TPL_LATE              = 'late_notification';         // sms, content
    private const TPL_STATUS_CHANGE     = 'attendance_status_change';  // sms, content
    private const TPL_KITCHEN_SUMMARY   = 'kitchen_summary';           // sms, content with {summary}

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /* =========================================================
     |  MARK: form
     ========================================================= */
    public function showForm(Request $request)
    {
        abort_unless(can_access("attendance", "mark_attendance", "view"), 403);

        $user = Auth::user();

        $selectedClass  = $request->input('class', '');
        $selectedStream = $request->input('stream', '');
        $selectedDate   = $request->input('date', today()->toDateString());
        $q              = trim($request->input('q', ''));

        // classes visible for this user
        $classes = $user->hasRole('teacher')
            ? Classroom::whereIn('id', $user->streams->pluck('classroom_id')->unique()->values())->pluck('name','id')
            : Classroom::pluck('name','id');

        // streams for selected class
        $streams = collect();
        if ($selectedClass) {
            $streams = Stream::whereHas('classrooms', fn($q2) => $q2->where('classrooms.id', $selectedClass))
                ->orderBy('name')->pluck('name','id');

            if ($user->hasRole('teacher')) {
                $allowedStreamIds = $user->streams->pluck('id')->unique()->toArray();
                $streams = $streams->filter(fn($name,$id) => in_array($id, $allowedStreamIds));
            }
        }

        $students = collect();
        $attendanceRecords = collect();
        $unmarkedCount = 0;

        if ($selectedClass) {
            if ($user->hasRole('teacher')) {
                $allowedClassIds = $user->streams->pluck('classroom_id')->unique()->toArray();
                abort_unless(in_array((int)$selectedClass, $allowedClassIds), 403);
                if ($selectedStream) {
                    $allowedStreamIds = $user->streams->pluck('id')->unique()->toArray();
                    abort_unless(in_array((int)$selectedStream, $allowedStreamIds), 403);
                }
            }

            $studentsQuery = Student::with(['classroom','stream','trip'])
                ->where('classroom_id', $selectedClass);

            if ($selectedStream) $studentsQuery->where('stream_id', $selectedStream);

            if ($q !== '') {
                $studentsQuery->where(function ($x) use ($q) {
                    $x->where('admission_number','like',"%{$q}%")
                      ->orWhere('first_name','like',"%{$q}%")
                      ->orWhere('middle_name','like',"%{$q}%")
                      ->orWhere('last_name','like',"%{$q}%");
                });
            }

            $students = $studentsQuery->orderBy('first_name')->get();
            $attendanceRecords = Attendance::whereIn('student_id', $students->pluck('id'))
                ->whereDate('date', $selectedDate)
                ->get()->keyBy('student_id');

            $unmarkedCount = $students->count() - $attendanceRecords->count();
        }

        return view('attendance.mark', compact(
            'classes','streams','selectedClass','selectedStream','students','attendanceRecords','selectedDate','q','unmarkedCount'
        ));
    }

    /* =========================================================
     |  MARK: bulk submit
     ========================================================= */
    public function markAttendance(Request $request)
    {
        abort_unless(can_access("attendance", "mark_attendance", "add"), 403);

        $request->validate([
            'class'  => 'required|exists:classrooms,id',
            'stream' => 'nullable|exists:streams,id',
            'date'   => 'nullable|date'
        ]);

        $user = Auth::user();
        $classId  = (int) $request->input('class');
        $streamId = $request->input('stream') ? (int)$request->input('stream') : null;
        $markDate = $request->input('date') ? Carbon::parse($request->input('date'))->toDateString() : today()->toDateString();

        if ($user->hasRole('teacher')) {
            $allowedClassIds = $user->streams->pluck('classroom_id')->unique()->toArray();
            abort_unless(in_array($classId, $allowedClassIds), 403);
            if ($streamId) {
                $allowedStreamIds = $user->streams->pluck('id')->unique()->toArray();
                abort_unless(in_array($streamId, $allowedStreamIds), 403);
            }
        }

        $students = Student::with(['classroom','trip','parent'])
            ->where('classroom_id', $classId)
            ->when($streamId, fn($q) => $q->where('stream_id', $streamId))
            ->get();

        $tplAbsent        = $this->getTemplateContent(self::TPL_ABSENT);
        $tplLate          = $this->getTemplateContent(self::TPL_LATE);
        $tplStatusChange  = $this->getTemplateContent(self::TPL_STATUS_CHANGE);

        foreach ($students as $student) {
            $statusKey = "status_{$student->id}";
            if (!$request->has($statusKey)) continue;

            $incomingStatus = $request->input($statusKey); // present|absent|late
            $reason = $request->input("reason_{$student->id}", null);

            $existing = Attendance::where('student_id', $student->id)
                ->whereDate('date', $markDate)->first();

            $wasStatus = $existing ? $existing->status : null;

            Attendance::updateOrCreate(
                ['student_id' => $student->id, 'date' => $markDate],
                ['status' => $incomingStatus, 'reason' => $incomingStatus === 'present' ? null : $reason]
            );

            // --- Notifications ---
            if ($incomingStatus === 'absent' && ($wasStatus !== 'absent')) {
                // First absent or present/late -> absent
                if ($student->parent) {
                    $msg = $tplAbsent
                        ? str_replace(['{name}','{class}','{reason}'], [
                            $student->full_name, optional($student->classroom)->name, (string)$reason
                          ], $tplAbsent)
                        : "Dear Parent, {$student->full_name} (".optional($student->classroom)->name.") marked absent. Reason: {$reason}";
                    $this->notifyPhones($student, $msg);
                }
                if ($student->trip && $student->trip->driver_phone) {
                    $this->smsService->sendSMS($student->trip->driver_phone,
                        "Driver Alert: {$student->full_name} is absent today.");
                }
            }

            if ($incomingStatus === 'present' && $wasStatus === 'absent') {
                // Correction absent -> present
                $msg = $tplStatusChange
                    ? str_replace(['{name}','{class}'], [
                        $student->full_name, optional($student->classroom)->name
                      ], $tplStatusChange)
                    : "Update: {$student->full_name} (".optional($student->classroom)->name.") now marked present after absence.";
                $this->notifyPhones($student, $msg);
            }

            if ($incomingStatus === 'late' && $wasStatus !== 'late') {
                // If was absent earlier => status update; otherwise normal late notice
                if ($wasStatus === 'absent') {
                    $msg = $tplStatusChange
                        ? str_replace(['{name}','{class}'], [
                            $student->full_name, optional($student->classroom)->name
                          ], $tplStatusChange)
                        : "Update: {$student->full_name} (".optional($student->classroom)->name.") was absent earlier but is now marked late.";
                } else {
                    $msg = $tplLate
                        ? str_replace(['{name}','{class}'], [
                            $student->full_name, optional($student->classroom)->name
                          ], $tplLate)
                        : "Notice: {$student->full_name} (".optional($student->classroom)->name.") arrived late today.";
                }
                $this->notifyPhones($student, $msg);
            }
        }

        // Auto Kitchen notify if complete
        $this->autoNotifyKitchenIfComplete($markDate);

        return back()->with('success', 'Attendance marked successfully.');
    }

    /* =========================================================
     |  EDIT: single record
     ========================================================= */
    public function updateAttendance(Request $request, $id)
    {
        abort_unless(can_access("attendance", "mark_attendance", "edit"), 403);

        $request->validate([
            'status' => 'required|in:present,absent,late',
            'reason' => 'nullable|string|max:255',
        ]);

        $attendance = Attendance::with(['student.classroom','student.trip','student.parent'])->find($id);
        if (!$attendance) return back()->with('error', 'Not found.');

        $student = $attendance->student;

        if (Auth::user()->hasRole('teacher')) {
            $allowedClassIds = Auth::user()->streams->pluck('classroom_id')->unique()->toArray();
            abort_unless(in_array($student->classroom_id, $allowedClassIds), 403);
        }

        $wasStatus = $attendance->status;
        $newStatus = $request->status;

        $attendance->update([
            'status' => $newStatus,
            'reason' => $newStatus === 'present' ? null : $request->reason
        ]);

        $tplAbsent        = $this->getTemplateContent(self::TPL_ABSENT);
        $tplLate          = $this->getTemplateContent(self::TPL_LATE);
        $tplStatusChange  = $this->getTemplateContent(self::TPL_STATUS_CHANGE);

        // --- Notifications ---
        if ($wasStatus !== 'absent' && $newStatus === 'absent') {
            $msg = $tplAbsent
                ? str_replace(['{name}','{class}','{reason}'], [
                    $student->full_name, optional($student->classroom)->name, (string)$request->reason
                  ], $tplAbsent)
                : "Dear Parent/Guardian, {$student->full_name} (".optional($student->classroom)->name.") marked absent today. Reason: {$request->reason}";
            $this->notifyPhones($student, $msg);

            if ($student->trip && $student->trip->driver_phone) {
                $this->smsService->sendSMS($student->trip->driver_phone,
                    "Driver Alert: {$student->full_name} is absent today.");
            }
        }

        if ($wasStatus === 'absent' && $newStatus === 'present') {
            $msg = $tplStatusChange
                ? str_replace(['{name}','{class}'], [
                    $student->full_name, optional($student->classroom)->name
                  ], $tplStatusChange)
                : "Update: {$student->full_name} (".optional($student->classroom)->name.") has now been marked present after initially being absent.";
            $this->notifyPhones($student, $msg);
        }

        if ($wasStatus !== 'late' && $newStatus === 'late') {
            if ($wasStatus === 'absent') {
                $msg = $tplStatusChange
                    ? str_replace(['{name}','{class}'], [
                        $student->full_name, optional($student->classroom)->name
                      ], $tplStatusChange)
                    : "Update: {$student->full_name} (".optional($student->classroom)->name.") was absent earlier but is now marked late.";
            } else {
                $msg = $tplLate
                    ? str_replace(['{name}','{class}'], [
                        $student->full_name, optional($student->classroom)->name
                      ], $tplLate)
                    : "Notice: {$student->full_name} (".optional($student->classroom)->name.") arrived late today.";
            }
            $this->notifyPhones($student, $msg);
        }

        return redirect()->route('attendance.mark.form')->with('success', 'Attendance updated.');
    }

    /* =========================================================
     |  Kitchen: SETTINGS (Chef / Upper / Lower)
     |  These pages let you assign which staff receives which classes.
     |  Upper/Lower are just labels — you control which classes each gets.
     ========================================================= */
    public function kitchenSettings()
    {
        abort_unless(can_access("attendance", "mark_attendance", "edit"), 403);

        $classes = Classroom::orderBy('name')->get();
        $staff   = Staff::orderBy('first_name')->get();

        // bootstrap 3 records if none
        if (KitchenRecipient::count() === 0) {
            KitchenRecipient::create(['label' => 'Chef',          'staff_id' => null, 'classroom_ids' => [], 'active' => true]);
            KitchenRecipient::create(['label' => 'Upper Janitor', 'staff_id' => null, 'classroom_ids' => [], 'active' => true]);
            KitchenRecipient::create(['label' => 'Lower Janitor', 'staff_id' => null, 'classroom_ids' => [], 'active' => true]);
        }

        $recipients = KitchenRecipient::orderBy('label')->get();

        return view('attendance.kitchen_settings', compact('classes','staff','recipients'));
    }

    public function kitchenSettingsSave(Request $request)
    {
        abort_unless(can_access("attendance", "mark_attendance", "edit"), 403);

        $data = $request->validate([
            'recipients' => 'required|array',
            'recipients.*.id'            => 'nullable|exists:kitchen_recipients,id',
            'recipients.*.label'         => 'required|string',
            'recipients.*.staff_id'      => 'nullable|exists:staff,id',
            'recipients.*.classroom_ids' => 'array',
            'recipients.*.active'        => 'boolean',
        ]);

        foreach ($data['recipients'] as $row) {
            $payload = [
                'label'         => $row['label'],
                'staff_id'      => $row['staff_id'] ?? null,
                'classroom_ids' => $row['classroom_ids'] ?? [],
                'active'        => isset($row['active']) ? (bool)$row['active'] : false,
            ];

            if (!empty($row['id'])) {
                KitchenRecipient::find($row['id'])->update($payload);
            } else {
                KitchenRecipient::create($payload);
            }
        }

        return back()->with('success', 'Kitchen recipients saved.');
    }

    /* =========================================================
     |  Kitchen: MANUAL notify (form + send)
     ========================================================= */
    public function kitchenNotifyForm(Request $request)
    {
        abort_unless(can_access("attendance", "mark_attendance", "view"), 403);

        $date = $request->input('date', today()->toDateString());

        // Build a preview summary for the whole school
        $summaryByClass = $this->attendancePresentCountsByClass($date);

        $recipients = KitchenRecipient::with('staff')->where('active', true)->orderBy('label')->get();
        return view('attendance.kitchen_notify', [
            'date' => $date,
            'summaryByClass' => $summaryByClass,
            'recipients' => $recipients,
            'isComplete' => $this->isAllClassesMarked($date),
        ]);
    }

    public function kitchenNotifySend(Request $request)
    {
        abort_unless(can_access("attendance", "mark_attendance", "add"), 403);

        $date = $request->validate(['date' => 'required|date'])['date'];
        $force = (bool) $request->input('force', false);

        if (!$force && !$this->isAllClassesMarked($date)) {
            return back()->with('error', 'Attendance is not complete for that date. Tick "Force send" to proceed.');
        }

        $sent = $this->sendKitchenNotifications($date);

        return back()->with('success', "Kitchen notified ({$sent} message(s)).");
    }

    /* =========================================================
     |  Kitchen: AUTO notify when complete
     ========================================================= */
    protected function autoNotifyKitchenIfComplete(string $date)
    {
        if ($this->isAllClassesMarked($date)) {
            try {
                $this->sendKitchenNotifications($date);
            } catch (\Throwable $e) {
                Log::warning('Auto Kitchen notify failed: '.$e->getMessage());
            }
        }
    }

    protected function isAllClassesMarked(string $date): bool
    {
        $classCount = Classroom::count();
        if ($classCount === 0) return false;

        $markedClassIds = Attendance::whereDate('date', $date)
            ->join('students', 'attendance.student_id','=','students.id')
            ->pluck('students.classroom_id')
            ->unique();

        return $markedClassIds->count() >= $classCount;
    }

    protected function sendKitchenNotifications(string $date): int
    {
        $summaryByClass = $this->attendancePresentCountsByClass($date); // [className => presentCount]

        // Build a whole-school message once (Chef will use this)
        $schoolTotal = array_sum(array_values($summaryByClass));
        $lines = ["Attendance Summary for {$date} (Present): Total {$schoolTotal}"];
        foreach ($summaryByClass as $class => $count) {
            $lines[] = "{$class}: {$count}";
        }
        $schoolMsgRaw = implode("\n", $lines);

        // Use template if present
        $tplKitchen = $this->getTemplateContent(self::TPL_KITCHEN_SUMMARY);
        $schoolMsg = $tplKitchen
            ? str_replace('{summary}', $schoolMsgRaw, $tplKitchen)
            : $schoolMsgRaw;

        $sent = 0;

        $recipients = KitchenRecipient::with('staff')->where('active', true)->get();
        foreach ($recipients as $rec) {
            if (!$rec->staff || !$rec->staff->phone_number) continue;

            // Chef: send whole-school summary
            if (stripos($rec->label, 'chef') !== false) {
                $this->smsService->sendSMS($this->formatMsisdn($rec->staff->phone_number), $schoolMsg);
                $sent++;
                continue;
            }

            // Others: send partial (classes assigned)
            $subset = [];
            foreach (($rec->classroom_ids ?? []) as $classId) {
                $name = Classroom::where('id', $classId)->value('name');
                if ($name && isset($summaryByClass[$name])) {
                    $subset[$name] = $summaryByClass[$name];
                }
            }

            // If no classes assigned, fall back to whole-school summary
            $msgRaw = $schoolMsgRaw;
            if (!empty($subset)) {
                $subTotal = array_sum(array_values($subset));
                $lines = ["Attendance ({$date}) Present: Total {$subTotal}"];
                foreach ($subset as $class => $count) {
                    $lines[] = "{$class}: {$count}";
                }
                $msgRaw = implode("\n", $lines);
            }

            $msg = $tplKitchen
                ? str_replace('{summary}', $msgRaw, $tplKitchen)
                : $msgRaw;

            $this->smsService->sendSMS($this->formatMsisdn($rec->staff->phone_number), $msg);
            $sent++;
        }

        return $sent;
    }

    protected function attendancePresentCountsByClass(string $date): array
    {
        // Present = status 'present' or 'late'
        $rows = Attendance::whereDate('attendance.date', $date)
            ->whereIn('attendance.status', ['present','late'])
            ->join('students','attendance.student_id','=','students.id')
            ->join('classrooms','students.classroom_id','=','classrooms.id')
            ->selectRaw('classrooms.name as cname, COUNT(*) as cnt')
            ->groupBy('classrooms.name')
            ->orderBy('classrooms.name')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->cname] = (int)$r->cnt;
        }
        return $out;
    }

    private function getTemplateContent(string $code): ?string
    {
        $tpl = CommunicationTemplate::where('type','sms')->where('code',$code)->first();
        return $tpl ? $tpl->content : null;
    }

    private function formatMsisdn(?string $raw): ?string
    {
        if (!$raw) return null;
        $n = preg_replace('/\D+/', '', $raw);
        // Normalize to Kenyan format 2547XXXXXXXX if needed
        if (str_starts_with($n, '0')) $n = '254'.substr($n,1);
        if (str_starts_with($n, '7')) $n = '254'.$n;
        if (!str_starts_with($n, '254')) $n = '254'.$n; // best-effort
        return $n;
    }

    private function notifyPhones($student, string $msg): void
    {
        $phones = collect([
            optional($student->parent)->father_phone,
            optional($student->parent)->mother_phone,
            optional($student->parent)->guardian_phone
        ])->filter()->unique();

        foreach ($phones as $phone) {
            $this->smsService->sendSMS($this->formatMsisdn($phone), $msg);
        }
    }

    /* =========================================================
     |  Records + History (unchanged)
     ========================================================= */
    public function history($studentId)
    {
        $records = Attendance::where('student_id', $studentId)
            ->orderByDesc('date')
            ->take(30)
            ->get();

        return response()->json($records);
    }

    public function records(Request $request)
    {
        abort_unless(can_access("attendance", "mark_attendance", "view"), 403);

        $user = Auth::user();
        $classes = $user->hasRole('teacher')
            ? Classroom::whereIn('id', $user->streams->pluck('classroom_id')->unique()->values())->pluck('name','id')
            : Classroom::pluck('name','id');

        $selectedClass  = $request->input('class', '');
        $selectedStream = $request->input('stream', '');
        $startDate      = $request->input('start', today()->toDateString());
        $endDate        = $request->input('end', today()->toDateString());

        $streams = collect();
        if ($selectedClass) {
            $streams = Stream::whereHas('classrooms', fn($q) => $q->where('classrooms.id', $selectedClass))
                ->orderBy('name')->pluck('name','id');

            if ($user->hasRole('teacher')) {
                $allowedStreamIds = $user->streams->pluck('id')->unique()->toArray();
                $streams = $streams->filter(fn($name,$id) => in_array($id, $allowedStreamIds));
            }
        }

        $students = Student::with(['classroom','stream'])
            ->when($selectedClass, fn($q) => $q->where('classroom_id', $selectedClass))
            ->when($selectedStream, fn($q) => $q->where('stream_id', $selectedStream))
            ->get();

        $records = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get()
            ->groupBy('date');

        return view('attendance.records', compact(
            'classes','streams','selectedClass','selectedStream','startDate','endDate','students','records'
        ));
    }

    // ---- Kitchen Recipients CRUD ----
    public function kitchenRecipientsIndex()
    {
        $recipients = KitchenRecipient::with('staff')->get();
        $classrooms = Classroom::pluck('name','id')->toArray();
        return view('attendance.kitchen_recipients.index', compact('recipients','classrooms'));
    }

    public function kitchenRecipientsCreate()
    {
        $staff = Staff::all();
        $classrooms = Classroom::pluck('name','id')->toArray();
        return view('attendance.kitchen_recipients.create', compact('staff','classrooms'));
    }

    public function kitchenRecipientsStore(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'staff_id' => 'required|exists:staff,id',
            'classroom_ids' => 'array',
            'active' => 'required|boolean',
        ]);

        KitchenRecipient::create([
            'label' => $data['label'],
            'staff_id' => $data['staff_id'],
            'classroom_ids' => $data['classroom_ids'] ?? [],
            'active' => $data['active'],
        ]);

        return redirect()->route('attendance.kitchen.recipients.index')->with('success', 'Recipient added.');
    }

    public function kitchenRecipientsEdit($id)
    {
        $recipient = KitchenRecipient::findOrFail($id);
        $staff = Staff::all();
        $classrooms = Classroom::pluck('name','id')->toArray();
        return view('attendance.kitchen_recipients.edit', compact('recipient','staff','classrooms'));
    }

    public function kitchenRecipientsUpdate(Request $request, $id)
    {
        $recipient = KitchenRecipient::findOrFail($id);
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'staff_id' => 'required|exists:staff,id',
            'classroom_ids' => 'array',
            'active' => 'required|boolean',
        ]);

        $recipient->update([
            'label' => $data['label'],
            'staff_id' => $data['staff_id'],
            'classroom_ids' => $data['classroom_ids'] ?? [],
            'active' => $data['active'],
        ]);

        return redirect()->route('attendance.kitchen.recipients.index')->with('success', 'Recipient updated.');
    }

    public function kitchenRecipientsDestroy($id)
    {
        $recipient = KitchenRecipient::findOrFail($id);
        $recipient->delete();
        return redirect()->route('attendance.kitchen.recipients.index')->with('success', 'Recipient deleted.');
    }

}
