<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\CommunicationLog;
use App\Models\ScheduledCommunication;
use App\Models\SMSTemplate;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;
use App\Services\SMSService;

class CommunicationController extends Controller
{
    // ===== EMAIL =====
    public function createEmail()
    {
        abort_unless(can_access("communication", "email", "add"), 403);
        $templates = EmailTemplate::all();
        return view('communication.send_email', compact('templates'));
    }

    public function sendEmail(Request $request)
    {
        abort_unless(can_access("communication", "email", "add"), 403);
        $request->validate([
            'target' => 'required',
            'template_id' => 'nullable|exists:email_templates,id',
            'title' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,png,pdf,docx,doc',
            'custom_emails' => 'nullable|string'
        ]);

        $recipients = [];

        // Get subject and message from manual or template
        $subject = $request->title;
        $messageBody = $request->message;

        if ($request->template_id) {
            $template = EmailTemplate::find($request->template_id);
            $subject = $template->title ?? $subject;
            $messageBody = $template->message ?? $messageBody;
        }

        // Ensure there's something to send
        if (!$messageBody) {
            return back()->with('error', 'Message content is required.');
        }

        // Optional file upload
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('email_attachments', 'public');
        }

        // Handle custom recipients
        if ($request->custom_emails) {
            $emails = explode(',', $request->custom_emails);
            $recipients = array_merge($recipients, array_map('trim', $emails));
        }

        // Handle group recipients
        if ($request->target !== 'custom') {
            $recipients = array_merge($recipients, $this->getGroupEmails($request->target));
        }

        // Clean recipients
        $recipients = array_unique(array_filter($recipients, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));

        // Fallbacks
        $subject = $subject ?: 'Untitled Email';
        $targetLabel = $request->target ?: 'custom';

        // Send and log
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new GenericMail($subject, $messageBody, $attachmentPath));

                CommunicationLog::create([
                    'recipient_type' => $targetLabel,
                    'recipient_id' => null,
                    'contact' => $email,
                    'channel' => 'email',
                    'message' => $messageBody,
                    'status' => 'sent',
                    'response' => 'OK',
                    'title' => $subject,
                    'target' => $targetLabel,
                    'type' => 'email',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                CommunicationLog::create([
                    'recipient_type' => $targetLabel,
                    'recipient_id' => null,
                    'contact' => $email,
                    'channel' => 'email',
                    'message' => $messageBody,
                    'status' => 'failed',
                    'response' => $e->getMessage(),
                    'title' => $subject,
                    'target' => $targetLabel,
                    'type' => 'email',
                    'sent_at' => now(),
                ]);
            }
        }

        return redirect()->route('communication.send.email')->with('success', 'Emails sent successfully.');
    }

    private function getGroupEmails($group)
    {
        switch ($group) {
            case 'students':
                return \App\Models\Student::pluck('email')->filter()->toArray();

            case 'parents':
                return array_filter(array_merge(
                    \App\Models\ParentInfo::pluck('father_email')->toArray(),
                    \App\Models\ParentInfo::pluck('mother_email')->toArray(),
                    \App\Models\ParentInfo::pluck('guardian_email')->toArray()
                ));

            case 'teachers':
                return \App\Models\Staff::where('role', 'teacher')->pluck('email')->filter()->toArray();

            case 'staff':
                return \App\Models\Staff::pluck('email')->filter()->toArray();

            default:
                return [];
        }
    }

    // ===== SMS =====
    public function createSMS()
    {
        abort_unless(can_access("communication", "sms", "add"), 403);
        $templates = SMSTemplate::all();
        return view('communication.send_sms', compact('templates'));
    }

public function sendSMS(Request $request, SMSService $smsService)
{
        abort_unless(can_access("communication", "sms", "add"), 403);
    $request->validate([
        'template_id' => 'nullable|exists:sms_templates,id',
        'message' => 'nullable|string|max:300',
        'target' => 'required',
        'custom_numbers' => 'nullable|string',
    ]);

    // Get the message
    $message = $request->message;
    if ($request->template_id) {
        $template = SMSTemplate::find($request->template_id);
        $message = $template->message;
    }

    if (!$message) {
        return back()->with('error', 'Message is required.');
    }

    // Build recipient list
    $numbers = [];

    if ($request->custom_numbers) {
        $numbers = array_merge($numbers, array_map('trim', explode(',', $request->custom_numbers)));
    }

    if ($request->target !== 'custom') {
        $numbers = array_merge($numbers, $this->getPhoneNumbersByGroup($request->target));
    }

    $numbers = array_unique(array_filter($numbers));

    $targetLabel = $request->target;

    foreach ($numbers as $number) {
        try {
            $response = $smsService->sendSMS($number, $message);

            CommunicationLog::create([
                'recipient_type' => $targetLabel,
                'recipient_id' => null,
                'contact' => $number,
                'channel' => 'sms',
                'message' => $message,
                'status' => 'sent',
                'response' => json_encode($response),
                'title' => '-',
                'target' => $targetLabel,
                'type' => 'sms',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            CommunicationLog::create([
                'recipient_type' => $targetLabel,
                'recipient_id' => null,
                'contact' => $number,
                'channel' => 'sms',
                'message' => $message,
                'status' => 'failed',
                'response' => $e->getMessage(),
                'title' => '-',
                'target' => $targetLabel,
                'type' => 'sms',
                'sent_at' => now(),
            ]);
        }
    }

    return redirect()->route('communication.send.sms')->with('success', 'SMS sent successfully!');
}

    private function getPhoneNumbersByGroup($group)
    {
        switch ($group) {
            case 'students':
                return \App\Models\Student::pluck('phone_number')->toArray();
            case 'parents':
                return array_merge(
                    \App\Models\ParentInfo::pluck('father_phone')->toArray(),
                    \App\Models\ParentInfo::pluck('mother_phone')->toArray(),
                    \App\Models\ParentInfo::pluck('guardian_phone')->toArray()
                );
            case 'teachers':
            case 'staff':
                return \App\Models\Staff::pluck('phone_number')->toArray();
            default:
                return [];
        }
    }


    // ===== LOGS =====
    public function logs()
    {
        abort_unless(can_access("communication", "logs", "view"), 403);
        $logs = CommunicationLog::latest()->paginate(20);
        return view('communication.logs', compact('logs'));
    }

    public function logsScheduled()
    {
        abort_unless(can_access("communication", "logs", "view"), 403);
        $logs = ScheduledCommunication::latest()->paginate(20);
        return view('communication.logs_scheduled', compact('logs'));
    }
}
