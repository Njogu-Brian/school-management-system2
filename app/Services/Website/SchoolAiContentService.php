<?php

namespace App\Services\Website;

use App\Jobs\Website\GenerateAiContentJob;
use App\Models\Website\AiContentLog;
use App\Models\User;
use App\Services\LLMService;

class SchoolAiContentService
{
    public const TONE_SYSTEM_PROMPT = <<<'PROMPT'
You write for Royal Kings Education Centre — a warm, Christian, family-centered school in Kenya (Creche through Grade 9).
Tone: professional yet caring, faith-informed without being preachy, inclusive of parents and learners.
Use clear headings where appropriate. Avoid jargon. Sign off with community warmth when writing parent-facing copy.
PROMPT;

    public const TYPE_TEMPLATES = [
        'blog' => 'Write a blog article about: {subject}. Include an engaging intro, 3–4 sections, and a gentle call to action.',
        'announcement' => 'Write a school announcement about: {subject}. Be clear on who it affects, dates, and any action needed.',
        'newsletter' => 'Write a newsletter section for Royal Kings parents about: {subject}. Friendly, informative, 2–3 short paragraphs.',
        'event_recap' => 'Write an event recap for Royal Kings about: {subject}. Celebrate participation and thank families.',
        'admissions_copy' => 'Write admissions marketing copy about: {subject}. Highlight CBC journey, Christian values, and how to apply.',
        'social_media_caption' => 'Write a short social media caption (under 280 chars) for Royal Kings about: {subject}. Include 2–3 relevant hashtags.',
        'parent_message' => 'Write a parent message about: {subject}. Supportive, practical, faith-aware.',
        'fee_reminder' => 'Write a polite fee reminder message about: {subject}. Firm but gracious; mention payment options.',
    ];

    public function __construct(
        protected LLMService $llm
    ) {}

    public function generate(?User $user, string $contentType, string $subject, bool $queue = true): AiContentLog
    {
        $template = self::TYPE_TEMPLATES[$contentType] ?? self::TYPE_TEMPLATES['blog'];
        $prompt = str_replace('{subject}', $subject, $template);

        $log = AiContentLog::create([
            'user_id' => $user?->id,
            'content_type' => $contentType,
            'prompt' => $prompt,
            'status' => 'pending',
        ]);

        if ($queue) {
            GenerateAiContentJob::dispatch($log->id);
        } else {
            $this->completeLog($log);
        }

        return $log->fresh();
    }

    public function completeLog(AiContentLog $log): AiContentLog
    {
        try {
            $output = $this->llm->generate($log->prompt, [
                'system_prompt' => self::TONE_SYSTEM_PROMPT,
                'max_tokens' => 2500,
            ]);

            $log->update([
                'output' => $output ?? 'AI generation unavailable. Check LLM configuration.',
                'status' => $output ? 'completed' : 'failed',
            ]);
        } catch (\Throwable) {
            $log->update(['status' => 'failed', 'output' => null]);
        }

        return $log->fresh();
    }
}
