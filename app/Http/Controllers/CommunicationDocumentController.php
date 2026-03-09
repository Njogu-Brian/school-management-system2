<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\GenericMail;
use App\Models\Finance\Invoice;
use App\Models\Family;
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
use App\Models\CommunicationTemplate;

class CommunicationDocumentController extends Controller
{
    /**
     * Quick-send documents (invoice/receipt/statement/report card) via SMS/Email/WhatsApp.
     * Email will try to attach a fetched PDF; SMS/WhatsApp send links.
     */
    public function send(Request $request, SMSService $smsService, WhatsAppService $whatsAppService)
    {
        $data = $request->validate([
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(['sms', 'email', 'whatsapp'])],
            'type'    => ['required', Rule::in(['invoice', 'receipt', 'statement', 'family_statement', 'report_card'])],
            'ids'     => ['required', 'array', 'min:1'],
            'ids.*'   => ['integer'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'year' => ['nullable', 'integer'],
            'term' => ['nullable', 'string', 'max:50'],
        ]);

        $channels = array_unique($data['channels']);
        $type     = $data['type'];
        $ids      = array_unique($data['ids']);
        $subject  = $data['subject'] ?? 'School Update';
        $message  = $data['message'];
        $year     = !empty($data['year']) ? (int) $data['year'] : null;
        $term     = ($data['term'] ?? '') !== '' ? $data['term'] : null;

        $sent = 0;
        $failed = 0;

        foreach ($ids as $id) {
            [$student, $viewLink, $attachmentLink, $title] = $this->resolveTarget($type, $id, $year, $term);
            if (!$student || !$viewLink) {
                $failed++;
                continue;
            }

            // Get payment_id for receipts to link communication logs
            $paymentId = null;
            if ($type === 'receipt') {
                $payment = Payment::find($id);
                $paymentId = $payment?->id;
            }

            // Send via each selected channel
            foreach ($channels as $channel) {
                $recipients = CommunicationHelperService::collectRecipients([
                    'target'     => 'student',
                    'student_id' => $student->id,
                ], $channel === 'whatsapp' ? 'whatsapp' : $channel);

                if (empty($recipients)) {
                    $failed++;
                    continue;
                }

                // Use templates from CommunicationTemplateSeeder for finance documents
                $template = null;
                if (in_array($type, ['invoice', 'receipt', 'statement'])) {
                    if ($channel === 'sms') {
                        $template = CommunicationTemplate::where('code', 'finance_share_link_sms')->first();
                    } elseif ($channel === 'email') {
                        $template = CommunicationTemplate::where('code', 'finance_share_link_email')->first();
                    }
                } elseif ($type === 'report_card') {
                    if ($channel === 'sms') {
                        $template = CommunicationTemplate::where('code', 'academics_report_sms')->first();
                    } elseif ($channel === 'email') {
                        $template = CommunicationTemplate::where('code', 'academics_report_email')->first();
                    }
                }
                
                // Fallback: create templates if seeder hasn't run yet
                if (!$template && in_array($type, ['invoice', 'receipt', 'statement']) && $channel === 'sms') {
                    $template = CommunicationTemplate::firstOrCreate(
                        ['code' => 'finance_share_link_sms'],
                        [
                            'title' => 'Share Finance Link (SMS)',
                            'type' => 'sms',
                            'subject' => null,
                            'content' => "Dear {{parent_name}},\n\nYou can view and download {{student_name}}'s invoices, receipts, and fee statement using the link below:\n\n{{finance_portal_link}}\n\nThank you,\n{{school_name}}",
                        ]
                    );
                }
                
                if (!$template && in_array($type, ['invoice', 'receipt', 'statement']) && $channel === 'email') {
                    $template = CommunicationTemplate::firstOrCreate(
                        ['code' => 'finance_share_link_email'],
                        [
                            'title' => 'Share Finance Link (Email)',
                            'type' => 'email',
                            'subject' => 'Financial Document – {{student_name}}',
                            'content' => "Dear {{parent_name}},\n\nPlease find the requested financial document for {{student_name}} attached.\nYou can also access all financial records anytime via:\n{{finance_portal_link}}\n\nWe are always happy to assist.\n\nWarm regards,\n{{school_name}} Finance Team",
                        ]
                    );
                }
                
                // Prepare base variables for template
                $schoolName = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');
                $studentName = $student->full_name ?? $student->first_name . ' ' . $student->last_name;
                $useTemplate = $template !== null;

                foreach (CommunicationHelperService::expandRecipientsToPairs($recipients) as [$contact, $entity]) {
                    // Prepare personalized body for each recipient
                    if ($useTemplate) {
                        $parentName = (is_object($entity) && $entity->parent)
                            ? ($entity->parent->father_name ?? $entity->parent->mother_name ?? 'Parent')
                            : 'Parent';
                        
                        $variables = [
                            'parent_name' => $parentName,
                            'student_name' => $studentName,
                            'finance_portal_link' => $viewLink,
                            'school_name' => $schoolName,
                        ];
                        
                        // Replace placeholders in template content (create a copy to avoid modifying original)
                        $templateContent = $template->content;
                        $templateSubject = $template->subject ?? $subject;
                        foreach ($variables as $key => $value) {
                            $templateContent = str_replace('{{' . $key . '}}', $value, $templateContent);
                            $templateSubject = str_replace('{{' . $key . '}}', $value, $templateSubject);
                        }
                        
                        $body = $templateContent . "\n\n" . $viewLink;
                        $finalSubject = $channel === 'email' ? $templateSubject : $subject;
                    } else {
                        $body = trim($message . "\n\n" . $viewLink);
                        $finalSubject = $subject;
                    }
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
                            $this->sendEmailWithOptionalPdf($contact, $finalSubject, $body, $attachmentLink ?: $viewLink);
                            $response = ['status' => 'sent'];
                        }

                        CommunicationLog::create([
                            'recipient_type' => 'student',
                            'recipient_id'   => $student->id,
                            'payment_id'     => $paymentId, // Link to payment for receipts
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
                            'payment_id'     => $paymentId, // Link to payment for receipts
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
        }

        $summary = "Sent {$sent}, failed {$failed}";
        return back()->with($failed ? 'warning' : 'success', $summary);
    }

    private function resolveTarget(string $type, int $id, ?int $year = null, ?string $term = null): array
    {
        $student = null;
        $viewLink = null;
        $attachmentLink = null;
        $title = ucfirst(str_replace('_', ' ', $type));

        if ($type === 'invoice') {
            $invoice = Invoice::with('student')->find($id);
            if ($invoice && $invoice->student) {
                $student = $invoice->student;
                $viewLink = URL::route('invoices.public', $invoice->hashed_id);
                $attachmentLink = $viewLink;
                $title = 'Invoice';
            }
        } elseif ($type === 'receipt') {
            $payment = Payment::with('student')->find($id);
            if ($payment && $payment->student) {
                $student = $payment->student;
                $viewLink = URL::route('receipts.public', $payment->public_token);
                $attachmentLink = $viewLink;
                $title = 'Receipt';
            }
        } elseif ($type === 'statement') {
            $student = Student::find($id);
            if ($student) {
                $statementYear = $year ?: $this->resolveStatementYearForStudent($student);
                $statementLink = \App\Http\Controllers\Finance\StudentStatementController::getOrCreateStatementLink(
                    'student',
                    $student->id,
                    $statementYear,
                    $term,
                    auth()->id()
                );
                $viewLink = $statementLink->getUrl();
                $attachmentLink = $statementLink->getUrl(['format' => 'pdf']);
                $title = 'Statement';
            }
        } elseif ($type === 'family_statement') {
            $family = Family::with(['students' => function ($q) {
                $q->where('archive', 0)->where('is_alumni', false);
            }])->find($id);
            $student = $family?->students->first();
            if ($family && $student) {
                $statementYear = $year ?: $this->resolveStatementYearForFamily($family);
                $statementLink = \App\Http\Controllers\Finance\StudentStatementController::getOrCreateStatementLink(
                    'family',
                    $family->id,
                    $statementYear,
                    $term,
                    auth()->id()
                );
                $viewLink = $statementLink->getUrl();
                $attachmentLink = $statementLink->getUrl(['format' => 'pdf']);
                $title = 'Family Statement';
            }
        } elseif ($type === 'report_card') {
            $rc = ReportCard::with('student')->find($id);
            if ($rc && $rc->student) {
                $student = $rc->student;
                $viewLink = URL::route('academics.report_cards.pdf', $rc);
                $attachmentLink = $viewLink;
                $title = 'Report Card';
            }
        }

        return [$student, $viewLink, $attachmentLink, $title];
    }

    private function sendEmailWithOptionalPdf(string $to, string $subject, string $message, string $attachmentUrl): void
    {
        $tmpPath = null;
        try {
            $response = Http::timeout(20)->get($attachmentUrl);
            if ($response->successful()) {
                $tmpPath = tempnam(sys_get_temp_dir(), 'doc') . '.pdf';
                $contentType = strtolower((string) $response->header('Content-Type'));
                if (str_contains($contentType, 'text/html')) {
                    file_put_contents($tmpPath, Pdf::loadHTML($response->body())->output());
                } else {
                    file_put_contents($tmpPath, $response->body());
                }
            }
        } catch (\Throwable) {
            // fallback to no attachment
        }

        Mail::to($to)->send(new GenericMail($subject, $message, $tmpPath));

        if ($tmpPath && file_exists($tmpPath)) {
            @unlink($tmpPath);
        }
    }

    private function resolveStatementYearForStudent(Student $student): int
    {
        $invoiceYears = \App\Models\Invoice::where('student_id', $student->id)
            ->with('academicYear:id,year')
            ->get(['id', 'year', 'academic_year_id'])
            ->map(fn ($invoice) => $invoice->year ?: optional($invoice->academicYear)->year);

        $legacyYears = \App\Models\LegacyStatementTerm::where('student_id', $student->id)
            ->pluck('academic_year');

        return (int) ($invoiceYears->merge($legacyYears)->filter()->max() ?: now()->year);
    }

    private function resolveStatementYearForFamily(Family $family): int
    {
        $studentIds = $family->students->pluck('id')->all();
        if (empty($studentIds)) {
            return now()->year;
        }

        $invoiceYears = \App\Models\Invoice::whereIn('student_id', $studentIds)
            ->with('academicYear:id,year')
            ->get(['id', 'year', 'academic_year_id'])
            ->map(fn ($invoice) => $invoice->year ?: optional($invoice->academicYear)->year);

        $legacyYears = \App\Models\LegacyStatementTerm::whereIn('student_id', $studentIds)
            ->pluck('academic_year');

        return (int) ($invoiceYears->merge($legacyYears)->filter()->max() ?: now()->year);
    }
}

