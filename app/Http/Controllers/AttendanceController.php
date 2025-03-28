<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SMSService;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class AttendanceController extends Controller
{
    protected $smsService;

    

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Show the attendance marking form with students based on the selected class.
     */
    public function showForm(Request $request)
{
    // Fetch distinct classes from the classrooms table instead of the students table
    $classes = \App\Models\Classroom::pluck('name', 'id');

    $selectedClass = $request->input('class', '');
    $selectedDate = $request->input('date', today()->toDateString());

    $students = collect();
    $attendanceRecords = collect();

    if ($selectedClass) {
        // Fetch students based on classroom_id instead of class
        $students = Student::with('classroom')->where('classroom_id', $selectedClass)->get();
        if ($students->isEmpty()) {
            Log::info("No students found for Class ID: $selectedClass");
        } else {
            Log::info("Students found for Class ID: $selectedClass - Count: " . $students->count());
        }
        
        $attendanceRecords = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereDate('date', $selectedDate)
            ->get()
            ->keyBy('student_id');
    }

    return view('attendance.mark', compact('classes', 'selectedClass', 'students', 'attendanceRecords', 'selectedDate'));
}



    /**
     * Mark attendance for students in a class.
     */
    public function markAttendance(Request $request)
{
    $request->validate([
        'class' => 'required|exists:classrooms,id', // Validate class using classrooms table
    ]);

    // Retrieve students using classroom_id instead of class
    $students = Student::where('classroom_id', $request->class)->get();

    foreach ($students as $student) {
        $status = $request->input("status_{$student->id}");
        if ($status === null) {
            continue; // Skip if no status is selected
        }

        $isPresent = $status == "1" ? 1 : 0;
        $reason = $request->input("reason_{$student->id}", null);

        Attendance::updateOrCreate(
            ['student_id' => $student->id, 'date' => today()],
            ['is_present' => $isPresent, 'reason' => $isPresent ? null : $reason]
        );

        // Send SMS if absent
        if (!$isPresent && $student->parent && $student->parent->phone) {
            $message = "Dear Parent, your child {$student->getFullNameAttribute()} (Class: {$student->classroom->name}) was marked absent today. Reason: {$reason}.";

            try {
                $this->smsService->sendSMS($student->parent->phone, $message);
                Log::info("Absent SMS sent to parent: {$student->parent->phone}");
                \App\Models\SmsLog::create([
                    'phone_number' => $student->parent->phone,
                    'message' => $message,
                    'status' => 'sent',
                    'response' => 'Success'
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send absent SMS: " . $e->getMessage());
                \App\Models\SmsLog::create([
                    'phone_number' => $student->parent->phone,
                    'message' => $message,
                    'status' => 'failed',
                    'response' => $e->getMessage()
                ]);
            }
        }
    }

    return redirect()->back()->with('success', 'Attendance marked successfully.');
}

    
    /**
 * Show edit form for attendance.
 */
public function edit($id)
{
    $attendance = Attendance::find($id);

    if (!$attendance) {
        return redirect()->back()->with('error', 'Attendance record not found.');
    }

    return view('attendance.edit', compact('attendance'));
}



    /**
     * Update attendance.
     */
    public function updateAttendance(Request $request, $id)
{
    // Validate input
    $validatedData = $request->validate([
        'is_present' => 'required|in:0,1', 
        'reason' => 'nullable|string|max:255',
    ]);

    // Find the attendance record
    $attendance = Attendance::find($id);

    if (!$attendance) {
        return redirect()->back()->with('error', 'Attendance record not found.');
    }

    // Get student information
    $student = $attendance->student;

    // Ensure correct data types
    $isPresent = (int) $validatedData['is_present'];

    // Check if attendance was previously "Present" and is now "Absent"
    $wasPreviouslyPresent = $attendance->is_present == 1;
    $isNowAbsent = $isPresent == 0;

    // Update attendance record
    $attendance->forceFill([
        'is_present' => $isPresent,
        'reason' => $isNowAbsent ? $validatedData['reason'] : null,
    ])->save();

    // Send SMS if the status changed from Present to Absent
    if ($wasPreviouslyPresent && $isNowAbsent && $student->parent && $student->parent->phone) {
        $message = "Dear Parent, your child {$student->name} (Class: {$student->class}) was marked absent today. Reason: {$validatedData['reason']}.";

        try {
            $this->smsService->sendSMS($student->parent->phone, $message);
            Log::info("Absent SMS sent to parent: {$student->parent->phone}");

            // **Log the SMS in `sms_logs`**
            \App\Models\SmsLog::create([
                'phone_number' => $student->parent->phone,
                'message' => $message,
                'status' => 'sent',
                'response' => 'Success'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send absent SMS: " . $e->getMessage());

            // **Log failed SMS attempt**
            \App\Models\SmsLog::create([
                'phone_number' => $student->parent->phone,
                'message' => $message,
                'status' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    return redirect()->route('attendance.mark.form')->with('success', 'Attendance updated successfully.');
}


}
