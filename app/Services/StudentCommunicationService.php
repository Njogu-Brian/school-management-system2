<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Models\FeeReminder;
use App\Models\ScheduledFeeCommunication;
use App\Models\Student;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentCommunicationService
{
    /**
     * Successfully sent (or delivered) communications for a student.
     */
    public function sentHistoryForStudent(Student $student, int $limit = 50): Collection
    {
        $studentId = (int) $student->id;

        return CommunicationLog::query()
            ->with('payment:id,student_id,receipt_number')
            ->where(function ($q) use ($studentId) {
                $q->where(function ($q2) use ($studentId) {
                    $q2->where('recipient_id', $studentId)
                        ->whereIn('recipient_type', ['student', 'parent', 'class', 'all', 'one_parent', 'specific_students']);
                })->orWhereHas('payment', fn ($p) => $p->where('student_id', $studentId));
            })
            ->whereIn('status', ['sent', 'delivered', 'success'])
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Pending / paused outbound items targeting this student.
     */
    public function upcomingForStudent(Student $student): array
    {
        $studentId = (int) $student->id;

        $student->loadMissing('parent', 'classroom', 'stream');

        $feeReminders = FeeReminder::query()
            ->with(['paymentPlan', 'paymentPlanInstallment', 'term.academicYear'])
            ->where('student_id', $studentId)
            ->whereIn('status', ['pending', 'paused'])
            ->orderBy('due_date')
            ->get()
            ->each(function (FeeReminder $reminder) use ($student) {
                $reminder->setAttribute('preview_message', $this->previewFeeReminderMessage($reminder, $student));
            });

        $scheduledFee = ScheduledFeeCommunication::query()
            ->with('template:id,title,code,content,subject')
            ->whereIn('status', ['pending', 'active', 'paused'])
            ->where(function ($q) use ($studentId, $student) {
                $q->where('student_id', $studentId)
                    ->orWhere('target', 'all')
                    ->orWhere(function ($q2) use ($studentId) {
                        $q2->where('target', 'specific_students')
                            ->whereJsonContains('selected_student_ids', $studentId);
                    });
                if ($student->classroom_id) {
                    $q->orWhere(function ($q3) use ($student) {
                        $q3->where('target', 'class')
                            ->whereJsonContains('classroom_ids', (int) $student->classroom_id);
                    });
                }
            })
            ->orderByRaw('COALESCE(recurrence_next_at, send_at) ASC')
            ->limit(30)
            ->get()
            ->filter(function (ScheduledFeeCommunication $item) use ($student) {
                if ($item->target === 'all' || $item->target === 'class') {
                    return true;
                }
                if ($item->target === 'specific_students') {
                    return in_array($student->id, $item->selected_student_ids ?? [], true);
                }
                if ($item->target === 'one_parent') {
                    return (int) $item->student_id === (int) $student->id;
                }

                return true;
            })
            ->each(function (ScheduledFeeCommunication $item) use ($student) {
                $item->setAttribute('preview_message', $this->previewScheduledFeeMessage($item, $student));
            })
            ->values();

        return [
            'fee_reminders' => $feeReminders,
            'scheduled_fee_communications' => $scheduledFee,
            'communications_paused' => CommunicationPauseService::isPaused(),
            'pause_meta' => CommunicationPauseService::getMeta(),
        ];
    }

    public function previewFeeReminderMessage(FeeReminder $reminder, Student $student): string
    {
        $raw = trim((string) ($reminder->message ?? ''));
        if ($raw !== '') {
            return $this->applyReminderPlaceholders($raw, $reminder, $student);
        }

        $channel = $this->primaryChannelForReminder($reminder);
        $template = $this->resolveReminderTemplate($reminder, $channel);
        if ($template && trim((string) $template->content) !== '') {
            return replace_placeholders($template->content, $student, $this->reminderExtraPlaceholders($reminder, $student));
        }

        return '(No message preview available)';
    }

    public function previewScheduledFeeMessage(ScheduledFeeCommunication $item, Student $student): string
    {
        $raw = trim((string) ($item->custom_message ?? ''));
        if ($raw === '' && $item->template) {
            $raw = trim((string) ($item->template->content ?? ''));
        }
        if ($raw === '') {
            return '(No message body — check template in Finance → Fee reminders schedule)';
        }

        return replace_placeholders($raw, $student);
    }

    protected function primaryChannelForReminder(FeeReminder $reminder): string
    {
        if (is_array($reminder->channels) && count($reminder->channels) > 0) {
            return strtolower((string) $reminder->channels[0]);
        }

        return match (strtolower((string) ($reminder->channel ?? 'sms'))) {
            'email' => 'email',
            'whatsapp' => 'whatsapp',
            'both' => 'sms',
            default => 'sms',
        };
    }

    protected function resolveReminderTemplate(FeeReminder $reminder, string $channelType): ?CommunicationTemplate
    {
        $reason = preg_replace('/[^a-z0-9_]/', '', str_replace('-', '_', (string) ($reminder->reason_code ?: 'pending')));

        if (($reminder->fee_reminder_type ?? 'invoice') === 'clearance') {
            $specific = CommunicationTemplate::where('code', "fee_clearance_reminder_{$reason}_{$channelType}")->first();
            if ($specific) {
                return $specific;
            }
            $fallback = CommunicationTemplate::where('code', "fee_clearance_reminder_pending_{$channelType}")->first();
            if ($fallback) {
                return $fallback;
            }
        }

        $code = match ($channelType) {
            'sms' => 'finance_fee_reminder_sms',
            'email' => 'finance_fee_plan_email',
            'whatsapp' => 'finance_fee_reminder_whatsapp',
            default => null,
        };

        return $code ? CommunicationTemplate::where('code', $code)->first() : null;
    }

    /**
     * @return array<string, string>
     */
    protected function reminderExtraPlaceholders(FeeReminder $reminder, Student $student): array
    {
        $parent = $student->parent;
        $parentName = $parent
            ? ($parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? 'Parent')
            : 'Parent';
        $currentTerm = Term::where('is_current', true)->first();
        $currentYear = \App\Models\AcademicYear::where('is_active', true)->first();

        $extra = [
            'parent_name' => $parentName,
            'term_name' => $currentTerm->name ?? 'Current Term',
            'academic_year' => (string) ($currentYear->year ?? date('Y')),
            'finance_portal_link' => get_public_student_statement_url($student),
            'outstanding_amount' => number_format((float) $reminder->outstanding_amount, 2),
        ];

        if ($student->family_id && class_exists(\App\Models\PaymentLink::class)) {
            try {
                $familyPayLink = \App\Models\PaymentLink::getOrCreateFamilyLink(
                    (int) $student->family_id,
                    auth()->id(),
                    'fee_reminder'
                );
                $extra['pay_link'] = $familyPayLink?->getPaymentUrl() ?? '';
            } catch (\Throwable $e) {
                $extra['pay_link'] = '';
            }
        }

        if ($reminder->payment_plan_installment_id && $reminder->paymentPlanInstallment && $reminder->paymentPlan) {
            $installment = $reminder->paymentPlanInstallment;
            $plan = $reminder->paymentPlan;
            $remainingBalance = $plan->total_amount - $plan->installments()->sum('paid_amount');
            $extra['installment_amount'] = number_format($installment->amount, 2);
            $extra['installment_number'] = (string) $installment->installment_number;
            $extra['due_date'] = $installment->due_date->format('F d, Y');
            $extra['remaining_balance'] = number_format($remainingBalance, 2);
            $extra['payment_plan_link'] = url('/payment-plan/' . $plan->hashed_id);
        }

        if (($reminder->fee_reminder_type ?? '') === 'clearance' && $reminder->term_id && $reminder->term) {
            $extra['term_name'] = $reminder->term->name;
            if ($reminder->term->academicYear) {
                $extra['academic_year'] = (string) $reminder->term->academicYear->year;
            }
            $extra['fee_clearance_deadline'] = Carbon::parse($reminder->due_date)->format('d M Y');
            $extra['fee_clearance_reason'] = str_replace('_', ' ', (string) ($reminder->reason_code ?? ''));
        }

        return $extra;
    }

    protected function applyReminderPlaceholders(string $text, FeeReminder $reminder, Student $student): string
    {
        $extra = $this->reminderExtraPlaceholders($reminder, $student);
        foreach ($extra as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }

        return replace_placeholders($text, $student, $extra);
    }
}
