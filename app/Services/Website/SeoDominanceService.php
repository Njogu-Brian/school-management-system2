<?php

namespace App\Services\Website;

use App\Http\Controllers\Api\Website\WebsiteSeoController;
use App\Models\Website\Page;
use App\Models\Website\SeoKeyword;
use App\Models\Website\ServiceAreaPage;
use App\Models\Website\WebsiteSetting;

class SeoDominanceService
{
    public function scoreContent(string $title, string $body, ?string $keyword = null): array
    {
        $wordCount = str_word_count(strip_tags($body));
        $readability = $wordCount > 0 ? min(100, (int) round(100 - (strlen($body) / max($wordCount, 1) / 2))) : 0;

        $score = 50;
        if (strlen($title) >= 30 && strlen($title) <= 65) {
            $score += 15;
        }
        if ($wordCount >= 300) {
            $score += 20;
        }
        if ($keyword && stripos($body, $keyword) !== false) {
            $score += 15;
        }

        return [
            'seo_score' => min(100, $score),
            'readability_score' => max(0, $readability),
            'word_count' => $wordCount,
            'suggestions' => $this->metaSuggestions($title, $body, $keyword),
        ];
    }

    public function metaSuggestions(string $title, string $body, ?string $keyword = null): array
    {
        $suggestions = [];
        if (strlen($title) < 30) {
            $suggestions[] = 'Title is short — aim for 30–65 characters for search snippets.';
        }
        if ($keyword && stripos($title, $keyword) === false) {
            $suggestions[] = "Include target keyword \"{$keyword}\" in the title.";
        }
        if (str_word_count(strip_tags($body)) < 300) {
            $suggestions[] = 'Add more substantive content (300+ words) for stronger rankings.';
        }

        return $suggestions;
    }

    public function internalLinkSuggestions(Page $page): array
    {
        return Page::query()
            ->where('id', '!=', $page->id)
            ->where('status', Page::STATUS_PUBLISHED)
            ->limit(5)
            ->get(['id', 'title', 'slug'])
            ->map(fn ($p) => [
                'title' => $p->title,
                'url' => '/'.$p->slug,
                'reason' => 'Related published page',
            ])
            ->all();
    }

    public function detectDuplicateTitles(): array
    {
        return Page::query()
            ->selectRaw('title, COUNT(*) as cnt')
            ->groupBy('title')
            ->having('cnt', '>', 1)
            ->pluck('title')
            ->all();
    }

    public function schemaBundle(?Page $page = null): array
    {
        $school = WebsiteSeoController::jsonLdSchool();

        $schemas = [
            $school,
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $school['name'] ?? 'Royal Kings Education Centre',
                'url' => config('app.url'),
            ],
        ];

        if ($page) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $page->title,
                'description' => $page->meta_description,
                'url' => config('app.url').'/'.$page->slug,
            ];
        }

        return $schemas;
    }

    public function localAreas(): array
    {
        return ServiceAreaPage::where('published', true)->orderBy('area_name')->get()->all();
    }

    public function napConsistency(): array
    {
        $s = WebsiteSetting::current();

        return [
            'name' => $s->school_name,
            'phone' => $s->phone,
            'email' => $s->email,
            'address' => $s->address,
            'consistent' => filled($s->school_name) && filled($s->phone) && filled($s->address),
        ];
    }
}
