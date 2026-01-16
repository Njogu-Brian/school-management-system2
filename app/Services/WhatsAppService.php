<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected ?string $webhookToken;
    protected ?string $personalAccessToken;

    public function __construct()
    {
        $this->apiKey = config('services.wasender.api_key');
        $this->baseUrl = rtrim(config('services.wasender.base_url', 'https://www.wasenderapi.com/api'), '/');
        $this->webhookToken = config('services.wasender.webhook_token');
        $this->personalAccessToken = config('services.wasender.personal_access_token');
    }

    /**
    * Send a text message via WasenderAPI.
    * @param int|null $delaySeconds Optional delay before sending (for rate limiting)
    */
    public function sendMessage(string $to, string $text, ?int $delaySeconds = null): array
    {
        $this->ensureApiKey();

        // Apply delay if specified (for rate limiting)
        if ($delaySeconds && $delaySeconds > 0) {
            sleep($delaySeconds);
        }

        $payload = [
            'to' => $this->normalizeRecipient($to),
            'text' => $text,
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->post($this->baseUrl . '/send-message', $payload);

            $body = $response->json();
            $result = [
                'status' => $response->successful() ? 'success' : 'error',
                'http_status' => $response->status(),
                'body' => $body ?? $response->body(),
            ];

            Log::info('Wasender send-message response', [
                'to' => $payload['to'],
                'status' => $result['status'],
                'http_status' => $result['http_status'],
                'body' => $result['body'],
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('Wasender send-message failed', [
                'to' => $payload['to'],
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'http_status' => null,
                'body' => $e->getMessage(),
            ];
        }
    }

    /**
    * Retrieve sessions for troubleshooting / monitoring.
    */
    public function listSessions(): array
    {
        $this->ensurePersonalAccessToken();

        try {
            $response = Http::withToken($this->personalAccessToken)
                ->acceptJson()
                ->get($this->baseUrl . '/whatsapp-sessions');

            $body = $response->json();

            return [
                'status' => $response->successful() ? 'success' : 'error',
                'http_status' => $response->status(),
                'body' => $body ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Wasender list-sessions failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'http_status' => null,
                'body' => $e->getMessage(),
            ];
        }
    }

    /**
    * Optional webhook token check.
    */
    public function validateWebhookToken(?string $provided): bool
    {
        if (!$this->webhookToken) {
            return true;
        }

        return hash_equals($this->webhookToken, (string) $provided);
    }

    protected function ensureApiKey(): void
    {
        if (!$this->apiKey) {
            throw new \RuntimeException('Wasender API key is not configured.');
        }
    }

    protected function ensurePersonalAccessToken(): void
    {
        if (!$this->personalAccessToken) {
            throw new \RuntimeException('Wasender Personal Access Token is not configured.');
        }
    }

    /**
    * Basic recipient cleanup: keep digits, drop leading plus for API consistency.
    */
    protected function normalizeRecipient(string $number): string
    {
        $clean = preg_replace('/[^\d+]/', '', $number);
        return ltrim($clean, '+');
    }

    /* ========= Account-level (PAT) session management ========= */
    public function createSession(array $payload): array
    {
        return $this->requestPat('post', '/whatsapp-sessions', $payload);
    }

    public function getSession(int|string $sessionId): array
    {
        return $this->requestPat('get', "/whatsapp-sessions/{$sessionId}");
    }

    public function updateSession(int|string $sessionId, array $payload): array
    {
        return $this->requestPat('put', "/whatsapp-sessions/{$sessionId}", $payload);
    }

    public function deleteSession(int|string $sessionId): array
    {
        return $this->requestPat('delete', "/whatsapp-sessions/{$sessionId}");
    }

    public function connectSession(int|string $sessionId): array
    {
        return $this->requestPat('post', "/whatsapp-sessions/{$sessionId}/connect");
    }

    public function restartSession(int|string $sessionId): array
    {
        return $this->requestPat('post', "/whatsapp-sessions/{$sessionId}/restart");
    }

    public function messageLogs(int|string $sessionId, int $page = 1, int $perPage = 20): array
    {
        return $this->requestPat('get', "/whatsapp-sessions/{$sessionId}/message-logs", [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function status(): array
    {
        $this->ensureApiKey();
        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->get($this->baseUrl . '/status');

            return [
                'status' => $response->successful() ? 'success' : 'error',
                'http_status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Wasender status check failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'http_status' => null,
                'body' => $e->getMessage(),
            ];
        }
    }

    protected function requestPat(string $method, string $path, array $payload = []): array
    {
        $this->ensurePersonalAccessToken();

        try {
            $http = Http::withToken($this->personalAccessToken)->acceptJson();
            $url = $this->baseUrl . $path;

            $response = match (strtolower($method)) {
                'post'   => $http->post($url, $payload),
                'put'    => $http->put($url, $payload),
                'delete' => $http->delete($url),
                default  => $http->get($url, $payload),
            };

            return [
                'status' => $response->successful() ? 'success' : 'error',
                'http_status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Wasender PAT request failed', [
                'method' => $method,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return [
                'status' => 'error',
                'http_status' => null,
                'body' => $e->getMessage(),
            ];
        }
    }
}


