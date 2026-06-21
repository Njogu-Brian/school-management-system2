<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LLMService
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('curriculum_ai.llm_provider', 'openai');
        $this->config = config("curriculum_ai.llm.{$this->provider}", []);
    }

    /**
     * Generate content using LLM
     *
     * @param string $prompt
     * @param array $options
     * @return string|null
     */
    public function generate(string $prompt, array $options = []): ?string
    {
        return match ($this->provider) {
            'openai' => $this->generateOpenAI($prompt, $options),
            'hf' => $this->generateHuggingFace($prompt, $options),
            'local' => $this->generateLocal($prompt, $options),
            default => throw new \InvalidArgumentException("Unsupported LLM provider: {$this->provider}"),
        };
    }

    /**
     * Generate using OpenAI
     */
    protected function generateOpenAI(string $prompt, array $options): ?string
    {
        $apiKey = config('curriculum_ai.openai.api_key');
        if (!$apiKey) {
            Log::error('OpenAI API key not configured');
            return null;
        }

        try {
            $response = Http::timeout(config('curriculum_ai.openai.timeout', 30))
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $options['model'] ?? $this->config['model'] ?? 'gpt-4-turbo-preview',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert curriculum designer and teacher trainer specializing in Kenyan CBC/CBE curriculum.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 2000,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI generation exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate using HuggingFace
     */
    protected function generateHuggingFace(string $prompt, array $options): ?string
    {
        $apiKey = config('curriculum_ai.huggingface.api_key');
        $apiUrl = config('curriculum_ai.huggingface.api_url', 'https://api-inference.huggingface.co');
        $model = $options['model'] ?? $this->config['model'] ?? 'meta-llama/Llama-2-7b-chat-hf';

        try {
            $response = Http::timeout(config('curriculum_ai.huggingface.timeout', 60))
                ->withHeaders([
                    'Authorization' => $apiKey ? "Bearer {$apiKey}" : null,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$apiUrl}/models/{$model}", [
                    'inputs' => $prompt,
                    'parameters' => [
                        'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
                        'max_new_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 2000,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // HuggingFace response format varies by model
                if (isset($data[0]['generated_text'])) {
                    return $data[0]['generated_text'];
                }
                if (isset($data['generated_text'])) {
                    return $data['generated_text'];
                }
            }

            Log::error('HuggingFace API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('HuggingFace generation exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate using local LLM endpoint
     */
    protected function generateLocal(string $prompt, array $options): ?string
    {
        $endpoint = $this->config['endpoint'] ?? 'http://localhost:8000/v1/chat/completions';

        try {
            $response = Http::timeout(120)
                ->post($endpoint, [
                    'model' => $options['model'] ?? $this->config['model'] ?? 'local-llm',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert curriculum designer.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $options['temperature'] ?? $this->config['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 2000,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('Local LLM API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Local LLM generation exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
}

