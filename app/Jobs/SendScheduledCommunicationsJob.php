<?php

namespace App\Jobs;

use App\Models\ScheduledCommunication;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Services\CommunicationHelperService;
use App\Services\SMSService;
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

    public function handle(SMSService $smsService)
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
                    if ($item->type === 'email') {
                        Mail::to($contact)->send(new GenericMail($template->title, $personalized));
                    } else {
                        $smsService->sendSMS($contact, $personalized);
                    }

                    CommunicationLog::create([
                        'recipient_type' => $item->target,
                        'recipient_id'   => $entity->id ?? null,
                        'contact'        => $contact,
                        'channel'        => $item->type,
                        'message'        => $personalized,
                        'type'           => $item->type,
                        'status'         => 'sent',
                        'classroom_id'   => $item->classroom_id,
                        'scope'          => 'scheduled',
                        'sent_at'        => now(),
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
