<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WasenderSessionController extends Controller
{
    public function index(WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        $connection = null;
        $error = null;

        try {
            $connection = $wa->status();
            if ($connection['status'] === 'error') {
                $error = is_array($connection['body'])
                    ? (data_get($connection['body'], 'error.message') ?? json_encode($connection['body']))
                    : ($connection['body'] ?? 'Unable to verify WhatsApp connection');
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $config = [
            'phone_number_id' => $wa->phoneNumberId(),
            'business_account_id' => $wa->businessAccountId(),
            'webhook_url' => $wa->webhookUrl(),
            'default_template' => config('services.whatsapp.default_template'),
            'api_version' => config('services.whatsapp.api_version'),
        ];

        return view('communication.wasender_sessions', compact('connection', 'error', 'config'));
    }

    public function store(Request $request, WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        return redirect()
            ->route('communication.wasender.sessions')
            ->with('error', 'Session creation is not used with Meta WhatsApp Cloud API. Manage your number in Meta Business Manager.');
    }

    public function connect($id, WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        return back()->with('error', 'QR session connect is not used with Meta WhatsApp Cloud API.');
    }

    public function restart($id, WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        return back()->with('error', 'Session restart is not used with Meta WhatsApp Cloud API.');
    }

    public function destroy($id, WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        return back()->with('error', 'Session delete is not used with Meta WhatsApp Cloud API.');
    }

    public function updateSettings(Request $request, $id, WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        return back()->with('error', 'Session settings are not used with Meta WhatsApp Cloud API.');
    }
}
