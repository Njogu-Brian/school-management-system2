<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CommunicationLog;
use App\Models\ScheduledCommunication;
use App\Models\CommunicationTemplate;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Academics\Classroom;
use App\Services\SMSService;
use App\Services\WhatsAppService;
use App\Services\CommunicationHelperService;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;
use Illuminate\Support\Facades\Storage;

class CommunicationController extends Controller
{
    /**
     * Normalize a phone and ensure it is a Kenyan MSISDN (country code 254).
     * Returns null if invalid/non-Kenyan.
     */
    private function normalizeKenyanPhone(?string $phone): ?string
    {
        if (!$phone) return null;
        // Keep digits and +
        $clean = preg_replace('/[^\d+]/', '', $phone);
        $clean = ltrim($clean, '+');
        // If starts with 0, replace leading 0 with 254
        if (str_starts_with($clean, '0')) {
            $clean = '254' . substr($clean, 1);
        }
        // If already 254...
        if (!str_starts_with($clean, '254')) {
            return null; // non-Kenyan, skip
        }
        // Must be digits only now and reasonable length (min 11, max 12)
        if (!preg_match('/^254\d{8,9}$/', $clean)) {
            return null;
        }
        return $clean;
    }

    /* ========== EMAIL ========== */
    public function createEmail()
    {
        abort_unless(can_access("communication", "email", "add"), 403);

        $templates = CommunicationTemplate::where('type', 'email')->get();
        $classes   = Classroom::with('streams')->get();
        $systemPlaceholders = $this->getSystemPlaceholders();
        $customPlaceholders = \App\Models\CustomPlaceholder::all();

        // Sort by full name at the DB level (exclude alumni and archived)
        $students = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->orderByRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) ASC")
            ->get();

        return view('communication.send_email', compact('templates', 'classes', 'students', 'systemPlaceholders', 'customPlaceholders'));
    }

    public function sendEmail(Request $request)
    {
        abort_unless(can_access("communication", "email", "add"), 403);

        $data = $request->validate([
            'template_id'    => 'nullable|exists:communication_templates,id',
            'message'        => 'nullable|string',
            'target'         => 'required|string',
            'custom_emails'  => 'nullable|string',
            'title'          => 'nullable|string|max:255',
            'classroom_id'   => 'nullable|integer',
            'student_id'     => 'nullable|integer',
            'selected_student_ids' => 'nullable|string',
            'attachment'     => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,mp4,mov,avi,webm|max:20480',
            'schedule'       => 'nullable|string|in:now,later',
            'send_at'        => 'nullable|date',
        ]);

        $subject     = $data['title'] ?? 'Untitled Email';
        $messageBody = $data['message'];

        if ($data['template_id']) {
            $tpl         = CommunicationTemplate::find($data['template_id']);
            $subject     = $tpl->title   ?: $subject;
            $messageBody = $tpl->content ?: $messageBody;
        }

        if (!$messageBody) {
            return back()->with('error', 'Message body is required.');
        }

        if ($request->schedule === 'later' && empty($data['template_id'])) {
            return back()->with('error', 'Select a template when scheduling email.');
        }

        // === HANDLE SCHEDULED EMAIL ===
        if ($request->schedule === 'later' && $request->send_at) {
            ScheduledCommunication::create([
                'type'         => 'email',
                'template_id'  => $data['template_id'] ?? null,
                'target'       => $data['target'],
                'classroom_id' => $data['classroom_id'] ?? null,
                'send_at'      => $data['send_at'],
                'status'       => 'pending',
            ]);
            return redirect()->route('communication.send.email')->with('success', 'Email scheduled for ' . $data['send_at']);
        }

        $attachmentPath = $request->file('attachment')
            ? $request->file('attachment')->store('email_attachments', 'public')
            : null;

        $recipients = $this->collectRecipients($data, 'email');
        $sentCount = 0;
        $failedCount = 0;
        $failures = [];

        foreach ($recipients as $email => $entity) {
            try {
                $personalized = replace_placeholders($messageBody, $entity);
                Mail::to($email)->send(new GenericMail($subject, $personalized, $attachmentPath));

                $sentCount++;
                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $email,
                    'channel'        => 'email',
                    'title'          => $subject,
                    'message'        => $personalized,
                    'type'           => 'email',
                    'status'         => 'sent',
                    'response'       => 'OK',
                    'classroom_id'   => $entity->classroom_id ?? null,
                    'scope'          => 'email',
                    'sent_at'        => now(),
                ]);
            } catch (\Throwable $e) {
                $failedCount++;
                $failures[] = ['email' => $email, 'reason' => $e->getMessage()];
                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $email,
                    'channel'        => 'email',
                    'title'          => $subject,
                    'message'        => $messageBody,
                    'type'           => 'email',
                    'status'         => 'failed',
                    'response'       => $e->getMessage(),
                    'scope'          => 'email',
                    'sent_at'        => now(),
                ]);
            }
        }

        $summary = "Email: sent {$sentCount}, failed {$failedCount}";
        $flashType = $failedCount > 0 ? 'warning' : 'success';
        $withData = [$flashType => $summary];
        if ($failedCount > 0) {
            $withData['error'] = 'Some sends failed. Sample: ' . json_encode($failures[0] ?? []);
        }

        return redirect()->route('communication.send.email')->with($withData);
    }

    /* ========== SMS ========== */
    public function createSMS()
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        $templates = CommunicationTemplate::where('type', 'sms')->get();
        $classes   = Classroom::with('streams')->get();
        $systemPlaceholders = $this->getSystemPlaceholders();
        $customPlaceholders = \App\Models\CustomPlaceholder::all();

        // Same here for the SMS page (exclude alumni and archived)
        $students = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->orderByRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) ASC")
            ->get();

        return view('communication.send_sms', compact('templates', 'classes', 'students', 'systemPlaceholders', 'customPlaceholders'));
    }

    public function sendSMS(Request $request, SMSService $smsService)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        $data = $request->validate([
            'template_id'    => 'nullable|exists:communication_templates,id',
            'message'        => 'nullable|string|max:300',
            'target'         => 'required|string',
            'custom_numbers' => 'nullable|string',
            'classroom_id'   => 'nullable|integer',
            'student_id'     => 'nullable|integer',
            'selected_student_ids' => 'nullable|string',
            'schedule'       => 'nullable|string|in:now,later',
            'send_at'        => 'nullable|date',
            'sender_id'      => 'nullable|string|in:finance,default,""',
        ]);

        $message = $data['message'];
        if ($data['template_id']) {
            $tpl     = CommunicationTemplate::find($data['template_id']);
            $message = $tpl->content ?: $message;
        }

        if (!$message) {
            return back()->with('error', 'Message content is required.');
        }

        if ($request->schedule === 'later' && empty($data['template_id'])) {
            return back()->with('error', 'Select a template when scheduling SMS.');
        }

        // === HANDLE SCHEDULED SMS ===
        if ($request->schedule === 'later' && $request->send_at) {
            ScheduledCommunication::create([
                'type'         => 'sms',
                'template_id'  => $data['template_id'] ?? null,
                'target'       => $data['target'],
                'classroom_id' => $data['classroom_id'] ?? null,
                'send_at'      => $data['send_at'],
                'status'       => 'pending',
            ]);
            return redirect()->route('communication.send.sms')->with('success', 'SMS scheduled for ' . $data['send_at']);
        }

        $rawRecipients = $this->collectRecipients($data, 'sms');
        $chosenSender = null;
        if ($request->input('sender_id') === 'finance') {
            $chosenSender = $smsService->getFinanceSenderId();
        }
        $recipients = [];
        $skipped = [];
        foreach ($rawRecipients as $phone => $entity) {
            $normalized = $this->normalizeKenyanPhone($phone);
            if (!$normalized) {
                $skipped[] = $phone;
                continue;
            }
            $recipients[$normalized] = $entity;
        }
        $title = 'SMS';
        if (!empty($data['template_id'])) {
            $tpl   = CommunicationTemplate::find($data['template_id']);
            $title = $tpl?->title ?: $title;
        }
        $sentCount = 0;
        $failedCount = 0;
        $failures = [];
        foreach ($recipients as $phone => $entity) {
            try {
                $personalized = replace_placeholders($message, $entity);
                $response = $smsService->sendSMS($phone, $personalized, $chosenSender);

                $status = 'sent';
                if (strtolower(data_get($response, 'status', 'sent')) !== 'success'
                    && strtolower(data_get($response, 'status', 'sent')) !== 'sent') {
                    $status = 'failed';
                }
                $status === 'sent' ? $sentCount++ : $failedCount++;
                if ($status !== 'sent') {
                    $failures[] = [
                        'phone' => $phone,
                        'reason' => $response,
                    ];
                }

                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $phone,
                    'channel'        => 'sms',
                    'title'          => $title,
                    'message'        => $personalized,
                    'type'           => 'sms',
                    'status'         => $status,
                    'response'       => $response, // will be cast to array
                    'classroom_id'   => $entity->classroom_id ?? null,
                    'scope'          => 'sms',
                    'sent_at'        => now(),

                    // NEW (match to your provider fields):
                    'provider_id'    => data_get($response,'id') 
                                        ?? data_get($response,'message_id') 
                                        ?? data_get($response,'MessageID'),
                    'provider_status'=> strtolower(data_get($response,'status','sent')),
                ]);
            } catch (\Throwable $e) {
                $failedCount++;
                $failures[] = [
                    'phone' => $phone,
                    'reason' => $e->getMessage(),
                ];
                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $phone,
                    'channel'        => 'sms',
                    'message'        => $message,
                    'type'           => 'sms',
                    'status'         => 'failed',
                    'response'       => $e->getMessage(),
                    'scope'          => 'sms',
                    'sent_at'        => now(),
                ]);
            }
        }

        $summary = "SMS: sent {$sentCount}, failed {$failedCount}";
        if ($skipped) {
            $summary .= '. Skipped non-Kenyan: ' . implode(', ', array_slice($skipped, 0, 3)) . (count($skipped) > 3 ? '…' : '');
        }
        $flashType = ($failedCount > 0 || $skipped) ? 'warning' : 'success';
        $withData = [$flashType => $summary];
        if ($failedCount > 0) {
            $withData['error'] = 'Some sends failed. Sample: ' . json_encode($failures[0] ?? []);
        }

        return redirect()->route('communication.send.sms')->with($withData);
    }

    /* ========== WHATSAPP ========== */
    public function createWhatsApp()
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        // Allow reusing SMS templates for WhatsApp to avoid duplication
        $templates = CommunicationTemplate::whereIn('type', ['whatsapp', 'sms'])->get();
        $classes   = Classroom::with('streams')->get();
        $systemPlaceholders = $this->getSystemPlaceholders();
        $customPlaceholders = \App\Models\CustomPlaceholder::all();
        $students = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->orderByRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) ASC")
            ->get();

        return view('communication.send_whatsapp', compact('templates', 'classes', 'students', 'systemPlaceholders', 'customPlaceholders'));
    }

    public function sendWhatsApp(Request $request, WhatsAppService $whatsAppService)
    {
        abort_unless(can_access("communication", "sms", "add"), 403);

        $data = $request->validate([
            'template_id'    => 'nullable|exists:communication_templates,id',
            'message'        => 'nullable|string|max:1000',
            'target'         => 'required|string',
            'custom_numbers' => 'nullable|string',
            'classroom_id'   => 'nullable|integer',
            'student_id'     => 'nullable|integer',
            'selected_student_ids' => 'nullable|string',
            'schedule'       => 'nullable|string|in:now,later',
            'send_at'        => 'nullable|date',
            'media'          => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm|max:20480',
        ]);

        $message = $data['message'];
        $title = 'WhatsApp';
        if ($data['template_id']) {
            $tpl     = CommunicationTemplate::find($data['template_id']);
            $message = $tpl->content ?: $message;
            $title   = $tpl?->title ?: $title;
        }

        if (!$message) {
            return back()->with('error', 'Message content is required.');
        }

        if ($request->schedule === 'later' && empty($data['template_id'])) {
            return back()->with('error', 'Select a template when scheduling WhatsApp.');
        }

        // === HANDLE SCHEDULED WHATSAPP ===
        if ($request->schedule === 'later' && $request->send_at) {
            if ($request->hasFile('media')) {
                return back()->with('error', 'Scheduling with media is not supported yet. Please send now.');
            }
            ScheduledCommunication::create([
                'type'         => 'whatsapp',
                'template_id'  => $data['template_id'] ?? null,
                'target'       => $data['target'],
                'classroom_id' => $data['classroom_id'] ?? null,
                'send_at'      => $data['send_at'],
                'status'       => 'pending',
            ]);
            return redirect()->route('communication.send.whatsapp')->with('success', 'WhatsApp message scheduled for ' . $data['send_at']);
        }

        $mediaUrl = null;
        if ($request->hasFile('media')) {
            $path = $request->file('media')->store('whatsapp_media', 'public');
            $mediaUrl = Storage::disk('public')->url($path);
        }

        $recipients = $this->collectRecipients($data, 'whatsapp');
        $skipped = [];
        // For WhatsApp, keep numbers but surface invalid format earlier for clarity
        $normalizedRecipients = [];
        foreach ($recipients as $phone => $entity) {
            $normalized = $this->normalizeKenyanPhone($phone);
            if (!$normalized) {
                $skipped[] = $phone;
                continue;
            }
            $normalizedRecipients[$normalized] = $entity;
        }
        $recipients = $normalizedRecipients;
        
        // For bulk sends (>10 recipients), use queue job for reliability
        $useQueue = count($recipients) > 10 || $request->has('use_queue');
        $skipSent = $request->has('skip_sent') ? (bool)$request->skip_sent : true;
        
        if ($useQueue) {
            // Generate tracking ID
            $trackingId = 'whatsapp_bulk_' . uniqid() . '_' . time();
            
            // Prepare recipients data (serialize entities)
            $recipientsData = [];
            foreach ($recipients as $phone => $entity) {
                // Store entity data for reconstruction in job
                $recipientsData[$phone] = [
                    'id' => $entity->id ?? null,
                    'classroom_id' => $entity->classroom_id ?? null,
                    'type' => get_class($entity),
                    // Store additional data that might be needed for placeholders
                    'first_name' => $entity->first_name ?? null,
                    'last_name' => $entity->last_name ?? null,
                    'admission_number' => $entity->admission_number ?? null,
                ];
            }
            
            // Dispatch job
            \App\Jobs\BulkSendWhatsAppMessages::dispatch(
                $trackingId,
                $recipientsData,
                $message,
                $title,
                $data['target'],
                $mediaUrl,
                $skipSent
            );
            
            \Log::info('WhatsApp bulk send job dispatched', [
                'tracking_id' => $trackingId,
                'recipient_count' => count($recipients),
                'skip_sent' => $skipSent,
            ]);
            
            return redirect()->route('communication.send.whatsapp.progress', [
                'tracking_id' => $trackingId,
                'total' => count($recipients),
            ])->with('info', 'Bulk send started. Processing in background...');
        }
        $sentCount = 0;
        $failedCount = 0;
        $failures = [];
        $delayBetweenMessages = 5; // Default 5 seconds for account protection
        $lastSentTime = 0;
        $totalRecipients = count($recipients);
        
        $index = 0;
        foreach ($recipients as $phone => $entity) {
            $index++;
            try {
                // Calculate delay needed since last message (skip delay for first message)
                if ($lastSentTime > 0) {
                    $currentTime = time();
                    $timeSinceLastMessage = $currentTime - $lastSentTime;
                    
                    // Check if we need to wait (respect rate limiting)
                    if ($timeSinceLastMessage < $delayBetweenMessages) {
                        $waitTime = $delayBetweenMessages - $timeSinceLastMessage;
                        \Log::info("Rate limiting: waiting {$waitTime} seconds before sending to {$phone} ({$index}/{$totalRecipients})");
                        sleep($waitTime);
                    }
                }
                
                $personalized = replace_placeholders($message, $entity);
                $finalMessage = $mediaUrl ? ($personalized . "\n\nMedia: " . $mediaUrl) : $personalized;
                $response = $whatsAppService->sendMessage($phone, $finalMessage);

                $status = data_get($response, 'status') === 'success' ? 'sent' : 'failed';
                
                // Check for rate limiting error and adjust delay
                $responseBody = data_get($response, 'body', []);
                $isRateLimited = false;
                $retryAfter = null;
                
                if (is_array($responseBody)) {
                    $errorMessage = data_get($responseBody, 'message', '');
                    if (is_string($errorMessage) && 
                        (str_contains(strtolower($errorMessage), 'account protection') || 
                         str_contains(strtolower($errorMessage), 'rate limit'))) {
                        $isRateLimited = true;
                        $retryAfter = data_get($responseBody, 'retry_after');
                        if (is_numeric($retryAfter) && $retryAfter > $delayBetweenMessages) {
                            $delayBetweenMessages = (int) ceil($retryAfter);
                            \Log::info('WhatsApp rate limit detected, adjusting delay to ' . $delayBetweenMessages . ' seconds');
                        }
                    }
                }
                
                if ($isRateLimited && $status === 'failed') {
                    // Wait for the required time, then retry
                    $waitTime = $retryAfter ?? $delayBetweenMessages;
                    \Log::info("Rate limited, waiting {$waitTime} seconds before retry for {$phone}");
                    sleep((int) ceil($waitTime));
                    
                    // Retry the message
                    $response = $whatsAppService->sendMessage($phone, $finalMessage);
                    $status = data_get($response, 'status') === 'success' ? 'sent' : 'failed';
                }
                
                $status === 'sent' ? $sentCount++ : $failedCount++;
                if ($status !== 'sent') {
                    $failures[] = [
                        'phone' => $phone,
                        'reason' => $isRateLimited ? 'Rate limited (retried)' : (data_get($response, 'body') ?? 'unknown'),
                    ];
                }
                
                // Update last sent time after successful or failed send
                $lastSentTime = time();

                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $phone,
                    'channel'        => 'whatsapp',
                    'title'          => $title,
                    'message'        => $finalMessage,
                    'type'           => 'whatsapp',
                    'status'         => $status,
                    'response'       => $response,
                    'classroom_id'   => $entity->classroom_id ?? null,
                    'scope'          => 'whatsapp',
                    'sent_at'        => now(),
                    'provider_id'    => data_get($response, 'body.data.id') 
                                        ?? data_get($response, 'body.data.message.id')
                                        ?? data_get($response, 'body.messageId')
                                        ?? data_get($response, 'body.id'),
                    'provider_status'=> data_get($response, 'body.status') ?? data_get($response, 'status'),
                ]);
            } catch (\Throwable $e) {
                $failedCount++;
                $failures[] = [
                    'phone' => $phone,
                    'reason' => $e->getMessage(),
                ];
                CommunicationLog::create([
                    'recipient_type' => $data['target'],
                    'recipient_id'   => $entity->id ?? null,
                    'contact'        => $phone,
                    'channel'        => 'whatsapp',
                    'message'        => $message,
                    'type'           => 'whatsapp',
                    'status'         => 'failed',
                    'response'       => $e->getMessage(),
                    'scope'          => 'whatsapp',
                    'sent_at'        => now(),
                ]);
            }
        }

        $summary = "WhatsApp: sent {$sentCount}, failed {$failedCount}";
        if ($skipped) {
            $summary .= '. Skipped invalid/non-Kenyan: ' . implode(', ', array_slice($skipped, 0, 3)) . (count($skipped) > 3 ? '…' : '');
        }
        $flashType = ($failedCount > 0 || $skipped) ? 'warning' : 'success';
        $withData = [$flashType => $summary];
        if ($failedCount > 0) {
            $withData['error'] = 'Some sends failed. Sample: ' . json_encode($failures[0] ?? []);
        }

        return redirect()->route('communication.send.whatsapp')->with($withData);
    }

    /**
     * Show progress for bulk WhatsApp send
     */
    public function whatsappProgress(Request $request)
    {
        abort_unless(can_access("communication", "sms", "add") || can_access("communication", "email", "add"), 403);
        
        $trackingId = $request->query('tracking_id');
        if (!$trackingId) {
            return redirect()->route('communication.send.whatsapp')->with('error', 'Invalid tracking ID');
        }

        $progress = \Cache::get("bulk_whatsapp_progress:{$trackingId}", [
            'status' => 'processing',
            'total' => $request->query('total', 0),
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'processed' => 0,
        ]);

        return view('communication.whatsapp-progress', compact('trackingId', 'progress'));
    }

    /**
     * Retry failed WhatsApp sends
     */
    public function retryFailedWhatsApp(Request $request)
    {
        abort_unless(can_access("communication", "sms", "add") || can_access("communication", "email", "add"), 403);
        
        $trackingId = $request->input('tracking_id');
        if (!$trackingId) {
            return back()->with('error', 'Invalid tracking ID');
        }

        // Get failed logs from this tracking session
        $failedLogs = CommunicationLog::where('channel', 'whatsapp')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        if ($failedLogs->isEmpty()) {
            return back()->with('info', 'No failed messages to retry');
        }

        // Prepare recipients for retry
        $recipients = [];
        foreach ($failedLogs as $log) {
            $recipients[$log->contact] = [
                'id' => $log->recipient_id,
                'classroom_id' => $log->classroom_id,
                'type' => 'App\Models\Student', // Default, adjust if needed
            ];
        }

        // Create new tracking ID for retry
        $retryTrackingId = 'whatsapp_retry_' . uniqid() . '_' . time();

        // Dispatch retry job
        \App\Jobs\BulkSendWhatsAppMessages::dispatch(
            $retryTrackingId,
            $recipients,
            $failedLogs->first()->message ?? '',
            $failedLogs->first()->title ?? 'Retry',
            $failedLogs->first()->recipient_type ?? 'student',
            null,
            false // Don't skip sent for retries
        );

        return redirect()->route('communication.send.whatsapp.progress', [
            'tracking_id' => $retryTrackingId,
            'total' => count($recipients),
        ])->with('success', 'Retry job started for ' . count($recipients) . ' failed messages');
    }

    /* ========== PREVIEW ========== */
    public function preview(Request $request)
    {
        try {
            abort_unless(can_access("communication", "sms", "add") || can_access("communication", "email", "add"), 403);

            $data = $request->validate([
                'message' => 'required|string',
                'channel' => 'required|string|in:sms,whatsapp,email',
                'target' => 'required|string',
                'classroom_id' => 'nullable|integer',
                'student_id' => 'nullable|integer',
                'selected_student_ids' => 'nullable|string',
                'template_id' => 'nullable|exists:communication_templates,id',
            ]);

            // Try to get recipients, but handle DB errors gracefully
            try {
                $recipients = $this->collectRecipients($data, $data['channel']);
            } catch (\Exception $e) {
                \Log::warning('Preview: Could not collect recipients', ['error' => $e->getMessage()]);
                $recipients = [];
            }

            // Get first student entity from recipients
            $firstStudent = null;
            if (!empty($recipients)) {
                foreach ($recipients as $contact => $entity) {
                    if ($entity instanceof Student) {
                        $firstStudent = $entity;
                        break;
                    }
                }
            }

            // If no student found, try to get one based on target
            if (!$firstStudent) {
                try {
                    if ($data['target'] === 'student' && !empty($data['student_id'])) {
                        $firstStudent = Student::with(['family.updateLink', 'classroom', 'parent'])
                            ->where('archive', 0)
                            ->where('is_alumni', false)
                            ->find($data['student_id']);
                    } elseif ($data['target'] === 'class' && !empty($data['classroom_id'])) {
                        $firstStudent = Student::with(['family.updateLink', 'classroom', 'parent'])
                            ->where('archive', 0)
                            ->where('is_alumni', false)
                            ->where('classroom_id', $data['classroom_id'])
                            ->first();
                    } elseif ($data['target'] === 'specific_students' && !empty($data['selected_student_ids'])) {
                        $studentIds = array_filter(explode(',', $data['selected_student_ids']));
                        if (!empty($studentIds)) {
                            $firstStudent = Student::with(['family.updateLink', 'classroom', 'parent'])
                                ->where('archive', 0)
                                ->where('is_alumni', false)
                                ->whereIn('id', $studentIds)
                                ->first();
                        }
                    } else {
                        // Get any active student
                        $firstStudent = Student::with(['family.updateLink', 'classroom', 'parent'])
                            ->where('archive', 0)
                            ->where('is_alumni', false)
                            ->first();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Preview: Could not load student', ['error' => $e->getMessage()]);
                }
            } else {
                // Load relationships if not already loaded
                try {
                    $firstStudent->load(['family.updateLink', 'classroom', 'parent']);
                } catch (\Exception $e) {
                    \Log::warning('Preview: Could not load relationships', ['error' => $e->getMessage()]);
                }
            }

            // If still no student, create a mock student for preview
            if (!$firstStudent) {
                $firstStudent = new Student([
                    'id' => 0,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'admission_number' => 'ADM001',
                ]);
                $firstStudent->classroom = new \App\Models\Academics\Classroom(['name' => 'Form 1']);
            }

            // Replace placeholders (this doesn't require DB)
            $previewMessage = replace_placeholders($data['message'], $firstStudent);

            // Get parent contact info for display
            $parentName = 'Parent';
            $parentContact = '';
            
            try {
                if ($firstStudent->parent) {
                    $parentName = $firstStudent->parent->father_name
                                ?? $firstStudent->parent->guardian_name
                                ?? $firstStudent->parent->mother_name
                                ?? 'Parent';
                    
                    if ($data['channel'] === 'email') {
                        $parentContact = $firstStudent->parent->father_email 
                                      ?? $firstStudent->parent->mother_email 
                                      ?? $firstStudent->parent->guardian_email 
                                      ?? '';
                    } else {
                        $parentContact = $firstStudent->parent->father_phone 
                                      ?? $firstStudent->parent->mother_phone 
                                      ?? $firstStudent->parent->guardian_phone 
                                      ?? '';
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Preview: Could not get parent info', ['error' => $e->getMessage()]);
            }

            return view('communication.preview', [
                'message' => $previewMessage,
                'originalMessage' => $data['message'],
                'channel' => $data['channel'],
                'student' => $firstStudent,
                'parentName' => $parentName,
                'parentContact' => $parentContact,
                'formData' => $data, // Pass all form data for sending
            ]);
        } catch (\Exception $e) {
            \Log::error('Preview error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Preview failed: ' . $e->getMessage());
        }
    }

    /* ========== LOGS ========== */
    public function logs()
    {
        $logs = CommunicationLog::latest()->paginate(20);
        return view('communication.logs', compact('logs'));
    }

    public function logsScheduled()
    {
        $scheduled = ScheduledCommunication::latest()->paginate(20);
        // Normalize variable name for the blade view
        return view('communication.logs_scheduled', [
            'logs' => $scheduled,
        ]);
    }

    /* ========== RECIPIENT BUILDER ========== */
    private function collectRecipients(array $data, string $type): array
    {
        return CommunicationHelperService::collectRecipients($data, $type);
    }

    /**
     * Shared system placeholders for send screens (mirror template builder)
     */
    protected function getSystemPlaceholders(): array
    {
        return [
            // General
            ['key' => 'school_name',  'value' => setting('school_name') ?? 'School Name'],
            ['key' => 'school_phone', 'value' => setting('school_phone') ?? 'School Phone'],
            ['key' => 'school_email', 'value' => setting('school_email') ?? 'School Email'],
            ['key' => 'date',         'value' => now()->format('d M Y')],

            // Student & Parent
            ['key' => 'student_name', 'value' => "Student's full name"],
            ['key' => 'admission_number', 'value' => 'Student admission number'],
            ['key' => 'class_name',   'value' => 'Classroom name'],
            ['key' => 'parent_name',  'value' => "Parent's full name"],
            ['key' => 'father_name',  'value' => "Parent's full name"],
            ['key' => 'profile_update_link', 'value' => 'Profile update link for parents'],

            // Staff
            ['key' => 'staff_name',   'value' => 'Staff full name'],

            // Receipts
            ['key' => 'receipt_number', 'value' => 'Receipt number (e.g., RCPT-2024-001)'],
            ['key' => 'transaction_code', 'value' => 'Transaction code (e.g., TXN-20241217-ABC123)'],
            ['key' => 'payment_date', 'value' => 'Payment date (e.g., 17 Dec 2024)'],
            ['key' => 'amount', 'value' => 'Payment amount (e.g., 5,000.00)'],
            ['key' => 'receipt_link', 'value' => 'Public receipt link (10-char token)'],
            ['key' => 'carried_forward', 'value' => 'Carried forward amount (unallocated payment)'],

            // Invoices & Reminders
            ['key' => 'invoice_number', 'value' => 'Invoice number (e.g., INV-2024-001)'],
            ['key' => 'total_amount', 'value' => 'Total invoice amount'],
            ['key' => 'due_date', 'value' => 'Due date'],
            ['key' => 'outstanding_amount', 'value' => 'Outstanding balance amount'],
            ['key' => 'status', 'value' => 'Invoice status (paid, partial, unpaid)'],
            ['key' => 'invoice_link', 'value' => 'Public invoice link (10-char hash)'],
            ['key' => 'days_overdue', 'value' => 'Number of days overdue'],

            // Payment Plans
            ['key' => 'installment_count', 'value' => 'Number of installments'],
            ['key' => 'installment_amount', 'value' => 'Amount per installment'],
            ['key' => 'installment_number', 'value' => 'Current installment number'],
            ['key' => 'start_date', 'value' => 'Payment plan start date'],
            ['key' => 'end_date', 'value' => 'Payment plan end date'],
            ['key' => 'remaining_installments', 'value' => 'Remaining installments'],
            ['key' => 'payment_plan_link', 'value' => 'Public payment plan link (10-char hash)'],

            // Custom Finance
            ['key' => 'custom_message', 'value' => 'Custom message content'],
            ['key' => 'custom_subject', 'value' => 'Custom email subject'],
        ];
    }

    /**
     * Handle SMS Delivery Report (DLR) webhook from HostPinnacle
     * Matches exact parameter names from HostPinnacle webhook configuration
     */
    public function smsDeliveryReport(Request $request)
    {
        \Log::info('SMS DLR Webhook Received', ['data' => $request->all()]);

        // Required parameters (from screenshot)
        $transactionId = $request->input('transactionId');
        $messageId = $request->input('messageId');
        $mobileNo = $request->input('mobileNo');
        $errorCode = $request->input('errorCode');
        
        // Time parameters (long format - milliseconds)
        $receivedTime = $request->input('receivedTime'); // Long format
        $deliveredTime = $request->input('deliveredTime'); // Long format
        
        // Optional parameters (from screenshot)
        $status = strtolower($request->input('status', ''));
        $cause = $request->input('cause'); // Status description
        $senderName = $request->input('senderName');
        $length = $request->input('length');
        $channel = $request->input('channel');
        $text = $request->input('text');
        $cost = $request->input('cost');
        $msgType = $request->input('msgType');
        
        // String format timestamps (optional)
        $receivedTimeString = $request->input('receivedTimeString'); // YYYYMMDD HH:MM:SS
        $doneDateString = $request->input('doneDateString'); // YYYYMMDD HH:MM:SS
        
        // Use cause as errorCode if errorCode not provided
        if (!$errorCode && $cause) {
            $errorCode = $cause;
        }
        
        // Convert long timestamp to Carbon if provided
        $deliveredAt = null;
        if ($deliveredTime) {
            try {
                // Convert milliseconds to seconds
                $deliveredAt = \Carbon\Carbon::createFromTimestamp($deliveredTime / 1000);
            } catch (\Exception $e) {
                // Try string format if long format fails
                if ($doneDateString) {
                    try {
                        $deliveredAt = \Carbon\Carbon::createFromFormat('Ymd H:i:s', $doneDateString);
                    } catch (\Exception $e2) {
                        \Log::warning('Failed to parse delivered time', [
                            'deliveredTime' => $deliveredTime,
                            'doneDateString' => $doneDateString
                        ]);
                    }
                }
            }
        }

        // Try to find log by transactionId first (HostPinnacle primary identifier)
        $log = null;
        if ($transactionId) {
            $log = \App\Models\CommunicationLog::where('provider_id', $transactionId)->first();
        }
        
        // Fallback to messageId if transactionId not found
        if (!$log && $messageId) {
            $log = \App\Models\CommunicationLog::where('provider_id', $messageId)->first();
        }

        if (!$log) {
            \Log::warning('SMS DLR webhook: Log not found', [
                'transactionId' => $transactionId,
                'messageId' => $messageId,
                'mobileNo' => $mobileNo
            ]);
            return response()->json(['ok' => false, 'reason' => 'log not found'], 404);
        }

        // Map HostPinnacle statuses to app statuses
        // Use 'status' field if provided, otherwise infer from 'cause'
        $finalStatus = $status;
        if (!$finalStatus && $cause) {
            // Infer status from cause
            if (stripos($cause, 'delivered') !== false) {
                $finalStatus = 'delivered';
            } elseif (stripos($cause, 'failed') !== false || stripos($cause, 'undelivered') !== false) {
                $finalStatus = 'failed';
            }
        }
        
        $map = [
            'delivered'   => 'sent',
            'success'     => 'sent',
            'sent'        => 'sent',
            'queued'      => 'pending',
            'pending'     => 'pending',
            'undelivered' => 'failed',
            'failed'      => 'failed',
            'blacklisted' => 'failed',
            'rejected'    => 'failed',
            'expired'     => 'failed',
        ];
        $appStatus = $map[strtolower($finalStatus)] ?? $log->status;

        $updateData = [
            'status'          => $appStatus,
            'provider_status' => $finalStatus ?: $log->provider_status,
        ];

        if ($deliveredAt) {
            $updateData['delivered_at'] = $deliveredAt;
        }

        if ($errorCode) {
            $updateData['error_code'] = $errorCode;
        }

        $log->update($updateData);

        \Log::info('SMS DLR Updated', [
            'log_id' => $log->id,
            'status' => $appStatus,
            'transactionId' => $transactionId,
            'cause' => $cause,
            'delivered_at' => $deliveredAt
        ]);

        return response()->json(['ok' => true]);
    }
}
