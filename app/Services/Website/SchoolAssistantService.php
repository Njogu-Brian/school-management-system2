<?php

namespace App\Services\Website;

use App\Models\Website\AiChatMessage;
use App\Models\Website\AiChatSession;
use App\Models\Website\AssistantKnowledgeArticle;
use App\Models\Website\Faq;
use App\Models\Website\WebsiteSetting;

class SchoolAssistantService
{
    public function __construct(
        protected WebsiteLlmService $llm,
        protected WebsiteErpIntegrationService $erp
    ) {}

    public function getOrCreateSession(?string $sessionKey = null, ?string $pagePath = null): AiChatSession
    {
        if ($sessionKey) {
            $session = AiChatSession::where('session_key', $sessionKey)->first();
            if ($session) {
                if ($pagePath) {
                    $ctx = $session->context ?? [];
                    $ctx['current_page'] = $pagePath;
                    $session->update(['context' => $ctx]);
                }

                return $session;
            }
        }

        return AiChatSession::create([
            'session_key' => (string) \Illuminate\Support\Str::uuid(),
            'context' => $this->buildKnowledgeContext($pagePath),
        ]);
    }

    public function chat(AiChatSession $session, string $userMessage, ?string $pagePath = null): array
    {
        if ($pagePath) {
            $ctx = $session->context ?? $this->buildKnowledgeContext($pagePath);
            $ctx['current_page'] = $pagePath;
            $session->update(['context' => $ctx]);
        }

        AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'message' => $userMessage,
        ]);

        $history = $session->messages()
            ->latest()
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn ($m) => "{$m->role}: {$m->message}")
            ->implode("\n");

        $context = $session->context ?? $this->buildKnowledgeContext($pagePath);
        $pageHint = $this->pageAwareHint($pagePath);

        $prompt = "School knowledge base:\n".json_encode($context, JSON_PRETTY_PRINT)
            ."\n\nPage context: {$pageHint}"
            ."\n\nRecent conversation:\n{$history}"
            ."\n\nVisitor question: {$userMessage}"
            ."\n\nAnswer helpfully using only the context. If on admissions pages, prioritize application steps and tour booking. Keep under 200 words.";

        $reply = $this->llm->generate($prompt, [
            'system_prompt' => 'You are the Royal Kings School Assistant — warm, Christian, family-centered. Never invent fee amounts.',
            'max_tokens' => 600,
        ]) ?? 'Please contact our office or submit an enquiry on our website.';

        AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'message' => $reply,
        ]);

        return [
            'session_key' => $session->session_key,
            'reply' => $reply,
            'page_context' => $pagePath,
        ];
    }

    public function buildKnowledgeContext(?string $pagePath = null): array
    {
        $settings = WebsiteSetting::current();

        $faqs = Faq::query()->orderBy('order')->limit(40)->get(['question', 'answer'])
            ->map(fn ($f) => ['q' => $f->question, 'a' => $f->answer])->all();

        $articles = AssistantKnowledgeArticle::query()
            ->where('published', true)
            ->when($pagePath, fn ($q) => $q->where(function ($q) use ($pagePath) {
                $q->whereNull('page_context')
                    ->orWhereJsonContains('page_context', $pagePath);
            }))
            ->orderByDesc('priority')
            ->limit(20)
            ->get(['title', 'topic', 'content'])
            ->map(fn ($a) => ['title' => $a->title, 'topic' => $a->topic, 'content' => $a->content])
            ->all();

        return [
            'school_name' => $settings->school_name ?? 'Royal Kings Education Centre',
            'tagline' => $settings->tagline ?? null,
            'contact_phone' => $settings->phone ?? null,
            'contact_email' => $settings->email ?? null,
            'address' => $settings->address ?? null,
            'admissions_info' => $settings->admissions_open ? 'Applications open — apply online.' : 'Contact admissions for availability.',
            'faqs' => $faqs,
            'knowledge_articles' => $articles,
            'upcoming_events' => $this->erp->upcomingErpEvents(5),
            'current_page' => $pagePath,
        ];
    }

    protected function pageAwareHint(?string $pagePath): string
    {
        if (! $pagePath) {
            return 'General website visitor';
        }
        if (str_contains($pagePath, 'admission')) {
            return 'User is on admissions — prioritize apply steps, documents, tours, and age groups.';
        }
        if (str_contains($pagePath, 'contact')) {
            return 'User is on contact — share phone, email, WhatsApp, visit booking.';
        }
        if (str_contains($pagePath, 'academic')) {
            return 'User is exploring academics — explain CBC pathway Creche to Grade 9.';
        }

        return "User is viewing: {$pagePath}";
    }
}
