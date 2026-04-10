<?php

namespace App\Jobs;

use App\Models\ScheduledCommunication;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Services\CommunicationHelperService;
use App\Services\SMSService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\GenericMail;

class SendScheduledCommunicationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 min max for cron dispatch; actual sends run in child jobs

    public function handle(SMSService $smsService, WhatsAppService $whatsAppService)
    {
        $now = now();
        $pending = ScheduledCommunication::where('status', 'pending')
            ->where('send_at', '<=', $now)
            ->get();

        foreach ($pending as $item) {
            $lock = Cache::lock("scheduled_comm:{$item->id}", 15 * 60);
            if (!$lock->get()) {
                continue;
            }

            try {
                // Re-check inside the lock to avoid double-send under concurrency.
                $item = ScheduledCommunication::query()
                    ->whereKey($item->id)
                    ->where('status', 'pending')
                    ->where('send_at', '<=', $now)
                    ->first();
                if (!$item) {
                    continue;
                }

            $template = CommunicationTemplate::find($item->template_id);
            if (!$template) continue;

            $recipients = CommunicationHelperService::collectRecipients([
                'target' => $item->target,
                'classroom_id' => $item->classroom_id,
                'classroom_ids' => $item->classroom_ids
                    ? array_filter(array_map('intval', explode(',', $item->classroom_ids)))
                    : null,
            ], $item->type);

            $pairs = CommunicationHelperService::expandRecipientsToPairs($recipients);

            // For large batches (>10), dispatch bulk job to avoid timeout
            if (count($pairs) > 10) {
                $trackingId = 'scheduled_' . $item->type . '_' . $item->id . '_' . Str::uuid()->toString();
                $title = $template->title ?? ucfirst($item->type);

                if ($item->type === 'email') {
                    $recipientsData = [];
                    foreach ($pairs as [$email, $entity]) {
                        $recipientsData[] = [
                            'email' => $email,
                            'entity' => [
                                'id' => $entity->id ?? null,
                                'classroom_id' => $entity->classroom_id ?? null,
                                'type' => is_object($entity) ? get_class($entity) : null,
                                'first_name' => $entity->first_name ?? null,
                                'last_name' => $entity->last_name ?? null,
                                'admission_number' => $entity->admission_number ?? null,
                            ],
                        ];
                    }
                    \App\Jobs\BulkSendEmail::dispatch($trackingId, $recipientsData, $template->content, $title, $item->target, null, null);
                } elseif ($item->type === 'whatsapp') {
                    $recipientsData = [];
                    foreach ($pairs as [$phone, $entity]) {
                        $normalized = $this->normalizeKenyanPhone($phone);
                        if (!$normalized) continue;
                        $recipientsData[] = [
                            'phone' => $normalized,
                            'entity' => [
                                'id' => $entity->id ?? null,
                                'classroom_id' => $entity->classroom_id ?? null,
                                'type' => is_object($entity) ? get_class($entity) : null,
                                'first_name' => $entity->first_name ?? null,
                                'last_name' => $entity->last_name ?? null,
                                'admission_number' => $entity->admission_number ?? null,
                            ],
                        ];
                    }
                    \App\Jobs\BulkSendWhatsAppMessages::dispatch($trackingId, $recipientsData, $template->content, $title, $item->target, null, true, null);
                } else {
                    // SMS - normalize Kenyan phones
                    $recipientsData = [];
                    foreach ($pairs as [$phone, $entity]) {
                        $normalized = $this->normalizeKenyanPhone($phone);
                        if ($normalized) {
                            $recipientsData[] = [
                                'phone' => $normalized,
                                'entity' => [
                                    'id' => $entity->id ?? null,
                                    'classroom_id' => $entity->classroom_id ?? null,
                                    'type' => is_object($entity) ? get_class($entity) : null,
                                    'first_name' => $entity->first_name ?? null,
                                    'last_name' => $entity->last_name ?? null,
                                    'admission_number' => $entity->admission_number ?? null,
                                ],
                            ];
                        }
                    }
                    \App\Jobs\BulkSendSMS::dispatch($trackingId, $recipientsData, $template->content, $title, $item->target, null, null);
                }
                $item->update(['status' => 'sent']);
                continue;
            }

            foreach ($pairs as [$contact, $entity]) {
                $personalized = replace_placeholders($template->content, $entity);

                try {
                    $logChannel = $item->type;
                    $status = 'sent';
                    $response = null;
                    $providerId = null;
                    $providerStatus = null;

                    if ($item->type === 'email') {
                        Mail::to($contact)->send(new GenericMail($template->title, $personalized));
                        $response = ['status' => 'sent'];
                        $providerStatus = 'sent';
                    } elseif ($item->type === 'whatsapp') {
                        $whatsAppResponse = $whatsAppService->sendMessage($contact, $personalized);
                        $status = data_get($whatsAppResponse, 'status') === 'success' ? 'sent' : 'failed';
                        $response = $whatsAppResponse;
                        $providerId = data_get($whatsAppResponse, 'body.data.id')
                            ?? data_get($whatsAppResponse, 'body.data.message.id')
                            ?? data_get($whatsAppResponse, 'body.messageId')
                            ?? data_get($whatsAppResponse, 'body.id');
                        $providerStatus = data_get($whatsAppResponse, 'body.status') ?? data_get($whatsAppResponse, 'status');
                    } else {
                        $smsResponse = $smsService->sendSMS($contact, $personalized);
                        $response = $smsResponse;
                        $providerStatus = strtolower(data_get($smsResponse, 'status', 'sent'));
                        $providerId = data_get($smsResponse,'id') 
                            ?? data_get($smsResponse,'message_id') 
                            ?? data_get($smsResponse,'MessageID');
                    }

                    CommunicationLog::create([
                        'recipient_type' => $item->target,
                        'recipient_id'   => $entity->id ?? null,
                        'contact'        => $contact,
                        'channel'        => $logChannel,
                        'message'        => $personalized,
                        'type'           => $item->type,
                        'status'         => $status,
                        'response'       => $response ?? $personalized,
                        'classroom_id'   => $item->classroom_id,
                        'scope'          => 'scheduled',
                        'sent_at'        => now(),
                        'provider_id'    => $providerId,
                        'provider_status'=> $providerStatus,
                    ]);
                } catch (\Throwable $e) {
                    CommunicationLog::create([
                        'recipient_type' => $item->target,
                        'recipient_id'   => $entity->id ?? null,
                        'contact'        => $contact,
                        'channel'        => $item->type,
                        'message'        => $personalized,
                        'type'           => $item->type,
                        'status'         => 'failed',
                        'response'       => $e->getMessage(),
                        'classroom_id'   => $item->classroom_id,
                        'scope'          => 'scheduled',
                        'sent_at'        => now(),
                    ]);
                }
            }

            $item->update(['status' => 'sent']);
            } finally {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }
    }

    private function normalizeKenyanPhone(?string $phone): ?string
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
