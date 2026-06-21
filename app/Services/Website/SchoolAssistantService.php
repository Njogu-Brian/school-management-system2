<?php

namespace App\Services\Website;

use App\Models\Website\AiChatMessage;
use App\Models\Website\AiChatSession;
use App\Models\Website\Faq;
use App\Models\Website\WebsiteSetting;
use App\Services\Website\WebsiteLlmService;
use Illuminate\Support\Str;

class SchoolAssistantService
{
    public function __construct(
        protected WebsiteLlmService $llm,
        protected WebsiteErpIntegrationService $erp
    ) {}

    public function getOrCreateSession(?string $sessionKey = null): AiChatSession
    {
        if ($sessionKey) {
            $session = AiChatSession::where('session_key', $sessionKey)->first();
            if ($session) {
                return $session;
            }
        }

        return AiChatSession::create([
            'session_key' => (string) Str::uuid(),
            'context' => $this->buildKnowledgeContext(),
        ]);
    }

    public function chat(AiChatSession $session, string $userMessage): array
    {
        AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'message' => $userMessage,
        ]);

        $history = $session->messages()
            ->latest()
            ->limit(8)
            ->get()
            ->reverse()
            ->map(fn ($m) => "{$m->role}: {$m->message}")
            ->implode("\n");

        $context = $session->context ?? $this->buildKnowledgeContext();
        $prompt = "School knowledge base:\n".json_encode($context, JSON_PRETTY_PRINT)
            ."\n\nRecent conversation:\n{$history}\n\nParent/visitor question: {$userMessage}\n\nAnswer helpfully. If unsure, suggest contacting the school office or visiting the admissions page. Keep answers concise (under 200 words).";

        $reply = $this->llm->generate($prompt, [
            'system_prompt' => 'You are the Royal Kings School Assistant — warm, Christian, family-centered. Answer admissions, fees, transport, curriculum, calendar, and policy questions using only the provided context. Never invent fee amounts.',
            'max_tokens' => 600,
        ]) ?? 'Thank you for your question. Please contact our office at Royal Kings Education Centre or submit an enquiry on our website for personalised assistance.';

        AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'message' => $reply,
        ]);

        return [
            'session_key' => $session->session_key,
            'reply' => $reply,
        ];
    }

    public function buildKnowledgeContext(): array
    {
        $settings = WebsiteSetting::current();

        $faqs = Faq::query()
            ->orderBy('order')
            ->limit(30)
            ->get(['question', 'answer'])
            ->map(fn ($f) => ['q' => $f->question, 'a' => $f->answer])
            ->all();

        return [
            'school_name' => $settings->school_name ?? 'Royal Kings Education Centre',
            'tagline' => $settings->tagline ?? null,
            'contact_phone' => $settings->phone ?? null,
            'contact_email' => $settings->email ?? null,
            'address' => $settings->address ?? null,
            'admissions_info' => $settings->admissions_open ? 'Applications are currently open.' : 'Contact admissions for availability.',
            'faqs' => $faqs,
            'recent_announcements' => $this->erp->announcements(5),
            'upcoming_events' => $this->erp->upcomingErpEvents(5),
            'topics' => [
                'admissions' => 'Online application available; track status with application number.',
                'fees' => 'Fee statements available in parent portal; M-Pesa and payment links supported.',
                'transport' => 'School bus routes available; contact office for route assignment.',
                'curriculum' => 'CBC competency-based learning from Creche to Grade 9.',
                'calendar' => 'Term dates and events published on website events page.',
            ],
        ];
    }
}
