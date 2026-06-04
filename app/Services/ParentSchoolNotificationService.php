<?php

namespace App\Services;

use App\Models\ParentInfo;
use App\Models\Student;
use Illuminate\Support\Facades\Log;

/**
 * Central outbound school notifications to father/mother only.
 * Respects school_notifications_muted_parent, per-slot names, and skips unnamed slots.
 */
class ParentSchoolNotificationService
{
    public function __construct(
        protected CommunicationService $communicationService,
        protected SMSService $smsService,
    ) {}

    /**
     * @return list<array{slot:string, name:?string, phone:string}>
     */
    public function smsRecipients(ParentInfo $parent, bool $requireName = false): array
    {
        return $this->filterNamedRecipients($parent->schoolNotificationSmsRecipients(), $requireName);
    }

    /**
     * @return list<array{slot:string, name:?string, email:string}>
     */
    public function emailRecipients(ParentInfo $parent, bool $requireName = false): array
    {
        return $this->filterNamedRecipients($parent->schoolNotificationEmailRecipients(), $requireName);
    }

    /**
     * @return list<array{slot:string, name:?string, phone:string}>
     */
    public function whatsappRecipients(ParentInfo $parent, bool $requireName = false): array
    {
        return $this->filterNamedRecipients($parent->schoolNotificationWhatsAppRecipients(), $requireName);
    }

    /**
     * @param  list<array<string, mixed>>  $recipients
     * @return list<array<string, mixed>>
     */
    protected function filterNamedRecipients(array $recipients, bool $requireName): array
    {
        if (! $requireName) {
            return $recipients;
        }

        return array_values(array_filter($recipients, function (array $r) {
            return trim((string) ($r['name'] ?? '')) !== '';
        }));
    }

    /**
     * Send the same message template to each reachable parent (SMS), personalized per slot.
     */
    public function sendSmsTemplateToStudentParents(
        Student $student,
        string $messageTemplate,
        ?string $title = null,
        ?string $senderId = null,
        ?int $paymentId = null,
        array $extraPlaceholders = [],
    ): int {
        $parent = $student->parent;
        if (! $parent) {
            return 0;
        }

        $sent = 0;
        foreach ($this->smsRecipients($parent) as $r) {
            $phone = $r['phone'] ?? null;
            if (! $phone) {
                continue;
            }
            $body = $this->personalize($messageTemplate, $student, $r, $extraPlaceholders);
            if ($body === null) {
                continue;
            }
            try {
                $this->communicationService->sendSMS(
                    'parent',
                    $parent->id,
                    $phone,
                    $body,
                    $title,
                    $senderId,
                    $paymentId
                );
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Parent SMS failed', [
                    'parent_id' => $parent->id,
                    'student_id' => $student->id,
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    public function sendEmailTemplateToStudentParents(
        Student $student,
        string $subjectTemplate,
        string $bodyTemplate,
        ?string $attachmentPath = null,
        array $extraPlaceholders = [],
    ): int {
        $parent = $student->parent;
        if (! $parent) {
            return 0;
        }

        $sent = 0;
        foreach ($this->emailRecipients($parent) as $r) {
            $email = $r['email'] ?? null;
            if (! $email) {
                continue;
            }
            $meta = ['name' => $r['name'], 'slot' => $r['slot']];
            $subject = $this->personalize($subjectTemplate, $student, $meta, $extraPlaceholders);
            $body = $this->personalize($bodyTemplate, $student, $meta, $extraPlaceholders);
            if ($subject === null || $body === null) {
                continue;
            }
            try {
                $this->communicationService->sendEmail('parent', $parent->id, $email, $subject, $body, $attachmentPath);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Parent email failed', [
                    'parent_id' => $parent->id,
                    'student_id' => $student->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    public function sendWhatsAppTemplateToStudentParents(
        Student $student,
        string $messageTemplate,
        ?string $title = null,
        ?int $paymentId = null,
        array $extraPlaceholders = [],
    ): int {
        $parent = $student->parent;
        if (! $parent) {
            return 0;
        }

        $sent = 0;
        foreach ($this->whatsappRecipients($parent) as $r) {
            $phone = $r['phone'] ?? null;
            if (! $phone) {
                continue;
            }
            $body = $this->personalize($messageTemplate, $student, $r, $extraPlaceholders);
            if ($body === null) {
                continue;
            }
            try {
                $this->communicationService->sendWhatsApp('parent', $parent->id, $phone, $body, $title, $paymentId);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Parent WhatsApp failed', [
                    'parent_id' => $parent->id,
                    'student_id' => $student->id,
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /**
     * Direct SMS via SMSService (for modules that log separately). Still enforces mute + named slots.
     */
    public function sendSmsRawToStudentParents(
        Student $student,
        string $messageTemplate,
        ?string $senderId = null,
        array $extraPlaceholders = [],
    ): int {
        $parent = $student->parent;
        if (! $parent) {
            return 0;
        }

        $sent = 0;
        foreach ($this->smsRecipients($parent) as $r) {
            $phone = $r['phone'] ?? null;
            if (! $phone) {
                continue;
            }
            $body = $this->personalize($messageTemplate, $student, $r, $extraPlaceholders);
            if ($body === null) {
                continue;
            }
            if (! $parent->contactAllowedForSchoolNotification('sms', $phone)) {
                continue;
            }
            try {
                $this->smsService->sendSMS($phone, $body, $senderId);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Parent raw SMS failed', [
                    'parent_id' => $parent->id,
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /**
     * @param  array{slot?:string, name?:?string}  $recipientMeta
     */
    protected function personalize(
        string $template,
        Student $student,
        array $recipientMeta,
        array $extraPlaceholders = [],
    ): ?string {
        $name = trim((string) ($recipientMeta['name'] ?? ''));
        if ($name === '') {
            $name = 'Parent';
        }

        $parent = $student->parent;
        $slot = $recipientMeta['slot'] ?? null;
        $extra = array_merge(
            parent_recipient_placeholder_extra($name, $parent, $slot),
            $extraPlaceholders
        );

        return replace_placeholders($template, $student, $extra);
    }
}
