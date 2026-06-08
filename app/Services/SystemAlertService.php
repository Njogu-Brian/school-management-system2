<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemAlertService
{
    public const DEDUP_HOURS = 6;

    public const ESCALATION_MINUTES = 30;

    public static function shouldReportException(\Throwable $e): bool
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Permission denied')
            && str_contains($message, 'storage/framework/views')) {
            return false;
        }

        return true;
    }

    public static function fingerprintForException(\Throwable $e): string
    {
        if ($e instanceof \Illuminate\Database\QueryException) {
            if (preg_match('/CONSTRAINT `([^`]+)`/', $e->getMessage(), $match)) {
                return 'exception_sql_'.sha1(get_class($e).'|'.$match[1]);
            }

            if (preg_match('/insert into `([^`]+)`/', $e->getMessage(), $match)) {
                return 'exception_sql_'.sha1(get_class($e).'|'.$match[1]);
            }
        }

        $normalized = preg_replace('/\b[A-Za-z0-9]{16,}\b/', '<dyn>', $e->getMessage()) ?? $e->getMessage();
        $normalized = preg_replace('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', '<ts>', $normalized) ?? $normalized;

        return 'exception_'.sha1(get_class($e).'|'.$e->getFile().'|'.$e->getLine().'|'.$normalized);
    }

    /**
     * Raise an actionable alert for all Super Admin users and send push notifications.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function raise(
        string $title,
        string $message,
        string $category = 'system',
        string $severity = 'critical',
        ?string $fingerprint = null,
        ?string $deepLink = null,
        array $metadata = [],
        bool $push = true,
        bool $escalate = true,
    ): void {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $fingerprint = $fingerprint ?: sha1($category.'|'.$title.'|'.$message);
        if ($this->hasRecentDuplicate($fingerprint)) {
            return;
        }

        $payload = [
            'title' => Str::limit($title, 180),
            'body' => Str::limit($message, 500),
            'message' => Str::limit($message, 500),
            'category' => $category,
            'severity' => $severity,
            'source_module' => $category,
            'deep_link' => $deepLink,
            'fingerprint' => $fingerprint,
            'requires_action' => true,
            'escalated_at' => null,
            'metadata' => $metadata,
        ];

        $superAdmins = $this->getSuperAdmins();
        if ($superAdmins->isEmpty()) {
            return;
        }

        foreach ($superAdmins as $admin) {
            $admin->notify(new SystemAlertNotification($payload));
        }

        $this->audit('system_alert_raised', $title.' — '.$message, null, [
            'category' => $category,
            'severity' => $severity,
            'fingerprint' => $fingerprint,
            'deep_link' => $deepLink,
            'metadata' => $metadata,
        ]);

        if ($push) {
            $this->pushToSuperAdmins(
                $payload['title'],
                $payload['body'],
                [
                    'type' => 'system_alert',
                    'category' => $category,
                    'severity' => $severity,
                    'deep_link' => $deepLink,
                    'fingerprint' => $fingerprint,
                ]
            );
        }

        if ($escalate && in_array($severity, ['critical', 'warning'], true)) {
            $this->escalateViaEmailAndSms(
                $payload['title'],
                $payload['body'],
                $deepLink,
                'escalation_'.$fingerprint
            );
        }
    }

    public function raiseSmsCreditsAlert(float $balance = 0, ?string $trigger = null): void
    {
        $this->raise(
            title: 'SMS credits exhausted',
            message: 'Outbound SMS is paused because the account has insufficient credits.'
                .($balance > 0 ? ' Balance: '.number_format($balance, 0).'.' : '')
                .($trigger ? ' Trigger: '.$trigger.'.' : '')
                .' Top up credits and resume communications from the queues page.',
            category: 'communication',
            severity: 'critical',
            fingerprint: 'sms_insufficient_credits',
            deepLink: '/communication/queues',
            metadata: [
                'balance' => $balance,
                'trigger' => $trigger,
            ],
        );
    }

    public function raiseSmsLowBalanceAlert(float $balance, float $threshold): void
    {
        $this->raise(
            title: 'SMS credits running low',
            message: 'SMS balance is '.number_format($balance, 0).' credits (threshold: '.number_format($threshold, 0).'). Top up soon to avoid communication pauses.',
            category: 'communication',
            severity: 'warning',
            fingerprint: 'sms_low_balance',
            deepLink: '/communication/queues',
            metadata: [
                'balance' => $balance,
                'threshold' => $threshold,
            ],
        );

        $this->audit('sms_balance_low', 'SMS balance low: '.number_format($balance, 0).' credits', null, [
            'balance' => $balance,
            'threshold' => $threshold,
        ]);
    }

    public function raiseQueueFailureAlert(string $jobName, string $error, ?string $uuid = null): void
    {
        $this->raise(
            title: 'Background job failed',
            message: 'Job '.$jobName.' failed: '.Str::limit($error, 220),
            category: 'system',
            severity: 'critical',
            fingerprint: 'queue_fail_'.sha1($jobName.'|'.Str::limit($error, 120)),
            deepLink: '/communication/queues',
            metadata: [
                'job' => $jobName,
                'uuid' => $uuid,
                'error' => Str::limit($error, 500),
            ],
        );

        $this->audit('queue_job_failed', 'Queue job failed: '.$jobName, null, [
            'job' => $jobName,
            'uuid' => $uuid,
            'error' => Str::limit($error, 500),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function raiseProcessingError(
        string $title,
        string $message,
        string $category = 'system',
        ?string $fingerprint = null,
        ?string $deepLink = null,
        array $context = [],
    ): void {
        $this->raise(
            title: $title,
            message: $message,
            category: $category,
            severity: 'critical',
            fingerprint: $fingerprint,
            deepLink: $deepLink,
            metadata: $context,
        );
    }

    public function escalateUnacknowledgedAlerts(int $minutes = self::ESCALATION_MINUTES): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        $cutoff = now()->subMinutes(max(5, $minutes));
        $rows = DB::table('notifications')
            ->where('created_at', '<=', $cutoff)
            ->where('data->requires_action', true)
            ->whereNull('data->acknowledged_at')
            ->whereNull('data->escalated_at')
            ->orderByDesc('created_at')
            ->get();

        $groups = [];
        foreach ($rows as $row) {
            $data = json_decode((string) $row->data, true) ?: [];
            $fingerprint = $data['fingerprint'] ?? $row->id;
            if (isset($groups[$fingerprint])) {
                continue;
            }
            $groups[$fingerprint] = $data;
        }

        $count = 0;
        foreach ($groups as $fingerprint => $data) {
            $title = (string) ($data['title'] ?? 'System alert');
            $body = (string) ($data['body'] ?? $data['message'] ?? '');
            $deepLink = $data['deep_link'] ?? null;

            $this->escalateViaEmailAndSms(
                '[Reminder] '.$title,
                'Unacknowledged alert ('.$minutes.'+ min): '.$body,
                is_string($deepLink) ? $deepLink : null,
                'escalation_reminder_'.$fingerprint
            );

            DB::table('notifications')
                ->where('data->fingerprint', $fingerprint)
                ->whereNull('data->acknowledged_at')
                ->orderBy('id')
                ->chunkById(50, function ($chunk) {
                    foreach ($chunk as $row) {
                        $payload = json_decode((string) $row->data, true) ?: [];
                        $payload['escalated_at'] = now()->toIso8601String();
                        DB::table('notifications')->where('id', $row->id)->update([
                            'data' => json_encode($payload),
                            'updated_at' => now(),
                        ]);
                    }
                });

            $this->audit('system_alert_escalated', 'Re-escalated unacknowledged alert: '.$title, null, [
                'fingerprint' => $fingerprint,
                'minutes' => $minutes,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * @return Collection<int, User>
     */
    public function getSuperAdmins(): Collection
    {
        return User::query()
            ->with('staff')
            ->whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function getSuperAdminPushTokens(): array
    {
        if (! Schema::hasTable('user_device_tokens')) {
            return [];
        }

        $adminIds = $this->getSuperAdmins()->pluck('id')->all();
        if ($adminIds === []) {
            return [];
        }

        return DB::table('user_device_tokens')
            ->whereIn('user_id', $adminIds)
            ->distinct()
            ->pluck('token')
            ->filter(fn ($t) => is_string($t) && $t !== '')
            ->values()
            ->all();
    }

    public function acknowledge(User $user, string $notificationId): bool
    {
        if (! Schema::hasTable('notifications')) {
            return false;
        }

        $notification = $user->notifications()->where('id', $notificationId)->first();
        if (! $notification) {
            return false;
        }

        $data = is_string($notification->data)
            ? json_decode($notification->data, true)
            : (array) $notification->data;

        $data['acknowledged_at'] = now()->toIso8601String();
        $data['acknowledged_by'] = $user->id;

        $notification->forceFill([
            'data' => $data,
            'read_at' => $notification->read_at ?? now(),
        ])->save();

        $this->audit('system_alert_acknowledged', 'Acknowledged alert: '.($data['title'] ?? $notificationId), $user->id, [
            'notification_id' => $notificationId,
            'fingerprint' => $data['fingerprint'] ?? null,
            'title' => $data['title'] ?? null,
        ]);

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingAlertsFor(User $user, int $limit = 20): array
    {
        if (! Schema::hasTable('notifications')) {
            return [];
        }

        return $user->notifications()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->filter(function ($notification) {
                $data = is_string($notification->data)
                    ? json_decode($notification->data, true)
                    : (array) $notification->data;

                if (! ($data['requires_action'] ?? false)) {
                    return false;
                }

                return empty($data['acknowledged_at']);
            })
            ->map(fn ($notification) => $this->formatAlert($notification))
            ->values()
            ->all();
    }

    public function pendingCountFor(User $user): int
    {
        return count($this->pendingAlertsFor($user, 100));
    }

    /**
     * @return array<string, mixed>
     */
    public function formatAlert($notification): array
    {
        $payload = is_string($notification->data)
            ? json_decode($notification->data, true)
            : (array) $notification->data;

        $title = $payload['title'] ?? $payload['subject'] ?? 'System alert';
        $body = $payload['body'] ?? $payload['message'] ?? '';

        return [
            'id' => $notification->id,
            'title' => (string) $title,
            'body' => (string) $body,
            'category' => (string) ($payload['category'] ?? 'system'),
            'severity' => (string) ($payload['severity'] ?? 'warning'),
            'deep_link' => $payload['deep_link'] ?? null,
            'requires_action' => (bool) ($payload['requires_action'] ?? false),
            'is_acknowledged' => ! empty($payload['acknowledged_at']),
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String() ?? '',
            'metadata' => $payload['metadata'] ?? [],
        ];
    }

    private function hasRecentDuplicate(string $fingerprint): bool
    {
        $since = now()->subHours(self::DEDUP_HOURS);

        return DB::table('notifications')
            ->where('created_at', '>=', $since)
            ->where('data->fingerprint', $fingerprint)
            ->whereNull('data->acknowledged_at')
            ->exists();
    }

    private function hasRecentEscalation(string $escalationFingerprint): bool
    {
        return Cache::has('system_alert_escalation:'.$escalationFingerprint);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pushToSuperAdmins(string $title, string $body, array $data = []): void
    {
        $tokens = $this->getSuperAdminPushTokens();
        if ($tokens === []) {
            return;
        }

        app(ExpoPushService::class)->sendToTokens($tokens, $title, $body, $data);
    }

    private function escalateViaEmailAndSms(
        string $title,
        string $body,
        ?string $deepLink,
        string $escalationFingerprint,
    ): void {
        if ($this->hasRecentEscalation($escalationFingerprint)) {
            return;
        }

        $actionUrl = $deepLink ? url($deepLink) : url('/');
        $smsBody = Str::limit($title.': '.$body, 155);
        $smsService = app(SMSService::class);

        foreach ($this->getSuperAdmins() as $admin) {
            if ($admin->email) {
                try {
                    Mail::send('emails.system-alert', [
                        'subject' => $title,
                        'body' => $body,
                        'actionUrl' => $actionUrl,
                    ], function ($message) use ($admin, $title) {
                        $message->to($admin->email)->subject('[ERP Alert] '.$title);
                    });
                } catch (\Throwable $e) {
                    Log::warning('System alert escalation email failed', [
                        'user_id' => $admin->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $phone = $admin->staff?->phone_number;
            if ($phone) {
                try {
                    $smsService->sendSMS($phone, $smsBody);
                } catch (\Throwable $e) {
                    Log::warning('System alert escalation SMS failed', [
                        'user_id' => $admin->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->audit('system_alert_escalation_sent', 'Escalated alert via email/SMS: '.$title, null, [
            'fingerprint' => $escalationFingerprint,
            'deep_link' => $deepLink,
        ]);

        Cache::put('system_alert_escalation:'.$escalationFingerprint, true, now()->addHours(self::DEDUP_HOURS));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function audit(string $action, string $description, ?int $userId = null, array $context = []): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        try {
            ActivityLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => $action,
                'model_type' => null,
                'model_id' => null,
                'description' => Str::limit($description, 500),
                'new_values' => $context,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'route' => request()?->route()?->getName(),
                'method' => request()?->method(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('System alert audit log failed', ['error' => $e->getMessage()]);
        }
    }
}
