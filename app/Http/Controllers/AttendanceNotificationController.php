<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecipient;
use App\Models\Staff;
use App\Models\Classroom;
use Illuminate\Http\Request;
use App\Services\CommunicationService;
use Carbon\Carbon;

class AttendanceNotificationController extends Controller
{
    protected $comm;

    public function __construct(CommunicationService $comm)
    {
        $this->comm = $comm;
    }

    // Recipients index
    public function index()
    {
        $recipients = AttendanceRecipient::with('staff')->get();
        $classrooms = Classroom::pluck('name', 'id')->toArray();

        return view('attendance_notifications.index', compact('recipients', 'classrooms'));
    }

    // Create form
    public function create()
    {
        $staff = Staff::all();
        $classrooms = Classroom::pluck('name', 'id')->toArray();
        return view('attendance_notifications.create', compact('staff', 'classrooms'));
    }

    // Store new
    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'staff_id' => 'required|exists:staff,id',
            'classroom_ids' => 'array|nullable',
            'active' => 'required|boolean',
        ]);

        AttendanceRecipient::create($data);
        return redirect()->route('attendance.notifications.index')->with('success', 'Recipient added.');
    }

    // Edit form
    public function edit($id)
    {
        $recipient = AttendanceRecipient::findOrFail($id);
        $staff = Staff::all();
        $classrooms = Classroom::pluck('name', 'id')->toArray();
        return view('attendance_notifications.edit', compact('recipient', 'staff', 'classrooms'));
    }

    // Update
    public function update(Request $request, $id)
    {
        $recipient = AttendanceRecipient::findOrFail($id);

        $data = $request->validate([
            'label' => 'required|string|max:255',
            'staff_id' => 'required|exists:staff,id',
            'classroom_ids' => 'array|nullable',
            'active' => 'required|boolean',
        ]);

        $recipient->update($data);
        return redirect()->route('attendance.notifications.index')->with('success', 'Recipient updated.');
    }

    // Destroy
    public function destroy($id)
    {
        AttendanceRecipient::findOrFail($id)->delete();
        return redirect()->route('attendance.notifications.index')->with('success', 'Recipient deleted.');
    }

    // Notify (manual trigger)
    public function notifyForm(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        // Check if attendance is complete
        $studentsCount = \App\Models\Student::count();
        $attendanceCount = \App\Models\Attendance::whereDate('date', $date)->count();

        $isComplete = ($studentsCount > 0 && $attendanceCount >= $studentsCount);

        // Attendance summary by class
        $summaryByClass = \App\Models\Attendance::whereDate('date', $date)
            ->where('status', 'present')
            ->with('student.classroom')
            ->get()
            ->groupBy(fn($a) => optional($a->student->classroom)->name ?? 'Unassigned')
            ->map->count();

        // Recipients list
        $recipients = \App\Models\AttendanceRecipient::with('staff')->get();

        return view('attendance_notifications.notify', [
            'date' => $date,
            'isComplete' => $isComplete,   // ✅ FIX
            'summaryByClass' => $summaryByClass,
            'recipients' => $recipients,
        ]);
    }

    public function notifySend(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        $recipients = AttendanceRecipient::with('staff')->where('active', true)->get();

        foreach ($recipients as $r) {
            if ($r->staff && $r->staff->phone_number) {
                $msg = "Attendance summary for {$date} is ready.";
                $this->comm->sendSMS('staff', $r->staff->id, $r->staff->phone_number, $msg);
            }
        }

        return redirect()->route('attendance.notifications.index')->with('success', 'Notifications sent.');
    }
}
