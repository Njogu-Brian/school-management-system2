<?php

namespace App\Jobs;

use App\Models\ScheduledFeeCommunication;
use App\Models\CommunicationTemplate;
use App\Services\CommunicationHelperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\GenericMail;

class ProcessScheduledFeeCommunicationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function handle(): void
    {
        $pending = ScheduledFeeCommunication::pending()
            ->due()
            ->get();

        foreach ($pending as $item) {
            $lock = Cache::lock("scheduled_fee_comm:{$item->id}", 15 * 60);
            if (!$lock->get()) {
                Log::info('ProcessScheduledFeeCommunicationsJob: skipping item (lock held)', ['item_id' => $item->id]);
                continue;
            }

            try {
                // Re-check due/pending inside the lock to prevent double-send under concurrency.
                $fresh = ScheduledFeeCommunication::query()
                    ->whereKey($item->id)
                    ->pending()
                    ->due()
                    ->first();

                if (!$fresh) {
                    continue;
                }

                $this->processItem($fresh);
            } catch (\Throwable $e) {
                Log::error('ProcessScheduledFeeCommunicationsJob failed for item ' . $item->id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            } finally {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }
    }

    protected function processItem(ScheduledFeeCommunication $item): void
    {
        $message = $this->getMessage($item);
        $subject = $this->getSubject($item);
        $recipientData = $this->buildRecipientData($item);

        $channels = $item->channels ?? ['email', 'sms', 'whatsapp'];
        $channels = is_array($channels) ? $channels : (array) $channels;

        $totalSent = 0;
        foreach ($channels as $channel) {
            $recipients = $recipientData[$channel] ?? [];
            if (empty($recipients)) {
                continue;
            }

            $pairs = CommunicationHelperService::expandRecipientsToPairs($recipients);

            if (empty($pairs)) {
                Log::info('ProcessScheduledFeeCommunicationsJob: no recipients for channel', ['item_id' => $item->id, 'channel' => $channel, 'target' => $item->target]);
                continue;
            }

            if (count($pairs) > 10) {
                $trackingId = 'scheduled_fee_' . $item->id . '_' . $channel . '_' . Str::uuid()->toString();
                $title = $subject;

                $recipientsData = [];
                foreach ($pairs as [$contact, $entity]) {
                    $entityData = $this->entityToArray($entity);
                    if ($channel === 'email') {
                        $recipientsData[] = ['email' => $contact, 'entity' => $entityData];
                    } else {
                        $normalized = $this->normalizeKenyanPhone($contact);
                        if ($normalized) {
                            $recipientsData[] = ['phone' => $normalized, 'entity' => $entityData];
                        } else {
                            Log::warning('ProcessScheduledFeeCommunicationsJob: skipped invalid phone', ['contact' => $contact, 'channel' => $channel]);
                        }
                    }
                }

                if (!empty($recipientsData)) {
                    $totalSent += count($recipientsData);
                    if ($channel === 'email') {
                        BulkSendEmail::dispatch($trackingId, $recipientsData, $message, $title, $item->target, null, $item->created_by);
                    } elseif ($channel === 'whatsapp') {
                        BulkSendWhatsAppMessages::dispatch($trackingId, $recipientsData, $message, $title, $item->target, null, true, $item->created_by);
                    } else {
                        BulkSendSMS::dispatch($trackingId, $recipientsData, $message, $title, $item->target, 'finance', $item->created_by);
                    }
                    Log::info('ProcessScheduledFeeCommunicationsJob: bulk job dispatched', ['item_id' => $item->id, 'channel' => $channel, 'count' => count($recipientsData)]);
                }
            } else {
                foreach ($pairs as [$contact, $entity]) {
                    try {
                        $personalized = replace_placeholders($message, $entity);
                        if ($channel === 'email') {
                            Mail::to($contact)->send(new GenericMail($subject, $personalized));
                        } elseif ($channel === 'whatsapp') {
                            app(\App\Services\WhatsAppService::class)->sendMessage($contact, $personalized);
                        } else {
                            app(\App\Services\SMSService::class)->sendSMS($contact, $personalized, app(\App\Services\SMSService::class)->getFinanceSenderId());
                        }
                        \App\Models\CommunicationLog::create([
                            'recipient_type' => $item->target,
                            'recipient_id' => $entity->id ?? null,
                            'contact' => $contact,
                            'channel' => $channel,
                            'message' => $personalized,
                            'type' => $channel,
                            'status' => 'sent',
                            'response' => 'OK',
                            'classroom_id' => $entity->classroom_id ?? null,
                            'scope' => 'scheduled_fee',
                            'sent_at' => now(),
                            'tracking_id' => 'scheduled_fee_' . $item->id,
                        ]);
                        $totalSent++;
                    } catch (\Throwable $e) {
                        Log::warning('Scheduled fee communication send failed', [
                            'item_id' => $item->id,
                            'channel' => $channel,
                            'contact' => $contact,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        if ($totalSent === 0) {
            Log::warning('ProcessScheduledFeeCommunicationsJob: no messages sent (0 recipients matched filters)', [
                'item_id' => $item->id,
                'target' => $item->target,
                'filter_type' => $item->filter_type,
            ]);
        }

        if ($item->isRecurring()) {
            $next = $this->calculateNextRecurrence($item);
            if ($next) {
                $item->update([
                    'status' => 'active',
                    'recurrence_next_at' => $next,
                ]);
            } else {
                $item->update(['status' => 'completed']);
            }
        } else {
            $item->update(['status' => 'sent']);
        }
    }

    protected function calculateNextRecurrence(ScheduledFeeCommunication $item): ?\Carbon\Carbon
    {
        $now = now();
        $times = $item->recurrence_times ?: ['09:00'];
        $endAt = $item->recurrence_end_at;

        if (in_array($item->recurrence_type, ['daily', 'times_per_day'])) {
            $base = $now->copy()->startOfDay();
            foreach ($times as $t) {
                $parts = array_pad(explode(':', $t), 2, 0);
                $candidate = $base->copy()->setTime((int) $parts[0], (int) $parts[1]);
                if ($candidate->gt($now) && (!$endAt || $candidate->lte($endAt))) {
                    return $candidate;
                }
            }
            $parts = array_pad(explode(':', $times[0]), 2, 0);
            $candidate = $base->copy()->addDay()->setTime((int) $parts[0], (int) $parts[1]);
            return (!$endAt || $candidate->lte($endAt)) ? $candidate : null;
        }

        if ($item->recurrence_type === 'weekly') {
            $weekDays = $item->recurrence_week_days ?: [1];
            $base = $now->copy()->startOfDay();
            for ($i = 0; $i <= 7; $i++) {
                $check = $base->copy()->addDays($i);
                $dayOfWeek = (int) $check->format('w');
                if (!in_array($dayOfWeek, $weekDays)) {
                    continue;
                }
                foreach ($times as $t) {
                    $parts = array_pad(explode(':', $t), 2, 0);
                    $candidate = $check->copy()->setTime((int) $parts[0], (int) $parts[1]);
                    if ($candidate->gt($now) && (!$endAt || $candidate->lte($endAt))) {
                        return $candidate;
                    }
                }
            }
            return null;
        }

        return null;
    }

    protected function getMessage(ScheduledFeeCommunication $item): string
    {
        if (!empty($item->custom_message)) {
            return $item->custom_message;
        }
        $template = $item->template;
        return $template ? ($template->content ?? '') : '';
    }

    protected function getSubject(ScheduledFeeCommunication $item): string
    {
        $template = $item->template;
        if ($template && ($template->subject ?? $template->title)) {
            return $template->subject ?? $template->title;
        }
        return 'Fee Payment Reminder';
    }

    protected function buildRecipientData(ScheduledFeeCommunication $item): array
    {
        $data = $this->scheduledToCollectData($item);
        $out = [
            'email' => CommunicationHelperService::collectRecipients($data, 'email'),
            'sms' => CommunicationHelperService::collectRecipients($data, 'sms'),
            'whatsapp' => CommunicationHelperService::collectRecipients($data, 'whatsapp'),
        ];
        return $out;
    }

    protected function scheduledToCollectData(ScheduledFeeCommunication $item): array
    {
        $data = [
            'target' => $item->target,
            'student_id' => $item->student_id,
            'selected_student_ids' => $item->selected_student_ids,
            'classroom_ids' => $item->classroom_ids,
            'exclude_staff' => $item->exclude_staff ?? true,
            'exclude_student_ids' => $item->exclude_student_ids,
        ];

        switch ($item->filter_type) {
            case 'outstanding_fees':
                $data['fee_balance_only'] = true;
                break;
            case 'upcoming_invoices':
                $data['upcoming_invoices_only'] = true;
                break;
            case 'swimming_balance':
                $data['swimming_balance_only'] = true;
                break;
            case 'prior_term_balance':
                $data['prior_term_balance_only'] = true;
                break;
        }

        if (isset($item->balance_min) && $item->balance_min > 0) {
            if ($item->filter_type === 'swimming_balance') {
                $data['swimming_balance_min'] = $item->balance_min;
            } elseif ($item->filter_type === 'prior_term_balance') {
                $data['prior_term_balance_min'] = $item->balance_min;
            } else {
                $data['fee_balance_min'] = $item->balance_min;
            }
        }
        if (isset($item->balance_percent_min) && $item->balance_percent_min > 0) {
            $data['fee_balance_percent_min'] = $item->balance_percent_min;
        }

        return $data;
    }

    protected function entityToArray($entity): array
    {
        if (!$entity) {
            return [];
        }
        return [
            'id' => $entity->id ?? null,
            'classroom_id' => $entity->classroom_id ?? null,
            'type' => is_object($entity) ? get_class($entity) : null,
            'first_name' => $entity->first_name ?? null,
            'last_name' => $entity->last_name ?? null,
            'admission_number' => $entity->admission_number ?? null,
        ];
    }

    protected function normalizeKenyanPhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $clean = preg_replace('/[^\d+]/', '', $phone);
        $clean = ltrim($clean, '+');
        if (str_starts_with($clean, '0')) {
            $clean = '254' . substr($clean, 1);
        }
        if (!str_starts_with($clean, '254')) return null;
        if (!preg_match('/^254\d{8,9}$/', $clean)) return null;
        return $clean;
    }
}
