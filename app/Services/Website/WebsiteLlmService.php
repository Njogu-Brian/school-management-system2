<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Website-only LLM wrapper — does not modify core ERP LLMService (curriculum AI).
 */
class WebsiteLlmService
{
    public function generate(string $prompt, array $options = []): ?string
    {
        $provider = config('curriculum_ai.llm_provider', 'openai');
        $config = config("curriculum_ai.llm.{$provider}", []);

        return match ($provider) {
            'openai' => $this->generateOpenAI($prompt, $options, $config),
            'local' => $this->generateLocal($prompt, $options, $config),
            default => $this->generateOpenAI($prompt, $options, $config),
        };
    }

    protected function generateOpenAI(string $prompt, array $options, array $config): ?string
    {
        $apiKey = config('curriculum_ai.openai.api_key');
        if (! $apiKey) {
            Log::warning('Website AI: OpenAI API key not configured');

            return null;
        }

        try {
            $response = Http::timeout(config('curriculum_ai.openai.timeout', 30))
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $options['model'] ?? $config['model'] ?? 'gpt-4-turbo-preview',
                    'messages' => [
                        ['role' => 'system', 'content' => $options['system_prompt'] ?? 'You write for Royal Kings Education Centre public website.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $options['temperature'] ?? $config['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? $config['max_tokens'] ?? 2000,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
        } catch (\Throwable $e) {
            Log::error('Website AI generation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function generateLocal(string $prompt, array $options, array $config): ?string
    {
        $endpoint = $config['endpoint'] ?? 'http://localhost:8000/v1/chat/completions';

        try {
            $response = Http::timeout(120)->post($endpoint, [
                'model' => $options['model'] ?? $config['model'] ?? 'local-llm',
                'messages' => [
                    ['role' => 'system', 'content' => $options['system_prompt'] ?? 'You write for Royal Kings Education Centre.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2000,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
        } catch (\Throwable $e) {
            Log::error('Website local LLM failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
