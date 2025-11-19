<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\ParentInfo;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;

class BulkCommunicationController extends Controller
{
    protected $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function index()
    {
        return view('communication.bulk.index');
    }

    public function create(Request $request)
    {
        $type = $request->get('type', 'email'); // email or sms
        $students = Student::with(['classroom', 'parent'])->orderBy('first_name')->get();
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        $templates = CommunicationTemplate::where('type', $type)->get();

        return view('communication.bulk.create', compact('type', 'students', 'classrooms', 'templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:email,sms',
            'target' => 'required|in:all_students,selected_students,classroom,grade',
            'student_ids' => 'required_if:target,selected_students|array',
            'student_ids.*' => 'exists:students,id',
            'classroom_id' => 'required_if:target,classroom|exists:classrooms,id',
            'subject' => 'required_if:type,email|string|max:255',
            'message' => 'required|string',
            'template_id' => 'nullable|exists:communication_templates,id',
        ]);

        $recipients = $this->collectRecipients($validated, $validated['type']);
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $contact => $entity) {
            try {
                $message = $validated['message'];
                if ($validated['template_id']) {
                    $template = CommunicationTemplate::find($validated['template_id']);
                    $message = replace_placeholders($template->content, $entity);
                } else {
                    $message = replace_placeholders($message, $entity);
                }

                if ($validated['type'] === 'email') {
                    Mail::to($contact)->send(new GenericMail(
                        $validated['subject'] ?? 'Message from School',
                        $message
                    ));
                } else {
                    $this->smsService->sendSMS($contact, $message);
                }

                CommunicationLog::create([
                    'recipient_type' => 'student',
                    'recipient_id' => $entity->id ?? null,
                    'contact' => $contact,
                    'channel' => $validated['type'],
                    'title' => $validated['subject'] ?? 'Bulk Message',
                    'message' => $message,
                    'type' => $validated['type'],
                    'status' => 'sent',
                    'classroom_id' => $entity->classroom_id ?? null,
                    'scope' => 'bulk',
                    'sent_at' => now(),
                ]);

                $sent++;
            } catch (\Exception $e) {
                CommunicationLog::create([
                    'recipient_type' => 'student',
                    'recipient_id' => $entity->id ?? null,
                    'contact' => $contact,
                    'channel' => $validated['type'],
                    'title' => $validated['subject'] ?? 'Bulk Message',
                    'message' => $validated['message'],
                    'type' => $validated['type'],
                    'status' => 'failed',
                    'response' => $e->getMessage(),
                    'scope' => 'bulk',
                    'sent_at' => now(),
                ]);
                $failed++;
            }
        }

        return redirect()->route('communication.bulk.index')
            ->with('success', "Bulk {$validated['type']} sent: {$sent} successful, {$failed} failed.");
    }

    protected function collectRecipients(array $data, string $type)
    {
        $recipients = [];

        if ($data['target'] === 'all_students') {
            $students = Student::with('parent')->get();
        } elseif ($data['target'] === 'selected_students') {
            $students = Student::whereIn('id', $data['student_ids'])->with('parent')->get();
        } elseif ($data['target'] === 'classroom') {
            $students = Student::where('classroom_id', $data['classroom_id'])->with('parent')->get();
        } else {
            $students = collect();
        }

        foreach ($students as $student) {
            $parent = $student->parent;
            if ($type === 'email' && $parent) {
                $email = $parent->father_email ?? $parent->mother_email ?? $parent->guardian_email ?? null;
                if ($email) {
                    $recipients[$email] = $student;
                }
            } elseif ($type === 'sms' && $parent) {
                $phone = $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone ?? null;
                if ($phone) {
                    $recipients[$phone] = $student;
                }
            }
        }

        return $recipients;
    }
}
