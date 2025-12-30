<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WasenderSessionController extends Controller
{
    public function index(WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        try {
            $list = $wa->listSessions();
            $sessions = data_get($list, 'body.data', []);
            $error = $list['status'] === 'error' ? ($list['body'] ?? 'Unable to fetch sessions') : null;
        } catch (\Throwable $e) {
            $sessions = [];
            $error = $e->getMessage();
        }

        return view('communication.wasender_sessions', compact('sessions', 'error'));
    }

    public function store(Request $request, WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:50',
            'account_protection' => 'sometimes|boolean',
            'log_messages' => 'sometimes|boolean',
            'webhook_enabled' => 'sometimes|boolean',
            'read_incoming_messages' => 'sometimes|boolean',
            'auto_reject_calls' => 'sometimes|boolean',
            'ignore_groups' => 'sometimes|boolean',
            'ignore_channels' => 'sometimes|boolean',
            'ignore_broadcasts' => 'sometimes|boolean',
        ]);

        // Default webhook URL to our webhook endpoint if not provided in UI
        $data['webhook_url'] = route('webhooks.whatsapp.wasender');

        $data['webhook_events'] = [
            'messages.received',
            'session.status',
            'messages.update',
        ];

        $payload = array_merge([
            'account_protection' => true,
            'log_messages' => true,
            'webhook_enabled' => true,
            'read_incoming_messages' => false,
            'auto_reject_calls' => false,
            'ignore_groups' => false,
            'ignore_channels' => false,
            'ignore_broadcasts' => false,
        ], $data);

        try {
            $resp = $wa->createSession($payload);
            if ($resp['status'] === 'success') {
                return redirect()->route('communication.wasender.sessions')->with('success', 'Session created. If status is NEED_SCAN, open connect to get QR.');
            }
            return back()->with('error', 'Create failed: ' . json_encode($resp['body']));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function connect($id, WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);
        try {
            $resp = $wa->connectSession($id);
            if ($resp['status'] === 'success') {
                $qr = data_get($resp, 'body.data.qrCode');
                $status = data_get($resp, 'body.data.status');
                return back()->with('success', "Connect requested. Status: {$status}. QR: {$qr}");
            }
            return back()->with('error', 'Connect failed: ' . json_encode($resp['body']));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function restart($id, WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);
        try {
            $resp = $wa->restartSession($id);
            if ($resp['status'] === 'success') {
                return back()->with('success', 'Session restart requested.');
            }
            return back()->with('error', 'Restart failed: ' . json_encode($resp['body']));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id, WhatsAppService $wa)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);
        try {
            $resp = $wa->deleteSession($id);
            if ($resp['status'] === 'success') {
                return back()->with('success', 'Session deleted.');
            }
            return back()->with('error', 'Delete failed: ' . json_encode($resp['body']));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}


