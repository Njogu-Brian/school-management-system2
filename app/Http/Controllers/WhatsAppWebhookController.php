<?php

namespace App\Http\Controllers;

use App\Models\CommunicationLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Meta WhatsApp webhook: GET verification handshake + POST inbound events.
     */
    public function handleMeta(Request $request, WhatsAppService $whatsAppService): Response|\Illuminate\Http\JsonResponse
    {
        if ($request->isMethod('GET')) {
            return $this->verifyMetaSubscription($request, $whatsAppService);
        }

        return $this->processMetaWebhook($request);
    }

    /**
     * @deprecated Legacy Wasender webhook — kept for in-flight callbacks during migration.
     */
    public function handle(Request $request, WhatsAppService $whatsAppService)
    {
        Log::info('Legacy Wasender webhook received — migrate to /webhooks/whatsapp/meta', [
            'payload_keys' => array_keys($request->all()),
        ]);

        return response()->json([
            'ok' => false,
            'reason' => 'Wasender webhooks are deprecated. Configure Meta webhook at ' . $whatsAppService->webhookUrl(),
        ], 410);
    }

    protected function verifyMetaSubscription(Request $request, WhatsAppService $whatsAppService): Response
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode'));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge'));

        if ($mode === 'subscribe' && $whatsAppService->validateWebhookVerifyToken($token)) {
            Log::info('Meta WhatsApp webhook verified');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Meta WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token_provided' => $token !== '',
        ]);

        return response('Forbidden', 403);
    }

    protected function processMetaWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->all();
        Log::info('Meta WhatsApp webhook received', ['object' => data_get($payload, 'object')]);

        if (data_get($payload, 'object') !== 'whatsapp_business_account') {
            return response()->json(['ok' => true, 'message' => 'ignored object type']);
        }

        foreach (data_get($payload, 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                $this->processMetaChange($change, $payload);
            }
        }

        return response()->json(['ok' => true]);
    }

    protected function processMetaChange(array $change, array $fullPayload): void
    {
        $field = data_get($change, 'field');
        $value = data_get($change, 'value', []);

        if ($field === 'messages') {
            $this->logInboundMessages($value, $fullPayload);
        }

        if ($field === 'message_template_status_update') {
            Log::info('Meta WhatsApp template status update', [
                'event' => data_get($value, 'event'),
                'template' => data_get($value, 'message_template_name'),
            ]);
        }
    }

    protected function logInboundMessages(array $value, array $fullPayload): void
    {
        $messages = data_get($value, 'messages', []);

        foreach ($messages as $message) {
            $sender = data_get($message, 'from');
            $providerId = data_get($message, 'id');
            $messageBody = data_get($message, 'text.body')
                ?? data_get($message, 'button.text')
                ?? data_get($message, 'interactive.button_reply.title')
                ?? '[' . (data_get($message, 'type') ?? 'unknown') . ' message]';

            CommunicationLog::create([
                'recipient_type' => 'webhook',
                'recipient_id'   => null,
                'contact'        => $sender,
                'channel'        => 'whatsapp',
                'message'        => $messageBody,
                'type'           => 'whatsapp',
                'status'         => 'received',
                'response'       => $fullPayload,
                'scope'          => 'webhook',
                'sent_at'        => now(),
                'provider_id'    => $providerId,
                'provider_status'=> data_get($message, 'type'),
            ]);
        }

        $statuses = data_get($value, 'statuses', []);
        foreach ($statuses as $status) {
            $providerId = data_get($status, 'id');
            if (!$providerId) {
                continue;
            }

            $log = CommunicationLog::where('channel', 'whatsapp')
                ->where('provider_id', $providerId)
                ->first();

            $deliveryState = data_get($status, 'status');
            $deliveryErrors = data_get($status, 'errors');

            if (!$log) {
                Log::warning('WhatsApp delivery status for unknown message', [
                    'wamid' => $providerId,
                    'status' => $deliveryState,
                    'recipient' => data_get($status, 'recipient_id'),
                    'errors' => $deliveryErrors,
                ]);
                continue;
            }

            $existingResponse = is_array($log->response) ? $log->response : [];
            $log->update([
                'provider_status' => $deliveryState,
                'error_code' => data_get($deliveryErrors, '0.code'),
                'response' => array_merge($existingResponse, ['delivery_status' => $status]),
            ]);

            if ($deliveryState === 'failed') {
                Log::warning('WhatsApp delivery failed', [
                    'wamid' => $providerId,
                    'contact' => $log->contact,
                    'payment_id' => $log->payment_id,
                    'errors' => $deliveryErrors,
                ]);
            }
        }
    }
}
