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
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;

class SendScheduledCommunicationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SMSService $smsService, WhatsAppService $whatsAppService)
    {
        $now = now();
        $pending = ScheduledCommunication::where('status', 'pending')
            ->where('send_at', '<=', $now)
            ->get();

        foreach ($pending as $item) {
            $template = CommunicationTemplate::find($item->template_id);
            if (!$template) continue;

            $recipients = CommunicationHelperService::collectRecipients([
                'target' => $item->target,
                'classroom_id' => $item->classroom_id,
            ], $item->type);

            foreach ($recipients as $contact => $entity) {
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
        }
    }
}
