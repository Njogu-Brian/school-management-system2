<?php

namespace App\Http\Controllers;

use App\Mail\GenericMail;
use App\Models\Finance\Invoice;
use App\Models\Payment;
use App\Models\Academics\ReportCard;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use App\Services\CommunicationHelperService;
use App\Services\SMSService;
use App\Services\WhatsAppService;
use App\Models\CommunicationLog;

class CommunicationDocumentController extends Controller
{
    /**
     * Quick-send documents (invoice/receipt/statement/report card) via SMS/Email/WhatsApp.
     * Email will try to attach a fetched PDF; SMS/WhatsApp send links.
     */
    public function send(Request $request, SMSService $smsService, WhatsAppService $whatsAppService)
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(['sms', 'email', 'whatsapp'])],
            'type'    => ['required', Rule::in(['invoice', 'receipt', 'statement', 'report_card'])],
            'ids'     => ['required', 'array', 'min:1'],
            'ids.*'   => ['integer'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $channel  = $data['channel'];
        $type     = $data['type'];
        $ids      = array_unique($data['ids']);
        $subject  = $data['subject'] ?? 'School Update';
        $message  = $data['message'];

        $sent = 0;
        $failed = 0;

        foreach ($ids as $id) {
            [$student, $link, $title] = $this->resolveTarget($type, $id);
            if (!$student || !$link) {
                $failed++;
                continue;
            }

            $recipients = CommunicationHelperService::collectRecipients([
                'target'     => 'student',
                'student_id' => $student->id,
            ], $channel === 'whatsapp' ? 'whatsapp' : $channel);

            if (empty($recipients)) {
                $failed++;
                continue;
            }

            $body = trim($message . "\n\n" . $link);

            foreach ($recipients as $contact => $entity) {
                try {
                    $response = null;
                    $status   = 'sent';

            if ($channel === 'sms') {
                        $sender = null;
                        if (in_array($type, ['invoice', 'receipt', 'statement', 'report_card'])) {
                            $sender = $smsService->getFinanceSenderId();
                        }
                        $response = $smsService->sendSMS($contact, $body, $sender);
                    } elseif ($channel === 'whatsapp') {
                        $response = $whatsAppService->sendMessage($contact, $body);
                    } else {
                        $this->sendEmailWithOptionalPdf($contact, $subject, $message, $link);
                        $response = ['status' => 'sent'];
                    }

                    CommunicationLog::create([
                        'recipient_type' => 'student',
                        'recipient_id'   => $student->id,
                        'contact'        => $contact,
                        'channel'        => $channel,
                        'title'          => $title,
                        'message'        => $body,
                        'type'           => $type,
                        'status'         => $status,
                        'response'       => $response,
                        'classroom_id'   => $student->classroom_id,
                        'scope'          => 'document_send',
                        'sent_at'        => now(),
                        'provider_id'    => data_get($response, 'id') ?? data_get($response, 'message_id') ?? data_get($response, 'MessageID'),
                        'provider_status'=> data_get($response, 'status'),
                    ]);

                    $sent++;
                } catch (\Throwable $e) {
                    $failed++;
                    CommunicationLog::create([
                        'recipient_type' => 'student',
                        'recipient_id'   => $student->id,
                        'contact'        => $contact,
                        'channel'        => $channel,
                        'title'          => $title,
                        'message'        => $body,
                        'type'           => $type,
                        'status'         => 'failed',
                        'response'       => $e->getMessage(),
                        'classroom_id'   => $student->classroom_id,
                        'scope'          => 'document_send',
                        'sent_at'        => now(),
                    ]);
                }
            }
        }

        $summary = "Sent {$sent}, failed {$failed}";
        return back()->with($failed ? 'warning' : 'success', $summary);
    }

    private function resolveTarget(string $type, int $id): array
    {
        $student = null;
        $link = null;
        $title = ucfirst(str_replace('_', ' ', $type));

        if ($type === 'invoice') {
            $invoice = Invoice::with('student')->find($id);
            if ($invoice && $invoice->student) {
                $student = $invoice->student;
                $link = URL::route('finance.invoices.print_single', $invoice);
                $title = 'Invoice';
            }
        } elseif ($type === 'receipt') {
            $payment = Payment::with('student')->find($id);
            if ($payment && $payment->student) {
                $student = $payment->student;
                $link = URL::route('finance.payments.receipt', $payment);
                $title = 'Receipt';
            }
        } elseif ($type === 'statement') {
            $student = Student::find($id);
            if ($student) {
                $link = URL::route('finance.student-statements.export', [
                    'student' => $student->id,
                    'format'  => 'pdf',
                ]);
                $title = 'Statement';
            }
        } elseif ($type === 'report_card') {
            $rc = ReportCard::with('student')->find($id);
            if ($rc && $rc->student) {
                $student = $rc->student;
                $link = URL::route('academics.report_cards.pdf', $rc);
                $title = 'Report Card';
            }
        }

        return [$student, $link, $title];
    }

    private function sendEmailWithOptionalPdf(string $to, string $subject, string $message, string $link): void
    {
        $tmpPath = null;
        try {
            $response = Http::timeout(20)->get($link);
            if ($response->successful()) {
                $tmpPath = tempnam(sys_get_temp_dir(), 'doc') . '.pdf';
                file_put_contents($tmpPath, $response->body());
            }
        } catch (\Throwable) {
            // fallback to no attachment
        }

        Mail::to($to)->send(new GenericMail($subject, $message . "\n\n" . $link, $tmpPath));

        if ($tmpPath && file_exists($tmpPath)) {
            @unlink($tmpPath);
        }
    }
}

