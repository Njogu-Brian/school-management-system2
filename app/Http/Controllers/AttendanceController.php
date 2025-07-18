<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SMSService;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    protected $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function showForm(Request $request)
    {
        abort_unless(can_access("attendance", "mark_attendance", "view"), 403);

        $user = Auth::user();
        $selectedClass = $request->input('class', '');
        $selectedDate = $request->input('date', today()->toDateString());

        $classes = collect();
        $streamIds = [];

        if ($user->hasRole('teacher')) {
            $streamIds = $user->streams->pluck('classroom_id')->unique()->toArray();
            $classes = \App\Models\Classroom::whereIn('id', $streamIds)->pluck('name', 'id');
        } else {
            $classes = \App\Models\Classroom::pluck('name', 'id');
        }

        $students = collect();
        $attendanceRecords = collect();

        if ($selectedClass) {
            if ($user->hasRole('teacher') && !in_array($selectedClass, $streamIds)) {
                abort(403, 'Unauthorized classroom access.');
            }

            $students = Student::with('classroom')->where('classroom_id', $selectedClass)->get();
            $attendanceRecords = Attendance::whereIn('student_id', $students->pluck('id'))
                ->whereDate('date', $selectedDate)
                ->get()->keyBy('student_id');
        }

        return view('attendance.mark', compact('classes', 'selectedClass', 'students', 'attendanceRecords', 'selectedDate'));
    }

    public function markAttendance(Request $request)
    {
        abort_unless(can_access("attendance", "mark_attendance", "add"), 403);

        $request->validate([
            'class' => 'required|exists:classrooms,id',
        ]);

        $user = Auth::user();

        if ($user->hasRole('teacher')) {
            $streamIds = $user->streams->pluck('classroom_id')->unique()->toArray();
            if (!in_array($request->class, $streamIds)) {
                abort(403, 'Unauthorized class.');
            }
        }

        $students = Student::where('classroom_id', $request->class)->get();
        $smsTemplate = SmsTemplate::where('code', 'absentee_notification')->first();

        foreach ($students as $student) {
            $status = $request->input("status_{$student->id}");
            if ($status === null) continue;

            $isPresent = $status == "1" ? 1 : 0;
            $reason = $request->input("reason_{$student->id}", null);

            Attendance::updateOrCreate(
                ['student_id' => $student->id, 'date' => today()],
                ['is_present' => $isPresent, 'reason' => $isPresent ? null : $reason]
            );

            if (!$isPresent && $student->parent) {
                $message = $smsTemplate
                    ? str_replace(['{name}', '{class}', '{reason}'], [$student->full_name, $student->classroom->name, $reason], $smsTemplate->message)
                    : "Dear Parent, your child {$student->full_name} was marked absent. Reason: {$reason}.";

                $phones = collect([
                    $student->parent->father_phone,
                    $student->parent->mother_phone,
                    $student->parent->guardian_phone
                ])->filter();

                foreach ($phones as $phone) {
                    $this->smsService->sendSMS($phone, $message);
                }

                if ($student->trip && $student->trip->driver_phone) {
                    $driverMessage = "Driver Alert: {$student->full_name} from {$student->classroom->name} is absent today.";
                    $this->smsService->sendSMS($student->trip->driver_phone, $driverMessage);
                }
            }
        }

        return back()->with('success', 'Attendance marked successfully.');
    }

    public function edit($id)
    {
        abort_unless(can_access("attendance", "mark_attendance", "edit"), 403);

        $attendance = Attendance::find($id);
        if (!$attendance) return back()->with('error', 'Record not found.');

        $user = Auth::user();
        if ($user->hasRole('teacher')) {
            $streamIds = $user->streams->pluck('classroom_id')->unique()->toArray();
            if (!in_array($attendance->student->classroom_id, $streamIds)) {
                abort(403);
            }
        }

        return view('attendance.edit', compact('attendance'));
    }

    public function updateAttendance(Request $request, $id)
    {
        abort_unless(can_access("attendance", "mark_attendance", "edit"), 403);

        $request->validate([
            'is_present' => 'required|in:0,1',
            'reason' => 'nullable|string|max:255',
        ]);

        $attendance = Attendance::find($id);
        if (!$attendance) return back()->with('error', 'Not found.');

        $student = $attendance->student;

        $user = Auth::user();
        if ($user->hasRole('teacher')) {
            $streamIds = $user->streams->pluck('classroom_id')->unique()->toArray();
            if (!in_array($student->classroom_id, $streamIds)) {
                abort(403);
            }
        }

        $wasPresent = $attendance->is_present == 1;
        $isNowAbsent = $request->is_present == 0;

        $attendance->update([
            'is_present' => $request->is_present,
            'reason' => $isNowAbsent ? $request->reason : null
        ]);

        if ($wasPresent && $isNowAbsent && $student && $student->parent) {
            $template = SmsTemplate::where('code', 'absentee_notification')->first();
            $message = $template
                ? str_replace(['{name}', '{class}', '{reason}'], [$student->full_name, $student->classroom->name, $request->reason], $template->message)
                : "Dear Parent, your child {$student->full_name} is absent today.";

            $phones = collect([
                $student->parent->father_phone,
                $student->parent->mother_phone,
                $student->parent->guardian_phone
            ])->filter();

            foreach ($phones as $phone) {
                $this->smsService->sendSMS($phone, $message);
            }
        }

        return redirect()->route('attendance.mark.form')->with('success', 'Attendance updated.');
    }
}
