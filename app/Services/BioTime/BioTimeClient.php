<?php

namespace App\Services\BioTime;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BioTimeClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly int $timeout = 25,
    ) {}

    public static function fromConfig(): self
    {
        $base = (string) config('biotime.base_url');
        if ($base === '') {
            throw new RuntimeException('BIOTIME_BASE_URL is not set.');
        }

        return new self(
            $base,
            (string) config('biotime.username'),
            (string) config('biotime.password'),
            (int) config('biotime.timeout', 25),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function transactions(?string $startTime = null, ?string $endTime = null): array
    {
        $token = $this->token();
        $page = 1;
        $all = [];

        do {
            $query = [
                'page' => $page,
                'page_size' => 200,
                'limit' => 200,
            ];
            if ($startTime) {
                $query['start_time'] = $startTime;
            }
            if ($endTime) {
                $query['end_time'] = $endTime;
            }

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->withToken($token, 'JWT')
                ->get($this->baseUrl.'/iclock/api/transactions/', $query);

            if (! $response->successful()) {
                throw new RuntimeException('BioTime transactions failed: HTTP '.$response->status().' '.$response->body());
            }

            $payload = $response->json();
            $rows = $payload['data'] ?? $payload['results'] ?? [];
            if (! is_array($rows)) {
                $rows = [];
            }
            $all = array_merge($all, $rows);
            $next = $payload['next'] ?? null;
            $page++;
        } while ($next || (count($rows) >= 200 && $page < 50));

        return $all;
    }

    private function token(): string
    {
        $endpoints = ['/jwt-api-token-auth/', '/api-token-auth/'];
        $lastBody = '';

        foreach ($endpoints as $path) {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl.$path, [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);
            $lastBody = $response->body();
            if (! $response->successful()) {
                continue;
            }
            $token = $response->json('token') ?? $response->json('data.token');
            if (is_string($token) && $token !== '') {
                return $token;
            }
        }

        Log::warning('BioTime auth failed', ['body' => $lastBody]);
        throw new RuntimeException('Could not obtain BioTime API token. Check username/password and web port.');
    }
}
