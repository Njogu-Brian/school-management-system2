<?php

namespace App\Services;

use App\Models\FeePaymentPlan;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationLog;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;

/**
 * Send SMS, WhatsApp, and Email to parent when a payment plan is created.
 */
class PaymentPlanNotificationService
{
    public function __construct(
        protected SMSService $smsService,
        protected WhatsAppService $whatsappService
    ) {}

    public function notifyParentOnPlanCreated(FeePaymentPlan $plan): void
    {
        $plan->load(['student.parent', 'student.classroom', 'invoice']);
        $student = $plan->student;
        $parent = $student->parent ?? null;

        if (! $parent) {
            return;
        }

        $message = $this->buildMessage($plan);
        [$emailSubject, $emailBody] = $this->buildEmailSubjectAndBody($plan);

        // SMS – never send fee-related communications to guardian; guardians are reached via manual number entry only
        $phone = $parent->father_phone ?? $parent->mother_phone ?? null;
        if ($phone) {
            $this->sendSms($phone, $message, $student);
        }

        // WhatsApp (prefer WhatsApp number) – never include guardian
        $waPhone = ! empty($parent->father_whatsapp) ? $parent->father_whatsapp
            : (! empty($parent->mother_whatsapp) ? $parent->mother_whatsapp
            : $phone);
        if ($waPhone) {
            $this->sendWhatsApp($waPhone, $message, $student);
        }

        // Email – never send fee-related communications to guardian
        $email = $parent->father_email ?? $parent->mother_email ?? null;
        if ($email) {
            $this->sendEmail($email, $emailSubject, $emailBody, $student);
        }
    }

    protected function buildMessage(FeePaymentPlan $plan): string
    {
        $extra = $this->planExtra($plan);
        $template = CommunicationTemplate::where('code', 'payment_plan_created_sms')->first();
        if ($template && $template->content) {
            return replace_placeholders($template->content, $plan->student, $extra);
        }
        $student = $plan->student;
        $studentName = $student->full_name ?? trim($student->first_name . ' ' . $student->last_name);
        $installmentAmount = number_format((float) $plan->installment_amount, 2);
        $total = number_format((float) $plan->total_amount, 2);
        $count = $plan->installment_count;
        $start = $plan->start_date->format('d M Y');
        $end = $plan->end_date->format('d M Y');
        $link = url('/payment-plan/' . $plan->hashed_id);
        $schoolName = setting('school_name', config('app.name'));
        return "Dear Parent/Guardian,\n\nA payment plan has been created for {$studentName}.\n\nTotal: KES {$total} in {$count} installments of KES {$installmentAmount}.\nPeriod: {$start} to {$end}.\n\nView your plan: {$link}\n\nRegards,\n{$schoolName}";
    }

    protected function planExtra(FeePaymentPlan $plan): array
    {
        $link = url('/payment-plan/' . $plan->hashed_id);
        return [
            'total_amount' => number_format((float) $plan->total_amount, 2),
            'installment_count' => (string) $plan->installment_count,
            'installment_amount' => number_format((float) $plan->installment_amount, 2),
            'payment_plan_link' => $link,
            'start_date' => $plan->start_date->format('d M Y'),
            'end_date' => $plan->end_date->format('d M Y'),
        ];
    }

    protected function buildEmailSubjectAndBody(FeePaymentPlan $plan): array
    {
        $extra = $this->planExtra($plan);
        $template = CommunicationTemplate::where('code', 'payment_plan_created_email')->first();
        if ($template && $template->content) {
            $subject = str_replace(['{{student_name}}'], [$plan->student->full_name ?? $plan->student->first_name . ' ' . $plan->student->last_name], $template->subject ?? 'Payment plan created');
            return [$subject, replace_placeholders($template->content, $plan->student, $extra)];
        }
        $subject = 'Payment plan created – ' . ($plan->student->full_name ?? $plan->student->first_name . ' ' . $plan->student->last_name);
        return [$subject, $this->buildMessage($plan)];
    }

    protected function sendSms(string $phone, string $message, $student): void
    {
        try {
            $this->smsService->sendSMS($phone, $message, 'finance');
            CommunicationLog::create([
                'recipient_type' => 'student',
                'recipient_id' => $student->id,
                'contact' => $phone,
                'channel' => 'sms',
                'title' => 'Payment plan created',
                'message' => $message,
                'type' => 'sms',
                'status' => 'sent',
                'scope' => 'payment_plan',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Payment plan SMS failed', ['student_id' => $student->id, 'error' => $e->getMessage()]);
        }
    }

    protected function sendWhatsApp(string $phone, string $message, $student): void
    {
        try {
            $this->whatsappService->sendMessage($phone, $message);
            CommunicationLog::create([
                'recipient_type' => 'student',
                'recipient_id' => $student->id,
                'contact' => $phone,
                'channel' => 'whatsapp',
                'title' => 'Payment plan created',
                'message' => $message,
                'type' => 'whatsapp',
                'status' => 'sent',
                'scope' => 'payment_plan',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Payment plan WhatsApp failed', ['student_id' => $student->id, 'error' => $e->getMessage()]);
        }
    }

    protected function sendEmail(string $email, string $subject, string $message, $student): void
    {
        try {
            Mail::to($email)->send(new GenericMail($subject, $message));
            CommunicationLog::create([
                'recipient_type' => 'student',
                'recipient_id' => $student->id,
                'contact' => $email,
                'channel' => 'email',
                'title' => $subject,
                'message' => $message,
                'type' => 'email',
                'status' => 'sent',
                'scope' => 'payment_plan',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Payment plan email failed', ['student_id' => $student->id, 'error' => $e->getMessage()]);
        }
    }
}
