<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Models\Student;
use App\Services\SMSService;
use Illuminate\Http\Request;

class ApiCommunicationController extends Controller
{
    public function templates(Request $request)
    {
        $type = $request->input('type', 'sms');
        $templates = CommunicationTemplate::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('title')
            ->get()
            ->map(fn (CommunicationTemplate $t) => [
                'id' => $t->id,
                'code' => $t->code,
                'title' => $t->title,
                'type' => $t->type,
                'subject' => $t->subject,
                'content' => $t->content,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $templates]);
    }

    public function templateStore(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:sms,email,whatsapp',
            'code' => 'nullable|string|max:100|unique:communication_templates,code',
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
        ]);

        $template = CommunicationTemplate::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Template created.',
            'data' => $this->serializeTemplate($template),
        ], 201);
    }

    public function templateUpdate(Request $request, int $id)
    {
        $template = CommunicationTemplate::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:sms,email,whatsapp',
            'code' => 'nullable|string|max:100|unique:communication_templates,code,'.$template->id,
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
        ]);

        $template->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Template updated.',
            'data' => $this->serializeTemplate($template->fresh()),
        ]);
    }

    public function templateDestroy(int $id)
    {
        $template = CommunicationTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['success' => true, 'message' => 'Template deleted.']);
    }

    public function templateShow(int $id)
    {
        $t = CommunicationTemplate::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $t->id,
                'code' => $t->code,
                'title' => $t->title,
                'type' => $t->type,
                'subject' => $t->subject,
                'content' => $t->content,
                'created_at' => $t->created_at?->toIso8601String(),
                'updated_at' => $t->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function logShow(int $id)
    {
        $log = CommunicationLog::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log->id,
                'channel' => $log->channel,
                'contact' => $log->contact,
                'title' => $log->title,
                'message' => $log->message,
                'status' => $log->status,
                'sent_at' => $log->sent_at?->toIso8601String(),
                'delivered_at' => $log->delivered_at?->toIso8601String(),
                'created_at' => $log->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Parent SMS recipients, optionally scoped to a classroom.
     * Used by the mobile SMS compose recipient picker.
     */
    public function recipients(Request $request)
    {
        $query = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereNotNull('parent_id')
            ->with(['parent', 'classroom']);

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', (int) $request->input('classroom_id'));
        }

        $students = $query->orderBy('first_name')->limit(2000)->get();

        $recipients = [];
        $seenPhones = [];
        foreach ($students as $student) {
            $parent = $student->parent;
            if (! $parent) {
                continue;
            }
            foreach ($parent->schoolNotificationSmsRecipients() as $r) {
                $phone = $r['phone'] ?? null;
                if (! $phone || isset($seenPhones[$phone])) {
                    continue;
                }
                $seenPhones[$phone] = true;
                $recipients[] = [
                    'phone' => $phone,
                    'name' => $r['name'] ?? null,
                    'relation' => $r['slot'] ?? null,
                    'student_name' => $student->full_name,
                    'classroom' => $student->classroom?->name,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'recipients' => $recipients,
                'total' => count($recipients),
                'students_matched' => $students->count(),
            ],
        ]);
    }

    public function logs(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);
        $query = CommunicationLog::query()->orderByDesc('created_at');

        if ($request->filled('channel')) {
            $query->where('channel', $request->string('channel'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn (CommunicationLog $log) => [
            'id' => $log->id,
            'channel' => $log->channel,
            'contact' => $log->contact,
            'title' => $log->title,
            'message' => $log->message,
            'status' => $log->status,
            'sent_at' => $log->sent_at?->toIso8601String(),
            'delivered_at' => $log->delivered_at?->toIso8601String(),
            'created_at' => $log->created_at?->toIso8601String(),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function sendSms(Request $request, SMSService $smsService)
    {
        $data = $request->validate([
            'message' => 'nullable|string',
            'template_id' => 'nullable|exists:communication_templates,id',
            'custom_numbers' => 'nullable|string',
            'phones' => 'nullable|array',
            'phones.*' => 'string',
            'sender_id' => 'nullable|string|in:finance,default',
        ]);

        $message = $data['message'] ?? '';
        if (! empty($data['template_id'])) {
            $tpl = CommunicationTemplate::find($data['template_id']);
            $message = $tpl?->content ?: $message;
        }

        if (! filled($message)) {
            return response()->json(['success' => false, 'message' => 'Message content is required.'], 422);
        }

        $phones = $data['phones'] ?? [];
        if (! empty($data['custom_numbers'])) {
            $phones = array_merge(
                $phones,
                preg_split('/[\s,;]+/', $data['custom_numbers'], -1, PREG_SPLIT_NO_EMPTY) ?: []
            );
        }

        $phones = array_values(array_unique(array_filter(array_map(
            fn ($p) => $this->normalizeKenyanPhone($p),
            $phones
        ))));

        if (count($phones) === 0) {
            return response()->json(['success' => false, 'message' => 'At least one valid phone number is required.'], 422);
        }

        $sender = null;
        if (($data['sender_id'] ?? '') === 'finance') {
            $sender = $smsService->getFinanceSenderId();
        }

        $sent = 0;
        $failed = 0;
        foreach ($phones as $phone) {
            try {
                $response = $smsService->sendSMS($phone, $message, $sender);
                if ($response) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "SMS dispatch complete. Sent: {$sent}, failed: {$failed}.",
            'data' => ['sent' => $sent, 'failed' => $failed, 'total' => count($phones)],
        ]);
    }

    public function sendWhatsApp(Request $request, \App\Services\WhatsAppService $whatsAppService)
    {
        $data = $request->validate([
            'message' => 'nullable|string',
            'template_id' => 'nullable|exists:communication_templates,id',
            'custom_numbers' => 'nullable|string',
            'phones' => 'nullable|array',
            'phones.*' => 'string',
        ]);

        $message = $data['message'] ?? '';
        if (! empty($data['template_id'])) {
            $tpl = CommunicationTemplate::find($data['template_id']);
            $message = $tpl?->content ?: $message;
        }

        if (! filled($message)) {
            return response()->json(['success' => false, 'message' => 'Message content is required.'], 422);
        }

        $phones = $data['phones'] ?? [];
        if (! empty($data['custom_numbers'])) {
            $phones = array_merge(
                $phones,
                preg_split('/[\s,;]+/', $data['custom_numbers'], -1, PREG_SPLIT_NO_EMPTY) ?: []
            );
        }

        $phones = array_values(array_unique(array_filter(array_map(
            fn ($p) => $this->normalizeKenyanPhone($p),
            $phones
        ))));

        if (count($phones) === 0) {
            return response()->json(['success' => false, 'message' => 'At least one valid phone number is required.'], 422);
        }

        $sent = 0;
        $failed = 0;
        foreach ($phones as $phone) {
            try {
                $result = $whatsAppService->sendMessage($phone, $message);
                if (($result['status'] ?? '') === 'success') {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "WhatsApp dispatch complete. Sent: {$sent}, failed: {$failed}.",
            'data' => ['sent' => $sent, 'failed' => $failed, 'total' => count($phones)],
        ]);
    }

    protected function serializeTemplate(CommunicationTemplate $t): array
    {
        return [
            'id' => $t->id,
            'code' => $t->code,
            'title' => $t->title,
            'type' => $t->type,
            'subject' => $t->subject,
            'content' => $t->content,
            'created_at' => $t->created_at?->toIso8601String(),
            'updated_at' => $t->updated_at?->toIso8601String(),
        ];
    }

    protected function normalizeKenyanPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            return '+'.$digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+254'.substr($digits, 1);
        }
        if (str_starts_with($digits, '7') && strlen($digits) === 9) {
            return '+254'.$digits;
        }

        return null;
    }
}
