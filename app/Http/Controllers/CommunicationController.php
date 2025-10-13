<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\CommunicationLog;
use App\Models\ScheduledCommunication;
use App\Models\CommunicationTemplate;
use App\Mail\GenericMail;
use App\Services\SMSService;

class CommunicationController extends Controller
{
    // ===== TEMPLATES =====
    public function index()
    {
        $templates = CommunicationTemplate::where('type', 'sms')->get();
        return view('communication.sms_templates.index', compact('templates'));
    }

    public function create()
    {
        return view('communication.sms_templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'code'    => 'required|string|max:100|unique:communication_templates,code',
            'content' => 'required|string|max:300',
        ]);

        $data['type'] = 'sms';
        CommunicationTemplate::create($data);

        return redirect()->route('sms-templates.index')->with('success', 'SMS Template created successfully.');
    }

    // ===== EMAIL =====
    public function createEmail()
    {
        abort_unless(can_access("communication", "email", "add"), 403);
        $templates = CommunicationTemplate::where('type', 'email')->get();
        return view('communication.send_email', compact('templates'));
    }

    public function sendEmail(Request $request)
    {
        abort_unless(can_access("communication", "email", "add"), 403);

        $request->validate([
            'template_id'    => 'nullable|exists:communication_templates,id',
            'message'        => 'nullable|string',
            'target'         => 'required|string',
            'custom_emails'  => 'nullable|string',
            'title'          => 'nullable|string|max:255',
            'attachment'     => 'nullable|file|mimes:jpg,png,pdf,docx,doc',
        ]);

        $target = $request->target; // ✅ define target group
        $subject = $request->title ?? 'Untitled Email';
        $messageBody = $request->message;

        if ($request->template_id) {
            $tpl = CommunicationTemplate::find($request->template_id);
            $subject = $tpl->title ?: $subject;
            $messageBody = $tpl->content ?: $messageBody;
        }

        if (!$messageBody) {
            return back()->with('error', 'Message body is required.');
        }

        $attachmentPath = $request->file('attachment')
            ? $request->file('attachment')->store('email_attachments', 'public')
            : null;

        // get [email => entity] pairs
        $recipients = $this->collectRecipients($target, $request->custom_emails, 'email');

        foreach ($recipients as $email => $entity) {
            try {
                $personalized = replace_placeholders($messageBody, $entity);
                Mail::to($email)->send(new GenericMail($subject, $personalized, $attachmentPath));

                CommunicationLog::create([
                    'recipient_type' => $target,
                    'contact' => $email,
                    'channel' => 'email',
                    'title'   => $subject,
                    'message' => $personalized,
                    'type'    => 'email',
                    'status'  => 'sent',
                    'response'=> 'OK',
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {
                CommunicationLog::create([
                    'recipient_type' => $target,
                    'contact' => $email,
                    'channel' => 'email',
                    'title'   => $subject,
                    'message' => $messageBody,
                    'type'    => 'email',
                    'status'  => 'failed',
                    'response'=> $e->getMessage(),
                    'sent_at' => now(),
                ]);
            }
        }

        return redirect()->route('communication.send.email')->with('success', 'Emails sent successfully.');
    }

    // ===== SMS =====
    public function createSMS()
    {
        abort_unless(can_access("communication", "sms", "add"), 403);
        $templates = CommunicationTemplate::where('type', 'sms')->get();
        return view('communication.send_sms', compact('templates'));
    }

    public function sendSMS(Request $request, SMSService $smsService)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        $request->validate([
            'template_id'    => 'nullable|exists:communication_templates,id',
            'message'        => 'nullable|string|max:300',
            'target'         => 'required|string',
            'custom_numbers' => 'nullable|string',
        ]);

        $target = $request->target; // ✅ define target group
        $message = $request->message;

        if ($request->template_id) {
            $tpl = CommunicationTemplate::find($request->template_id);
            $message = $tpl->content ?: $message;
        }

        if (!$message) {
            return back()->with('error', 'Message content is required.');
        }

        // get [phone => entity] pairs
        $recipients = $this->collectRecipients($target, $request->custom_numbers, 'sms');

        foreach ($recipients as $phone => $entity) {
            try {
                $personalized = replace_placeholders($message, $entity);
                $response = $smsService->sendSMS($phone, $personalized);

                CommunicationLog::create([
                    'recipient_type' => $target,
                    'contact' => $phone,
                    'channel' => 'sms',
                    'title' => $tpl->title ?? 'SMS Message',
                    'message' => $personalized,
                    'type'    => 'sms',
                    'status'  => 'sent',
                    'response'=> json_encode($response),
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {
                CommunicationLog::create([
                    'recipient_type' => $target,
                    'contact' => $phone,
                    'channel' => 'sms',
                    'title' => $tpl->title ?? 'SMS Message',
                    'message' => $message,
                    'type'    => 'sms',
                    'status'  => 'failed',
                    'response'=> $e->getMessage(),
                    'sent_at' => now(),
                ]);
            }
        }

        return redirect()->route('communication.send.sms')->with('success', 'SMS sent successfully!');
    }

    /**
     * Build a map of recipients => entity used for personalization.
     * $target: students|parents|teachers|staff|custom
     * $custom: comma-separated emails/phones (depending on $type)
     * $type  : 'email' or 'sms'
     */
    private function collectRecipients(string $target, ?string $custom, string $type): array
    {
        $out = [];

        // custom manual entries
        if ($custom) {
            foreach (array_map('trim', explode(',', $custom)) as $item) {
                if ($item !== '') $out[$item] = null; // no entity -> only global placeholders
            }
        }

        // Students
        if ($target === 'students') {
            \App\Models\Student::with('parent', 'classroom')->get()->each(function ($s) use (&$out, $type) {
                $contact = $type === 'email' ? $s->email : $s->phone_number;
                if ($contact) $out[$contact] = $s;
            });
        }

        // Parents
        if ($target === 'parents') {
            \App\Models\Student::with('parent', 'classroom')->get()->each(function ($s) use (&$out, $type) {
                $p = $s->parent;
                if (!$p) return;
                $contacts = $type === 'email'
                    ? [$p->father_email, $p->mother_email, $p->guardian_email]
                    : [$p->father_phone, $p->mother_phone, $p->guardian_phone];
                foreach ($contacts as $c) {
                    if ($c) $out[$c] = $s;
                }
            });
        }

        // Teachers or Staff
        if (in_array($target, ['teachers', 'staff'])) {
            \App\Models\Staff::all()->each(function ($st) use (&$out, $type) {
                $contact = $type === 'email' ? $st->email : $st->phone_number;
                if ($contact) $out[$contact] = $st;
            });
        }

        return $out;
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
