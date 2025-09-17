<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceRecipient;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationLog;
use App\Services\SMSService;
use Carbon\Carbon;

class AttendanceNotificationController extends Controller
{
    protected SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    // -------------------- SHOW FORM --------------------
    public function notifyForm(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        // Check if attendance is complete for that day
        $isComplete = Attendance::where('date', $date)->exists();

        // Get summary by class (present count)
        $summaryByClass = Attendance::with('student.classroom')
            ->where('date', $date)
            ->where('status', 'present')
            ->get()
            ->groupBy(fn($row) => optional($row->student->classroom)->name ?? 'Unknown')
            ->map(fn($rows) => $rows->count());

        $recipients = AttendanceRecipient::with('staff')->where('active', true)->get();

        return view('attendance_notifications.notify', compact('date', 'isComplete', 'summaryByClass', 'recipients'));
    }

    // -------------------- SEND NOTIFICATIONS --------------------
    public function sendNotify(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        $recipients = AttendanceRecipient::with('staff')->where('active', true)->get();

        // Find template
        $tpl = CommunicationTemplate::where('code', 'attendance_daily_summary')->first();
        if (!$tpl) {
            return back()->with('error', 'Template "attendance_daily_summary" not found. Please seed it first.');
        }

        foreach ($recipients as $r) {
            if (!$r->staff || !$r->staff->phone_number) continue;

            // Build message with placeholders
            $message = str_replace(
                ['{date}', '{label}'],
                [$date, $r->label],
                $tpl->content
            );

            try {
                $response = $this->smsService->sendSMS($r->staff->phone_number, $message);

                CommunicationLog::create([
                    'recipient_type' => 'staff',
                    'recipient_id'   => $r->staff_id,
                    'contact'        => $r->staff->phone_number,
                    'channel'        => 'sms',
                    'message'        => $message,
                    'status'         => 'sent',
                    'response'       => json_encode($response),
                    'title'          => $tpl->title ?? 'attendance_daily_summary',
                    'target'         => 'attendance',
                    'type'           => 'sms',
                    'sent_at'        => now(),
                ]);
            } catch (\Exception $e) {
                CommunicationLog::create([
                    'recipient_type' => 'staff',
                    'recipient_id'   => $r->staff_id,
                    'contact'        => $r->staff->phone_number,
                    'channel'        => 'sms',
                    'message'        => $message,
                    'status'         => 'failed',
                    'response'       => $e->getMessage(),
                    'title'          => $tpl->title ?? 'attendance_daily_summary',
                    'target'         => 'attendance',
                    'type'           => 'sms',
                    'sent_at'        => now(),
                ]);
            }
        }

        return back()->with('success', 'Attendance notifications sent successfully.');
    }
}
