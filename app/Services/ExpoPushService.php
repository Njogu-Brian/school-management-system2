<?php

namespace App\Services;

use App\Models\Announcement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExpoPushService
{
    private const PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    private const CHUNK = 100;

    /**
     * Send a push for a new or newly-activated announcement to all registered Expo device tokens.
     */
    public function sendAnnouncementNotification(Announcement $announcement): void
    {
        $tokens = DB::table('user_device_tokens')
            ->distinct()
            ->pluck('token')
            ->filter(fn ($t) => is_string($t) && $t !== '')
            ->values()
            ->all();

        if ($tokens === []) {
            return;
        }

        $title = Str::limit($announcement->title, 100);
        $body = Str::limit(trim(strip_tags($announcement->content)), 160);

        foreach (array_chunk($tokens, self::CHUNK) as $chunk) {
            $messages = [];
            foreach ($chunk as $token) {
                $messages[] = [
                    'to' => $token,
                    'title' => $title,
                    'body' => $body !== '' ? $body : 'New announcement',
                    'sound' => 'default',
                    'data' => [
                        'type' => 'announcement',
                        'announcement_id' => $announcement->id,
                    ],
                ];
            }

            $this->postMessages($messages);
        }
    }

    /**
     * Send a custom notification to the given tokens.
     *
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>  $data  Optional data payload delivered with the notification.
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        $tokens = array_values(array_filter(array_unique($tokens), fn ($t) => is_string($t) && $t !== ''));
        if ($tokens === []) {
            return;
        }
        $title = Str::limit($title, 100);
        $body = Str::limit($body, 160);

        foreach (array_chunk($tokens, self::CHUNK) as $chunk) {
            $messages = [];
            foreach ($chunk as $token) {
                $messages[] = [
                    'to' => $token,
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'data' => $data,
                ];
            }
            $this->postMessages($messages);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function postMessages(array $messages): void
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $accessToken = config('services.expo.access_token');
        if (is_string($accessToken) && $accessToken !== '') {
            $headers['Authorization'] = 'Bearer '.$accessToken;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post(self::PUSH_URL, ['messages' => $messages]);

            if (! $response->successful()) {
                Log::warning('Expo push send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Expo push send exception', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
