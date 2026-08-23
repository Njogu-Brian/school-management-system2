<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected ?string $accessToken;
    protected ?string $phoneNumberId;
    protected ?string $businessAccountId;
    protected string $apiVersion;
    protected ?string $webhookVerifyToken;
    protected ?string $defaultTemplate;
    protected string $defaultTemplateLanguage;

    public function __construct()
    {
        $config = config('services.whatsapp');

        $this->accessToken = $config['access_token'] ?? null;
        $this->phoneNumberId = $config['phone_number_id'] ?? null;
        $this->businessAccountId = $config['business_account_id'] ?? null;
        $this->apiVersion = $config['api_version'] ?? 'v21.0';
        $this->webhookVerifyToken = $config['webhook_verify_token'] ?? null;
        $this->defaultTemplate = $config['default_template'] ?? null;
        $this->defaultTemplateLanguage = $config['default_template_language'] ?? 'en_US';
    }

    /**
     * Send a WhatsApp message via Meta Cloud API.
     *
     * Uses the configured default template when set (required for business-initiated
     * messages outside the 24-hour customer service window). Otherwise sends free text.
     *
     * @param int|null $delaySeconds Optional delay before sending (for rate limiting)
     */
    public function sendMessage(string $to, string $text, ?int $delaySeconds = null): array
    {
        if ($delaySeconds && $delaySeconds > 0) {
            sleep($delaySeconds);
        }

        if (! $this->isValidRecipient($to)) {
            Log::warning('WhatsApp send skipped: invalid recipient number', [
                'to' => $to,
                'normalized' => $this->normalizeRecipient($to),
            ]);

            return [
                'status' => 'error',
                'http_status' => null,
                'body' => [
                    'error' => [
                        'message' => 'Invalid WhatsApp recipient number',
                        'code' => 'invalid_recipient',
                    ],
                ],
                'message_id' => null,
            ];
        }

        if (! $this->defaultTemplate) {
            Log::warning('WhatsApp sending session text without WHATSAPP_DEFAULT_TEMPLATE; Meta will reject business-initiated messages outside the 24-hour window', [
                'to' => $this->normalizeRecipient($to),
            ]);
        }

        if ($this->defaultTemplate) {
            $components = [];
            if (!in_array($this->defaultTemplate, ['hello_world'], true)) {
                $components = [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $this->truncateForTemplate($text)],
                        ],
                    ],
                ];
            }

            return $this->sendTemplateMessage(
                $to,
                $this->defaultTemplate,
                $this->defaultTemplateLanguage,
                $components
            );
        }

        return $this->sendTextMessage($to, $text);
    }

    /**
     * Send a free-text message (only works within the 24-hour customer service window).
     */
    public function sendTextMessage(string $to, string $text): array
    {
        return $this->sendRequest([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizeRecipient($to),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text,
            ],
        ]);
    }

    /**
     * Send an approved template message.
     *
     * @param  array<int, array<string, mixed>>  $components
     */
    public function sendTemplateMessage(
        string $to,
        string $templateName,
        string $languageCode = 'en_US',
        array $components = []
    ): array {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizeRecipient($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
            ],
        ];

        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        return $this->sendRequest($payload);
    }

    /**
     * Check API connectivity and token validity.
     */
    public function status(): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->get($this->graphUrl($this->phoneNumberId), [
                    'fields' => 'display_phone_number,verified_name,quality_rating,messaging_limit_tier',
                ]);

            $body = $response->json();

            return [
                'status' => $response->successful() ? 'success' : 'error',
                'http_status' => $response->status(),
                'body' => $body ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Meta WhatsApp status check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'error',
                'http_status' => null,
                'body' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate Meta webhook verify token (GET subscription handshake).
     */
    public function validateWebhookVerifyToken(?string $provided): bool
    {
        if (!$this->webhookVerifyToken) {
            return false;
        }

        return hash_equals($this->webhookVerifyToken, (string) $provided);
    }

    /**
     * @deprecated Wasender-only; Meta Cloud API does not use session tokens.
     */
    public function validateWebhookToken(?string $provided): bool
    {
        return $this->validateWebhookVerifyToken($provided);
    }

    /**
     * @deprecated Meta Cloud API does not use QR sessions.
     */
    public function listSessions(): array
    {
        return [
            'status' => 'success',
            'http_status' => 200,
            'body' => [
                'data' => [],
                'message' => 'Meta WhatsApp Cloud API does not use sessions. Check WhatsApp Setup for connection status.',
            ],
        ];
    }

    /** @deprecated Meta Cloud API does not use QR sessions. */
    public function createSession(array $payload): array
    {
        return $this->deprecatedSessionResponse('create');
    }

    /** @deprecated Meta Cloud API does not use QR sessions. */
    public function getSession(int|string $sessionId): array
    {
        return $this->deprecatedSessionResponse('get');
    }

    /** @deprecated Meta Cloud API does not use QR sessions. */
    public function updateSession(int|string $sessionId, array $payload): array
    {
        return $this->deprecatedSessionResponse('update');
    }

    /** @deprecated Meta Cloud API does not use QR sessions. */
    public function deleteSession(int|string $sessionId): array
    {
        return $this->deprecatedSessionResponse('delete');
    }

    /** @deprecated Meta Cloud API does not use QR sessions. */
    public function connectSession(int|string $sessionId): array
    {
        return $this->deprecatedSessionResponse('connect');
    }

    /** @deprecated Meta Cloud API does not use QR sessions. */
    public function restartSession(int|string $sessionId): array
    {
        return $this->deprecatedSessionResponse('restart');
    }

    /** @deprecated Meta Cloud API does not use QR sessions. */
    public function messageLogs(int|string $sessionId, int $page = 1, int $perPage = 20): array
    {
        return $this->deprecatedSessionResponse('message-logs');
    }

    public function phoneNumberId(): ?string
    {
        return $this->phoneNumberId;
    }

    public function businessAccountId(): ?string
    {
        return $this->businessAccountId;
    }

    public function webhookUrl(): string
    {
        return route('webhooks.whatsapp.meta');
    }

    protected function sendRequest(array $payload): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->post($this->graphUrl($this->phoneNumberId . '/messages'), $payload);

            $body = $response->json();
            $successful = $response->successful() && !isset($body['error']);

            $result = [
                'status' => $successful ? 'success' : 'error',
                'http_status' => $response->status(),
                'body' => $body ?? $response->body(),
                'message_id' => data_get($body, 'messages.0.id'),
            ];

            Log::info('Meta WhatsApp send response', [
                'to' => $payload['to'] ?? null,
                'type' => $payload['type'] ?? null,
                'status' => $result['status'],
                'http_status' => $result['http_status'],
                'message_id' => $result['message_id'],
                'error' => data_get($body, 'error.message'),
                'error_code' => data_get($body, 'error.code'),
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('Meta WhatsApp send failed', [
                'to' => $payload['to'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'http_status' => null,
                'body' => ['error' => ['message' => $e->getMessage()]],
                'message_id' => null,
            ];
        }
    }

    protected function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/' . $this->apiVersion . '/' . ltrim($path, '/');
    }

    protected function ensureConfigured(): void
    {
        if (!$this->accessToken) {
            throw new \RuntimeException('WhatsApp access token is not configured (WHATSAPP_ACCESS_TOKEN).');
        }

        if (!$this->phoneNumberId) {
            throw new \RuntimeException('WhatsApp phone number ID is not configured (WHATSAPP_PHONE_NUMBER_ID).');
        }
    }

    public function isValidRecipient(string $number): bool
    {
        return (bool) preg_match('/^254[17]\d{8}$/', $this->normalizeRecipient($number));
    }

    public function normalizeRecipient(string $number): string
    {
        $clean = preg_replace('/[^\d+]/', '', $number);
        $digits = ltrim((string) $clean, '+');

        // Local Kenyan mobiles: 07xxxxxxxx / 01xxxxxxxx (or without leading 0)
        if (preg_match('/^0([17]\d{8})$/', $digits, $matches)) {
            return '254' . $matches[1];
        }

        if (preg_match('/^([17]\d{8})$/', $digits)) {
            return '254' . $digits;
        }

        return $digits;
    }

    protected function truncateForTemplate(string $text, int $maxLength = 1024): string
    {
        // Meta rejects template body params with newlines/tabs (error 132018).
        $text = preg_replace('/[\r\n\t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/ {2,}/', ' ', $text) ?? $text;
        $text = trim($text);

        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength - 3) . '...';
    }

    protected function deprecatedSessionResponse(string $action): array
    {
        return [
            'status' => 'error',
            'http_status' => 400,
            'body' => [
                'message' => "Wasender session {$action} is no longer supported. This system uses Meta WhatsApp Cloud API.",
            ],
        ];
    }
}
