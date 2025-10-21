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
        $classes = Classroom::with('streams')->get();
        return view('communication.send_email', compact('templates', 'classes'));
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
        $classes = Classroom::with('streams')->get();
        return view('communication.send_sms', compact('templates', 'classes'));
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

        foreach ($recipients as $phone => $entity) {
            try {
                $personalized = replace_placeholders($message, $entity);
                $response     = $smsService->sendSMS($phone, $personalized);

                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $phone,
                    'channel'        => 'sms',
                    'message'        => $personalized,
                    'type'           => 'sms',
                    'status'         => 'sent',
                    'response'       => json_encode($response),
                    'classroom_id'   => $entity->classroom_id ?? null,
                    'scope'          => 'sms',
                    'sent_at'        => now(),
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
}
