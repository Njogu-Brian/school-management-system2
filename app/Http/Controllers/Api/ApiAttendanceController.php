<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
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

        $studentQuery = Student::where('classroom_id', $classId)
            ->where('archive', 0)
            ->where('is_alumni', false);
        if ($streamId !== null) {
            $studentQuery->where('stream_id', $streamId);
        }
        $studentIds = $studentQuery->pluck('id');

        $records = Attendance::whereDate('date', $date)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->map(fn ($a) => [
                'student_id' => $a->student_id,
                'status' => $a->status === 'absent' && $a->is_excused ? 'absent' : $a->status,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $records]);
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
        ]);

        $date = Carbon::parse($request->date)->toDateString();
        $classId = (int) $request->class_id;
        $streamId = $request->stream_id ? (int) $request->stream_id : null;
        $user = $request->user();
        $isToday = Carbon::parse($date)->isToday();

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

        // Teacher access
        if ($user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor'])) {
            $hasAccess = in_array($classId, $user->getAssignedClassroomIds(), true);
            if (!$hasAccess && $user->hasRole('Senior Teacher')) {
                $hasAccess = in_array($classId, $user->getSupervisedClassroomIds(), true);
            }
            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'You are not assigned to this class.'], 403);
            }
        }

        $count = 0;

        DB::transaction(function () use ($request, $date, $classId, $streamId, $user, $isToday, &$count) {
            foreach ($request->records as $rec) {
                $studentId = (int) $rec['student_id'];
                $status = $rec['status'];

                $student = Student::where('id', $studentId)
                    ->where('classroom_id', $classId)
                    ->when($streamId !== null, fn ($q) => $q->where('stream_id', $streamId))
                    ->where('archive', 0)
                    ->where('is_alumni', false)
                    ->with('parent')
                    ->first();

                if (!$student) {
                    continue;
                }

                if ($status !== 'unmarked' && ! $this->attendanceCalendar->canMarkAttendanceForDate($student, $date)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more students cannot be marked for this date (not enrolled on this date).',
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
                $attendance->is_excused = false;
                $attendance->marked_by = $user->id;
                $attendance->marked_at = now();
                $attendance->save();

                $count++;

                if ($status === 'absent' && $isToday && $student->parent) {
                    $this->notifyParentAbsent($student);
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

    protected function notifyParentAbsent(Student $student): void
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
            $parentName = $student->parent->primary_contact_name ?? $student->parent->father_name ?? $student->parent->mother_name ?? 'Parent';
            $message = str_replace(
                ['{{student_name}}', '{{attendance_status}}', '{{attendance_date}}', '{{parent_name}}', '{{school_name}}'],
                [$student->full_name, 'absent', 'today', $parentName, $schoolName],
                $tpl->content ?? ''
            );

            $phones = array_values(array_unique(array_filter([
                $student->parent->primary_contact_phone ?? $student->parent->father_phone ?? null,
                $student->parent->mother_phone ?? null,
            ])));

            foreach ($phones as $phone) {
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
