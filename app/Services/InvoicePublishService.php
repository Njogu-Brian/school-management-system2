<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Publishes draft fee invoices to parents (SMS / WhatsApp / in-app).
 * Fee posting creates invoices as drafts; this service makes them visible and notifies.
 */
class InvoicePublishService
{
    public function __construct(
        protected SMSService $smsService,
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * @param  array{
     *   year?: int|null,
     *   term?: int|null,
     *   class_id?: int|null,
     *   stream_id?: int|null,
     *   student_id?: int|null,
     *   student_ids?: array<int>|null,
     *   invoice_ids?: array<int>|null,
     *   include_published?: bool,
     * }  $filters
     */
    public function queryUnpublished(array $filters = []): Builder
    {
        $query = Invoice::query()
            ->with(['student.classroom', 'student.stream', 'student.family', 'academicYear'])
            ->notReversed()
            ->whereHas('student', fn (Builder $s) => $s->where('archive', 0)->where('is_alumni', false));

        if (empty($filters['include_published'])) {
            $query->unpublished();
        }

        if (! empty($filters['invoice_ids']) && is_array($filters['invoice_ids'])) {
            $query->whereIn('id', array_map('intval', $filters['invoice_ids']));
        }

        if (! empty($filters['year'])) {
            $year = (int) $filters['year'];
            $query->where(function (Builder $q) use ($year) {
                $q->where('year', $year)
                    ->orWhereHas('academicYear', fn (Builder $ay) => $ay->where('year', $year));
            });
        }

        if (! empty($filters['term'])) {
            $query->where('term', (int) $filters['term']);
        }

        if (! empty($filters['student_id'])) {
            $query->where('student_id', (int) $filters['student_id']);
        }

        if (! empty($filters['student_ids']) && is_array($filters['student_ids'])) {
            $query->whereIn('student_id', array_map('intval', $filters['student_ids']));
        }

        if (! empty($filters['class_id'])) {
            $classId = (int) $filters['class_id'];
            $query->whereHas('student', fn (Builder $s) => $s->where('classroom_id', $classId));
        }

        if (! empty($filters['stream_id'])) {
            $streamId = (int) $filters['stream_id'];
            $query->whereHas('student', fn (Builder $s) => $s->where('stream_id', $streamId));
        }

        return $query->orderBy('year')->orderBy('term')->orderBy('id');
    }

    /**
     * @param  array<string>  $channels  sms|whatsapp (in-app always sent on publish)
     * @return array{published:int, notified:int, failed:int, skipped:int}
     */
    public function publishByFilters(array $filters, array $channels = ['sms', 'whatsapp'], bool $notify = true, bool $dryRun = false): array
    {
        $invoices = $this->queryUnpublished($filters)->get();

        return $this->publishMany($invoices, $channels, $notify, $dryRun);
    }

    /**
     * @param  Collection<int, Invoice>|array<int, Invoice>  $invoices
     * @param  array<string>  $channels
     * @return array{published:int, notified:int, failed:int, skipped:int}
     */
    public function publishMany($invoices, array $channels = ['sms', 'whatsapp'], bool $notify = true, bool $dryRun = false): array
    {
        $invoices = collect($invoices);
        $channels = array_values(array_unique(array_filter($channels)));
        $published = 0;
        $skipped = 0;
        $notified = 0;
        $failed = 0;

        $toNotify = collect();

        foreach ($invoices as $invoice) {
            if (! $invoice instanceof Invoice) {
                continue;
            }

            if ($invoice->isReversed()) {
                $skipped++;
                continue;
            }

            if ($invoice->isPublished()) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $published++;
                $toNotify->push($invoice);
                continue;
            }

            $this->publishOne($invoice, false);
            $published++;
            $toNotify->push($invoice->fresh(['student.family', 'academicYear']) ?? $invoice);
        }

        if ($notify && $toNotify->isNotEmpty()) {
            foreach ($toNotify as $invoice) {
                try {
                    if (! $dryRun) {
                        app(ParentAppNotifyService::class)->notifyInvoiceGenerated($invoice);
                    }

                    if ($channels !== []) {
                        $ok = $this->notifyParentsViaChannels($invoice, $channels, $dryRun);
                        if ($ok) {
                            $notified++;
                        } else {
                            $failed++;
                        }
                    } else {
                        $notified++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Invoice publish notify failed: '.$e->getMessage(), [
                        'invoice_id' => $invoice->id,
                    ]);
                }
            }
        }

        return [
            'published' => $published,
            'notified' => $notified,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    public function publishOne(Invoice $invoice, bool $notify = true): Invoice
    {
        if (! $invoice->isPublished()) {
            $invoice->published_at = now();
            $invoice->published_by = Auth::id();
            $invoice->save();
        }

        if ($notify) {
            try {
                app(ParentAppNotifyService::class)->notifyInvoiceGenerated($invoice->fresh(['student']) ?? $invoice);
            } catch (\Throwable $e) {
                Log::warning('Invoice publish in-app notify failed: '.$e->getMessage(), [
                    'invoice_id' => $invoice->id,
                ]);
            }

            $this->notifyParentsViaChannels($invoice, ['sms', 'whatsapp']);
        }

        return $invoice->fresh() ?? $invoice;
    }

    /**
     * @param  array<string>  $channels
     */
    protected function notifyParentsViaChannels(Invoice $invoice, array $channels, bool $dryRun = false): bool
    {
        $invoice->loadMissing('student', 'academicYear');
        $student = $invoice->student;
        if (! $student) {
            return false;
        }

        if (empty($invoice->hashed_id)) {
            $invoice->hashed_id = Invoice::generateHashedId();
            if (! $dryRun) {
                $invoice->save();
            }
        }

        $invoiceUrl = URL::route('invoices.public', $invoice->hashed_id);
        $schoolName = setting('school_name', config('app.name', 'School'));
        $studentName = $student->full_name ?? trim(($student->first_name ?? '').' '.($student->last_name ?? ''));
        $invoiceNumber = $invoice->invoice_number ?: ('#'.$invoice->id);
        $termLabel = $invoice->termDisplayLabel() ?: ('Term '.($invoice->term ?? ''));
        $yearLabel = (string) ($invoice->academicYear->year ?? $invoice->year ?? '');
        $amount = number_format((float) ($invoice->balance ?? $invoice->total ?? 0), 2);

        $anySent = false;

        foreach ($channels as $channel) {
            if (! in_array($channel, ['sms', 'whatsapp'], true)) {
                continue;
            }

            $recipients = CommunicationHelperService::collectRecipients([
                'target' => 'student',
                'student_id' => $student->id,
            ], $channel);

            if (empty($recipients)) {
                continue;
            }

            $template = $this->resolveTemplateForChannel($channel);

            foreach (CommunicationHelperService::expandRecipientsToPairs($recipients) as $pair) {
                [$contact, $entity, $parentMeta] = array_pad($pair, 3, null);

                if ($template) {
                    $vars = [
                        'parent_name' => $parentMeta['name'] ?? 'Parent',
                        'student_name' => $studentName,
                        'invoice_number' => $invoiceNumber,
                        'invoice_link' => $invoiceUrl,
                        'finance_portal_link' => $invoiceUrl,
                        'outstanding_amount' => $amount,
                        'total_amount' => number_format((float) ($invoice->total ?? 0), 2),
                        'term_name' => $termLabel,
                        'academic_year' => $yearLabel,
                        'school_name' => $schoolName,
                    ];
                    $body = $template->content;
                    foreach ($vars as $key => $value) {
                        $body = str_replace('{{'.$key.'}}', (string) $value, $body);
                    }
                } else {
                    $body = "Dear Parent,\n\nA new fee invoice ({$invoiceNumber}) for {$studentName} ({$termLabel}) has been published.\nAmount due: KES {$amount}\nView invoice: {$invoiceUrl}\n\n{$schoolName}";
                }

                $body = personalize_message_for_parent_recipient(
                    $body,
                    $entity instanceof Student ? $entity : $student,
                    $parentMeta
                );
                if ($body === null) {
                    continue;
                }

                // Ensure the invoice link is present for SMS/WhatsApp even if the template omitted it.
                if (! str_contains($body, $invoiceUrl)) {
                    $body = rtrim($body)."\n\n".$invoiceUrl;
                }

                if ($dryRun) {
                    $anySent = true;
                    continue;
                }

                try {
                    if ($channel === 'sms') {
                        $this->smsService->sendSMS($contact, $body, $this->smsService->getFinanceSenderId());
                    } else {
                        $this->whatsAppService->sendMessage($contact, $body);
                    }

                    CommunicationLog::create([
                        'recipient_type' => 'student',
                        'recipient_id' => $student->id,
                        'contact' => $contact,
                        'channel' => $channel,
                        'title' => 'Invoice Published',
                        'message' => $body,
                        'type' => 'invoice',
                        'status' => 'sent',
                        'classroom_id' => $student->classroom_id,
                        'scope' => 'invoice_publish',
                        'sent_at' => now(),
                    ]);

                    $anySent = true;
                } catch (\Throwable $e) {
                    CommunicationLog::create([
                        'recipient_type' => 'student',
                        'recipient_id' => $student->id,
                        'contact' => $contact,
                        'channel' => $channel,
                        'title' => 'Invoice Published',
                        'message' => $body,
                        'type' => 'invoice',
                        'status' => 'failed',
                        'response' => $e->getMessage(),
                        'classroom_id' => $student->classroom_id,
                        'scope' => 'invoice_publish',
                        'sent_at' => now(),
                    ]);
                }
            }
        }

        return $anySent;
    }

    public function resolveTemplateForChannel(string $channel): ?CommunicationTemplate
    {
        $this->ensureDefaultTemplates();

        $codes = match ($channel) {
            'whatsapp' => ['finance_invoice_published_whatsapp', 'finance_invoice_published_sms', 'finance_share_link_sms'],
            default => ['finance_invoice_published_sms', 'finance_share_link_sms'],
        };

        foreach ($codes as $code) {
            $template = CommunicationTemplate::where('code', $code)->first();
            if ($template) {
                return $template;
            }
        }

        return null;
    }

    protected function ensureDefaultTemplates(): void
    {
        CommunicationTemplate::firstOrCreate(
            ['code' => 'finance_invoice_published_sms'],
            [
                'title' => 'Invoice Published (SMS)',
                'type' => 'sms',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nA new fee invoice ({{invoice_number}}) for {{student_name}} ({{term_name}}) has been published.\nAmount due: KES {{outstanding_amount}}\nView: {{invoice_link}}\n\n{{school_name}}",
            ]
        );

        CommunicationTemplate::firstOrCreate(
            ['code' => 'finance_invoice_published_whatsapp'],
            [
                'title' => 'Invoice Published (WhatsApp)',
                'type' => 'whatsapp',
                'subject' => null,
                'content' => "Dear {{parent_name}},\n\nA new fee invoice ({{invoice_number}}) for {{student_name}} ({{term_name}}) has been published.\nAmount due: KES {{outstanding_amount}}\nView invoice: {{invoice_link}}\n\n{{school_name}}",
            ]
        );
    }
}
