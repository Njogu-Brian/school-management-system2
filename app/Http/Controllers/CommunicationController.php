<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CommunicationLog;
use App\Models\ScheduledCommunication;
use App\Models\CommunicationTemplate;
use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Services\CommunicationRecipientService;
use App\Services\CommunicationService;

class CommunicationController extends Controller
{
    public function __construct(
        protected CommunicationRecipientService $recipientService,
        protected CommunicationService $communicationService,
    ) {
    }

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

        $recipients = $this->recipientService->resolveDetailed($data, 'email');

        foreach ($recipients->chunk(100) as $chunk) {
            foreach ($chunk as $recipient) {
                $entity = $recipient['entity'] ?? null;
                $extra  = $recipient['extra'] ?? [];
                $personalized = replace_placeholders($messageBody, $entity, $extra);

                $this->communicationService->sendEmail(
                    recipientType: $recipient['recipient_type'],
                    recipientId: $recipient['entity_id'],
                    email: $recipient['contact'],
                    subject: $subject,
                    htmlMessage: $personalized,
                    attachmentPath: $attachmentPath,
                    meta: [
                        'type'         => 'email',
                        'scope'        => 'email',
                        'title'        => $subject,
                        'classroom_id' => $entity->classroom_id ?? null,
                    ]
                );
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

    public function sendSMS(Request $request)
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

        $recipients = $this->recipientService->resolveDetailed($data, 'sms');
        $title = 'SMS';
        if (!empty($data['template_id'])) {
            $tpl   = CommunicationTemplate::find($data['template_id']);
            $title = $tpl?->title ?: $title;
        }
        foreach ($recipients->chunk(200) as $chunk) {
            foreach ($chunk as $recipient) {
                $entity = $recipient['entity'] ?? null;
                $extra  = $recipient['extra'] ?? [];
                $personalized = replace_placeholders($message, $entity, $extra);

                $this->communicationService->sendSMS(
                    recipientType: $recipient['recipient_type'],
                    recipientId: $recipient['entity_id'],
                    phone: $recipient['contact'],
                    message: $personalized,
                    meta: [
                        'type'         => 'sms',
                        'scope'        => 'sms',
                        'title'        => $title,
                        'classroom_id' => $entity->classroom_id ?? null,
                    ]
                );
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

    public function smsDeliveryReport(Request $request)
    {
        // Typical provider fields (rename to yours)
        $providerId = $request->input('id') 
            ?? $request->input('message_id') 
            ?? $request->input('MessageID');

        $status     = strtolower($request->input('status', ''));
        $delivered  = $request->input('delivered_at') ?? $request->input('done_time');
        $errorCode  = $request->input('error_code');

        if (!$providerId) {
            return response()->json(['ok' => false, 'reason' => 'missing provider id'], 422);
        }

        $log = \App\Models\CommunicationLog::where('provider_id', $providerId)->first();
        if (!$log) {
            return response()->json(['ok' => false, 'reason' => 'log not found'], 404);
        }

        // Map provider statuses to app statuses
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
        ];
        $appStatus = $map[$status] ?? $log->status;

        $log->update([
            'status'          => $appStatus,
            'provider_status' => $status ?: $log->provider_status,
            'delivered_at'    => $delivered ? \Illuminate\Support\Carbon::parse($delivered) : $log->delivered_at,
            'error_code'      => $errorCode,
        ]);

        return response()->json(['ok' => true]);
    }
}
