<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceReasonCode;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Models\Student;
use App\Services\SMSService;
use App\Services\StudentAttendanceCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiAttendanceController extends Controller
{
    public function __construct(
        protected SMSService $smsService,
        protected StudentAttendanceCalendarService $attendanceCalendar
    ) {
    }

    /**
     * Existing attendance for a class/stream on a date (for mobile to show live data).
     */
    public function classAttendance(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'class_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $date = Carbon::parse($request->date)->toDateString();
        $classId = (int) $request->class_id;
        $streamId = $request->stream_id ? (int) $request->stream_id : null;
        $user = $request->user();

        if ($user && $user->hasTeacherLikeRole()) {
            if (! $user->canMarkClassAttendanceForClassroom($classId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only class teachers can mark attendance for this class.',
                ], 403);
            }
        }

        $studentQuery = Student::where('classroom_id', $classId)
            ->where('archive', 0)
            ->where('is_alumni', false);
        if ($streamId !== null) {
            $studentQuery->where('stream_id', $streamId);
        }
        if ($user && $user->hasTeacherLikeRole()) {
            $user->applyTeacherStudentFilter($studentQuery);
        }
        $studentIds = $studentQuery->pluck('id');

        $records = Attendance::whereDate('date', $date)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->map(fn ($a) => [
                'student_id' => $a->student_id,
                'status' => $a->status === 'absent' && $a->is_excused ? 'absent' : $a->status,
                'reason' => $a->reason,
                'reason_code_id' => $a->reason_code_id,
                'excuse_notes' => $a->excuse_notes,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $records]);
    }

    /**
     * Whether attendance may be recorded on a date (school calendar).
     */
    public function schoolDay(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date)->toDateString();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'is_school_day' => $this->attendanceCalendar->isValidSchoolDay($date),
                'is_future' => Carbon::parse($date)->isFuture(),
            ],
        ]);
    }

    /**
     * Preset absence/late reasons used on web and mobile.
     */
    public function reasonCodes()
    {
        $codes = AttendanceReasonCode::active()
            ->get(['id', 'code', 'name', 'requires_excuse', 'is_medical']);

        return response()->json(['success' => true, 'data' => $codes]);
    }

    /**
     * Mark attendance for a class/stream.
     * Request: { date, class_id, stream_id?, records: [{ student_id, status }, ...] }
     * status: present|absent|late|unmarked (unmarked = delete record)
     */
    public function mark(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'class_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|integer|exists:students,id',
            'records.*.status' => 'required|in:present,absent,late,unmarked',
            'records.*.reason_code_id' => 'nullable|integer|exists:attendance_reason_codes,id',
            'records.*.reason' => 'nullable|string|max:500',
            'records.*.excuse_notes' => 'nullable|string|max:1000',
        ]);

        $date = Carbon::parse($request->date)->toDateString();
        $classId = (int) $request->class_id;
        $streamId = $request->stream_id ? (int) $request->stream_id : null;
        $user = $request->user();
        $isToday = Carbon::parse($date)->isToday();

        if ($user && $user->isAcademicAdministratorUser()) {
            return response()->json(['success' => false, 'message' => 'Academic administrators cannot mark attendance.'], 403);
        }

        if (Carbon::parse($date)->isFuture()) {
            return response()->json(['success' => false, 'message' => 'Cannot mark attendance for a future date.'], 422);
        }

        $hasNonUnmark = false;
        foreach ($request->input('records', []) as $rec) {
            if (($rec['status'] ?? '') !== 'unmarked') {
                $hasNonUnmark = true;
                break;
            }
        }
        if ($hasNonUnmark && ! $this->attendanceCalendar->isValidSchoolDay($date)) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance cannot be recorded on this date (weekend, holiday, or other non-school day).',
            ], 422);
        }

        if ($user->hasTeacherLikeRole() && ! $user->canMarkClassAttendanceForClassroom($classId)) {
            return response()->json([
                'success' => false,
                'message' => 'Only class teachers can mark attendance for this class.',
            ], 403);
        }

        $count = 0;

        DB::transaction(function () use ($request, $date, $classId, $streamId, $user, $isToday, &$count) {
            foreach ($request->records as $rec) {
                $studentId = (int) $rec['student_id'];
                $status = $rec['status'];

                $studentQ = Student::where('id', $studentId)
                    ->where('classroom_id', $classId)
                    ->when($streamId !== null, fn ($q) => $q->where('stream_id', $streamId))
                    ->where('archive', 0)
                    ->where('is_alumni', false);
                if ($user && $user->hasTeacherLikeRole()) {
                    $user->applyTeacherStudentFilter($studentQ);
                }
                $student = $studentQ->with('parent')->first();

                if (!$student) {
                    continue;
                }

                if ($status !== 'unmarked' && ! $this->attendanceCalendar->canMarkAttendanceForDate($student, $date)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more students cannot be marked for this date (not enrolled yet or archived on this date).',
                    ], 422);
                }

                if ($status === 'unmarked') {
                    Attendance::where('student_id', $studentId)->whereDate('date', $date)->forceDelete();
                    continue;
                }

                $attendance = Attendance::firstOrNew([
                    'student_id' => $studentId,
                    'date' => $date,
                ]);
                $oldStatus = $attendance->exists ? $attendance->status : null;

                $attendance->status = $status;
                $this->applyReasonToAttendance($attendance, $status, is_array($rec) ? $rec : []);
                $attendance->marked_by = $user->id;
                $attendance->marked_at = now();
                $attendance->save();

                $count++;

                if ($status === 'absent' && $isToday && $student->parent) {
                    $this->notifyParentAbsent($student, $attendance->reason);
                    try {
                        app(\App\Services\ParentAppNotifyService::class)->notifyChildAbsent($student);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'message' => "Attendance updated for {$count} students.",
                'count' => $count,
            ],
        ]);
    }

    /**
     * Mark selected students absent without going class by class.
     * Request: { date, student_ids: [1, 2, ...] }
     */
    public function markAbsent(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'student_ids' => 'required|array|min:1|max:200',
            'student_ids.*' => 'required|integer|exists:students,id',
            'reason_code_id' => 'nullable|integer|exists:attendance_reason_codes,id',
            'reason' => 'nullable|string|max:500',
            'excuse_notes' => 'nullable|string|max:1000',
        ]);

        $date = Carbon::parse($request->date)->toDateString();
        $user = $request->user();
        $isToday = Carbon::parse($date)->isToday();

        if ($user && $user->isAcademicAdministratorUser()) {
            return response()->json(['success' => false, 'message' => 'Academic administrators cannot mark attendance.'], 403);
        }

        if (Carbon::parse($date)->isFuture()) {
            return response()->json(['success' => false, 'message' => 'Cannot mark attendance for a future date.'], 422);
        }

        if (! $this->attendanceCalendar->isValidSchoolDay($date)) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance cannot be recorded on this date (weekend, holiday, or other non-school day).',
            ], 422);
        }

        $ids = array_values(array_unique(array_map('intval', $request->input('student_ids', []))));
        $query = Student::whereIn('id', $ids)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->with('parent');
        if ($user && $user->hasTeacherLikeRole()) {
            if ($user->shouldRestrictToHomeroomDuties()) {
                $user->applyHomeroomStudentFilter($query);
            } else {
                $user->applyTeacherStudentFilter($query);
            }
        }
        $students = $query->get();
        if ($students->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No matching students were found.'], 422);
        }

        $reasonPayload = [
            'reason_code_id' => $request->input('reason_code_id'),
            'reason' => $request->input('reason'),
            'excuse_notes' => $request->input('excuse_notes'),
        ];

        $count = 0;
        DB::transaction(function () use ($students, $date, $user, $isToday, $reasonPayload, &$count) {
            foreach ($students as $student) {
                if (! $this->attendanceCalendar->canMarkAttendanceForDate($student, $date)) {
                    continue;
                }

                $attendance = Attendance::firstOrNew([
                    'student_id' => $student->id,
                    'date' => $date,
                ]);
                $attendance->status = 'absent';
                $this->applyReasonToAttendance($attendance, 'absent', $reasonPayload);
                $attendance->marked_by = $user->id;
                $attendance->marked_at = now();
                $attendance->save();
                $count++;

                if ($isToday && $student->parent) {
                    $this->notifyParentAbsent($student, $attendance->reason);
                    try {
                        app(\App\Services\ParentAppNotifyService::class)->notifyChildAbsent($student);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $count === 1
                    ? '1 student marked absent.'
                    : "{$count} students marked absent.",
                'count' => $count,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $rec
     */
    protected function applyReasonToAttendance(Attendance $attendance, string $status, array $rec): void
    {
        if ($status === 'present' || $status === 'unmarked') {
            $attendance->reason = null;
            $attendance->reason_code_id = null;
            $attendance->is_excused = false;
            $attendance->is_medical_leave = false;
            $attendance->excuse_notes = null;

            return;
        }

        $reasonCodeId = isset($rec['reason_code_id']) && $rec['reason_code_id'] !== '' && $rec['reason_code_id'] !== null
            ? (int) $rec['reason_code_id']
            : null;
        $freeReason = trim((string) ($rec['reason'] ?? ''));
        $notes = trim((string) ($rec['excuse_notes'] ?? $freeReason));

        $presetReason = null;
        $isMedical = false;
        $isExcused = false;
        if ($reasonCodeId) {
            $code = AttendanceReasonCode::find($reasonCodeId);
            if ($code) {
                $presetReason = $code->name;
                $isMedical = (bool) $code->is_medical;
                $isExcused = (bool) $code->requires_excuse;
            } else {
                $reasonCodeId = null;
            }
        }

        $attendance->reason = $presetReason ?: ($freeReason !== '' ? $freeReason : null);
        $attendance->reason_code_id = $reasonCodeId;
        $attendance->is_excused = $isExcused;
        $attendance->is_medical_leave = $isMedical;
        $attendance->excuse_notes = $notes !== '' ? $notes : null;
    }

    protected function notifyParentAbsent(Student $student, ?string $reason = null): void
    {
        try {
            $tpl = CommunicationTemplate::where('code', 'attendance_absent_sms')->first();
            if (!$tpl) {
                $tpl = CommunicationTemplate::firstOrCreate(
                    ['code' => 'attendance_absent_sms'],
                    [
                        'title' => 'Attendance: Absent (SMS)',
                        'type' => 'sms',
                        'content' => "Dear {{parent_name}},\n\n{{student_name}} was marked absent today. If clarification is needed, kindly contact the school.\n\nRegards,\n{{school_name}}",
                    ]
                );
            }

            $schoolName = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');
            $messageTemplate = str_replace(
                ['{{student_name}}', '{{attendance_status}}', '{{attendance_date}}', '{{attendance_reason}}', '{{school_name}}'],
                [$student->full_name, 'absent', 'today', $reason ?: '', $schoolName],
                $tpl->content ?? ''
            );
            if (filled($reason) && ! str_contains((string) ($tpl->content ?? ''), '{{attendance_reason}}')) {
                $messageTemplate = rtrim($messageTemplate)."\nReason: ".$reason;
            }

            $parentNotify = app(\App\Services\ParentSchoolNotificationService::class);
            foreach ($parentNotify->smsRecipients($student->parent) as $r) {
                $phone = $r['phone'] ?? null;
                if (! $phone) {
                    continue;
                }
                $message = personalize_message_for_parent_recipient($messageTemplate, $student, $r);
                if ($message === null) {
                    continue;
                }
                $this->smsService->sendSMS($phone, $message);
                CommunicationLog::create([
                    'recipient_type' => 'parent',
                    'recipient_id' => $student->parent->id ?? null,
                    'contact' => $phone,
                    'channel' => 'sms',
                    'message' => $message,
                    'status' => 'sent',
                    'title' => 'attendance_absent_sms',
                    'target' => 'attendance',
                    'type' => 'sms',
                    'sent_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
