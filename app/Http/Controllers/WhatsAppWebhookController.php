<?php

namespace App\Http\Controllers;

use App\Models\CommunicationLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request, WhatsAppService $whatsAppService)
    {
        $providedToken = $request->header('X-Wasender-Token') ?? $request->query('token');
        if (!$whatsAppService->validateWebhookToken($providedToken)) {
            return response()->json(['ok' => false, 'reason' => 'invalid webhook token'], 401);
        }

        $payload = $request->all();
        Log::info('Wasender webhook received', ['payload' => $payload]);

        $messageData = data_get($payload, 'data.messages');
        if (!$messageData) {
            return response()->json(['ok' => true, 'message' => 'no messages node present']);
        }

        $key = data_get($messageData, 'key', []);
        $sender = $key['cleanedParticipantPn']
            ?? $key['cleanedSenderPn']
            ?? $key['remoteJid']
            ?? null;

        $messageBody = data_get($messageData, 'messageBody', '');
        $providerId = data_get($key, 'id');

        CommunicationLog::create([
            'recipient_type' => 'webhook',
            'recipient_id'   => null,
            'contact'        => $sender,
            'channel'        => 'whatsapp',
            'message'        => $messageBody,
            'type'           => 'whatsapp',
            'status'         => 'received',
            'response'       => $payload,
            'scope'          => 'webhook',
            'sent_at'        => now(),
            'provider_id'    => $providerId,
            'provider_status'=> data_get($payload, 'event'),
        ]);

        return response()->json(['ok' => true]);
    }
}


