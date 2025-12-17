<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CommunicationLog;
use App\Models\ScheduledCommunication;
use App\Models\CommunicationTemplate;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Academics\Classroom;
use App\Services\SMSService;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;

class CommunicationController extends Controller
{
    /* ========== EMAIL ========== */
    public function createEmail()
    {
        abort_unless(can_access("communication", "email", "add"), 403);

        $templates = CommunicationTemplate::where('type', 'email')->get();
        $classes   = Classroom::with('streams')->get();

        // Sort by full name at the DB level
        $students = Student::query()
            ->orderByRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) ASC")
            ->get();

        return view('communication.send_email', compact('templates', 'classes', 'students'));
    }

    public function sendEmail(Request $request)
    {
        abort_unless(can_access("communication", "email", "add"), 403);

        $data = $request->validate([
            'template_id'    => 'nullable|exists:communication_templates,id',
            'message'        => 'nullable|string',
            'target'         => 'required|string',
            'custom_emails'  => 'nullable|string',
            'title'          => 'nullable|string|max:255',
            'classroom_id'   => 'nullable|integer',
            'student_id'     => 'nullable|integer',
            'attachment'     => 'nullable|file|mimes:jpg,png,pdf,docx,doc',
            'schedule'       => 'nullable|string|in:now,later',
            'send_at'        => 'nullable|date',
        ]);

        $subject     = $data['title'] ?? 'Untitled Email';
        $messageBody = $data['message'];

        if ($data['template_id']) {
            $tpl         = CommunicationTemplate::find($data['template_id']);
            $subject     = $tpl->title   ?: $subject;
            $messageBody = $tpl->content ?: $messageBody;
        }

        if (!$messageBody) {
            return back()->with('error', 'Message body is required.');
        }

        // === HANDLE SCHEDULED EMAIL ===
        if ($request->schedule === 'later' && $request->send_at) {
            ScheduledCommunication::create([
                'type'         => 'email',
                'template_id'  => $data['template_id'] ?? null,
                'target'       => $data['target'],
                'classroom_id' => $data['classroom_id'] ?? null,
                'send_at'      => $data['send_at'],
                'status'       => 'pending',
            ]);
            return redirect()->route('communication.send.email')->with('success', 'Email scheduled for ' . $data['send_at']);
        }

        $attachmentPath = $request->file('attachment')
            ? $request->file('attachment')->store('email_attachments', 'public')
            : null;

        $recipients = $this->collectRecipients($data, 'email');

        foreach ($recipients as $email => $entity) {
            try {
                $personalized = replace_placeholders($messageBody, $entity);
                Mail::to($email)->send(new GenericMail($subject, $personalized, $attachmentPath));

                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $email,
                    'channel'        => 'email',
                    'title'          => $subject,
                    'message'        => $personalized,
                    'type'           => 'email',
                    'status'         => 'sent',
                    'response'       => 'OK',
                    'classroom_id'   => $entity->classroom_id ?? null,
                    'scope'          => 'email',
                    'sent_at'        => now(),
                ]);
            } catch (\Throwable $e) {
                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $email,
                    'channel'        => 'email',
                    'title'          => $subject,
                    'message'        => $messageBody,
                    'type'           => 'email',
                    'status'         => 'failed',
                    'response'       => $e->getMessage(),
                    'scope'          => 'email',
                    'sent_at'        => now(),
                ]);
            }
        }

        return redirect()->route('communication.send.email')->with('success', 'Emails sent successfully.');
    }

    /* ========== SMS ========== */
    public function createSMS()
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        $templates = CommunicationTemplate::where('type', 'sms')->get();
        $classes   = Classroom::with('streams')->get();

        // Same here for the SMS page
        $students = Student::query()
            ->orderByRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) ASC")
            ->get();

        return view('communication.send_sms', compact('templates', 'classes', 'students'));
    }

    public function sendSMS(Request $request, SMSService $smsService)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        $data = $request->validate([
            'template_id'    => 'nullable|exists:communication_templates,id',
            'message'        => 'nullable|string|max:300',
            'target'         => 'required|string',
            'custom_numbers' => 'nullable|string',
            'classroom_id'   => 'nullable|integer',
            'student_id'     => 'nullable|integer',
            'schedule'       => 'nullable|string|in:now,later',
            'send_at'        => 'nullable|date',
        ]);

        $message = $data['message'];
        if ($data['template_id']) {
            $tpl     = CommunicationTemplate::find($data['template_id']);
            $message = $tpl->content ?: $message;
        }

        if (!$message) {
            return back()->with('error', 'Message content is required.');
        }

        // === HANDLE SCHEDULED SMS ===
        if ($request->schedule === 'later' && $request->send_at) {
            ScheduledCommunication::create([
                'type'         => 'sms',
                'template_id'  => $data['template_id'] ?? null,
                'target'       => $data['target'],
                'classroom_id' => $data['classroom_id'] ?? null,
                'send_at'      => $data['send_at'],
                'status'       => 'pending',
            ]);
            return redirect()->route('communication.send.sms')->with('success', 'SMS scheduled for ' . $data['send_at']);
        }

        $recipients = $this->collectRecipients($data, 'sms');
        $title = 'SMS';
        if (!empty($data['template_id'])) {
            $tpl   = CommunicationTemplate::find($data['template_id']);
            $title = $tpl?->title ?: $title;
        }
        foreach ($recipients as $phone => $entity) {
            try {
                $personalized = replace_placeholders($message, $entity);
                $response = $smsService->sendSMS($phone, $personalized);

                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $phone,
                    'channel'        => 'sms',
                    'title'          => $title,
                    'message'        => $personalized,
                    'type'           => 'sms',
                    'status'         => 'sent',
                    'response'       => $response, // will be cast to array
                    'classroom_id'   => $entity->classroom_id ?? null,
                    'scope'          => 'sms',
                    'sent_at'        => now(),

                    // NEW (match to your provider fields):
                    'provider_id'    => data_get($response,'id') 
                                        ?? data_get($response,'message_id') 
                                        ?? data_get($response,'MessageID'),
                    'provider_status'=> strtolower(data_get($response,'status','sent')),
                ]);
            } catch (\Throwable $e) {
                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $phone,
                    'channel'        => 'sms',
                    'message'        => $message,
                    'type'           => 'sms',
                    'status'         => 'failed',
                    'response'       => $e->getMessage(),
                    'scope'          => 'sms',
                    'sent_at'        => now(),
                ]);
            }
        }

        return redirect()->route('communication.send.sms')->with('success', 'SMS sent successfully!');
    }

    /* ========== LOGS ========== */
    public function logs()
    {
        $logs = CommunicationLog::latest()->paginate(20);
        return view('communication.logs', compact('logs'));
    }

    public function logsScheduled()
    {
        $scheduled = ScheduledCommunication::latest()->paginate(20);
        return view('communication.logs_scheduled', compact('scheduled'));
    }

    /* ========== RECIPIENT BUILDER ========== */
    private function collectRecipients(array $data, string $type): array
    {
        $out = [];
        $target = $data['target'];
        $custom = $data['custom_emails'] ?? $data['custom_numbers'] ?? null;

        if ($custom) {
            foreach (array_map('trim', explode(',', $custom)) as $item) {
                if ($item !== '') $out[$item] = null;
            }
        }

        if ($target === 'student' && !empty($data['student_id'])) {
            $student = Student::with('parent', 'classroom')->find($data['student_id']);
            if ($student && $student->parent) {
                $contacts = $type === 'email'
                    ? [$student->parent->father_email, $student->parent->mother_email, $student->parent->guardian_email]
                    : [$student->parent->father_phone, $student->parent->mother_phone, $student->parent->guardian_phone];
                foreach ($contacts as $c) if ($c) $out[$c] = $student;
            }
        }

        if ($target === 'class' && !empty($data['classroom_id'])) {
            $students = Student::with('parent')->where('classroom_id', $data['classroom_id'])->get();
            foreach ($students as $s) {
                if ($s->parent) {
                    $contacts = $type === 'email'
                        ? [$s->parent->father_email, $s->parent->mother_email, $s->parent->guardian_email]
                        : [$s->parent->father_phone, $s->parent->mother_phone, $s->parent->guardian_phone];
                    foreach ($contacts as $c) if ($c) $out[$c] = $s;
                }
            }
        }

        if ($target === 'parents') {
            Student::with('parent')->get()->each(function ($s) use (&$out, $type) {
                if ($s->parent) {
                    $contacts = $type === 'email'
                        ? [$s->parent->father_email, $s->parent->mother_email, $s->parent->guardian_email]
                        : [$s->parent->father_phone, $s->parent->mother_phone, $s->parent->guardian_phone];
                    foreach ($contacts as $c) if ($c) $out[$c] = $s;
                }
            });
        }

        if ($target === 'students') {
            Student::all()->each(function ($s) use (&$out, $type) {
                $contact = $type === 'email' ? $s->email : $s->phone_number;
                if ($contact) $out[$contact] = $s;
            });
        }

        if ($target === 'staff') {
            Staff::all()->each(function ($st) use (&$out, $type) {
                $contact = $type === 'email' ? $st->email : $st->phone_number;
                if ($contact) $out[$contact] = $st;
            });
        }

        return $out;
    }

    /**
     * Handle SMS Delivery Report (DLR) webhook from HostPinnacle
     * Matches exact parameter names from HostPinnacle webhook configuration
     */
    public function smsDeliveryReport(Request $request)
    {
        \Log::info('SMS DLR Webhook Received', ['data' => $request->all()]);

        // Required parameters (from screenshot)
        $transactionId = $request->input('transactionId');
        $messageId = $request->input('messageId');
        $mobileNo = $request->input('mobileNo');
        $errorCode = $request->input('errorCode');
        
        // Time parameters (long format - milliseconds)
        $receivedTime = $request->input('receivedTime'); // Long format
        $deliveredTime = $request->input('deliveredTime'); // Long format
        
        // Optional parameters (from screenshot)
        $status = strtolower($request->input('status', ''));
        $cause = $request->input('cause'); // Status description
        $senderName = $request->input('senderName');
        $length = $request->input('length');
        $channel = $request->input('channel');
        $text = $request->input('text');
        $cost = $request->input('cost');
        $msgType = $request->input('msgType');
        
        // String format timestamps (optional)
        $receivedTimeString = $request->input('receivedTimeString'); // YYYYMMDD HH:MM:SS
        $doneDateString = $request->input('doneDateString'); // YYYYMMDD HH:MM:SS
        
        // Use cause as errorCode if errorCode not provided
        if (!$errorCode && $cause) {
            $errorCode = $cause;
        }
        
        // Convert long timestamp to Carbon if provided
        $deliveredAt = null;
        if ($deliveredTime) {
            try {
                // Convert milliseconds to seconds
                $deliveredAt = \Carbon\Carbon::createFromTimestamp($deliveredTime / 1000);
            } catch (\Exception $e) {
                // Try string format if long format fails
                if ($doneDateString) {
                    try {
                        $deliveredAt = \Carbon\Carbon::createFromFormat('Ymd H:i:s', $doneDateString);
                    } catch (\Exception $e2) {
                        \Log::warning('Failed to parse delivered time', [
                            'deliveredTime' => $deliveredTime,
                            'doneDateString' => $doneDateString
                        ]);
                    }
                }
            }
        }

        // Try to find log by transactionId first (HostPinnacle primary identifier)
        $log = null;
        if ($transactionId) {
            $log = \App\Models\CommunicationLog::where('provider_id', $transactionId)->first();
        }
        
        // Fallback to messageId if transactionId not found
        if (!$log && $messageId) {
            $log = \App\Models\CommunicationLog::where('provider_id', $messageId)->first();
        }

        if (!$log) {
            \Log::warning('SMS DLR webhook: Log not found', [
                'transactionId' => $transactionId,
                'messageId' => $messageId,
                'mobileNo' => $mobileNo
            ]);
            return response()->json(['ok' => false, 'reason' => 'log not found'], 404);
        }

        // Map HostPinnacle statuses to app statuses
        // Use 'status' field if provided, otherwise infer from 'cause'
        $finalStatus = $status;
        if (!$finalStatus && $cause) {
            // Infer status from cause
            if (stripos($cause, 'delivered') !== false) {
                $finalStatus = 'delivered';
            } elseif (stripos($cause, 'failed') !== false || stripos($cause, 'undelivered') !== false) {
                $finalStatus = 'failed';
            }
        }
        
        $map = [
            'delivered'   => 'sent',
            'success'     => 'sent',
            'sent'        => 'sent',
            'queued'      => 'pending',
            'pending'     => 'pending',
            'undelivered' => 'failed',
            'failed'      => 'failed',
            'blacklisted' => 'failed',
            'rejected'    => 'failed',
            'expired'     => 'failed',
        ];
        $appStatus = $map[strtolower($finalStatus)] ?? $log->status;

        $updateData = [
            'status'          => $appStatus,
            'provider_status' => $finalStatus ?: $log->provider_status,
        ];

        if ($deliveredAt) {
            $updateData['delivered_at'] = $deliveredAt;
        }

        if ($errorCode) {
            $updateData['error_code'] = $errorCode;
        }

        $log->update($updateData);

        \Log::info('SMS DLR Updated', [
            'log_id' => $log->id,
            'status' => $appStatus,
            'transactionId' => $transactionId,
            'cause' => $cause,
            'delivered_at' => $deliveredAt
        ]);

        return response()->json(['ok' => true]);
    }
}
