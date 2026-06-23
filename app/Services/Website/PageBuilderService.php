<?php

namespace App\Services\Website;

use App\Models\Website\Page;
use App\Models\Website\PageBuilderDraft;
use App\Models\Website\PageBuilderSnapshot;
use App\Models\Website\PageSection;
use App\Models\Website\SectionTemplate;
use Illuminate\Support\Str;

class PageBuilderService
{
    public const BLOCK_TYPES = [
        'hero', 'page_hero', 'rich_text', 'editorial_intro', 'school_story', 'school_pathway', 'school_pathways_intro',
        'journey', 'gallery', 'photo_grid', 'stats', 'card_grid', 'info_grid', 'payment_methods', 'list_columns',
        'social_cta', 'testimonials', 'timeline', 'cta', 'cta_banner', 'faq', 'videos', 'spotlight', 'blog_feed',
        'admissions_banner', 'scripture_block', 'leadership_message', 'programs',
    ];

    public function addSectionFromTemplate(Page $page, SectionTemplate|string $template, int $sortOrder = 0): PageSection
    {
        if (is_string($template)) {
            $template = SectionTemplate::where('type', $template)->firstOrFail();
        }

        $defaults = $template->default_content ?? [];

        return PageSection::create([
            'page_id' => $page->id,
            'section_type' => $template->type,
            'section_key' => $template->type.'-'.Str::random(6),
            'title' => $defaults['title'] ?? $template->name,
            'subtitle' => $defaults['subtitle'] ?? null,
            'content' => $defaults['content'] ?? null,
            'settings' => array_merge($template->settings ?? [], $defaults['settings'] ?? []),
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    public function cloneSection(PageSection $section): PageSection
    {
        $copy = $section->replicate(['section_key']);
        $copy->section_key = $section->section_type.'-'.Str::random(6);
        $copy->sort_order = $section->sort_order + 1;
        $copy->save();

        return $copy;
    }

    public function reorder(Page $page, array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            PageSection::where('page_id', $page->id)->where('id', $id)->update(['sort_order' => $position]);
        }
    }

    public function autosave(Page $page, array $sections, ?int $userId = null): PageBuilderDraft
    {
        return PageBuilderDraft::updateOrCreate(
            ['page_id' => $page->id],
            ['sections' => $sections, 'updated_by' => $userId]
        );
    }

    public function snapshot(Page $page, ?string $label = null, ?int $userId = null): PageBuilderSnapshot
    {
        $sections = $page->sections()->orderBy('sort_order')->get()->map(fn ($s) => $s->toArray())->all();

        return PageBuilderSnapshot::create([
            'page_id' => $page->id,
            'sections' => $sections,
            'label' => $label ?? 'Snapshot '.now()->format('Y-m-d H:i'),
            'created_by' => $userId,
        ]);
    }

    public function restoreSnapshot(PageBuilderSnapshot $snapshot): void
    {
        $page = $snapshot->page;
        $page->sections()->delete();

        foreach ($snapshot->sections as $i => $data) {
            PageSection::create([
                'page_id' => $page->id,
                'section_type' => $data['section_type'],
                'section_key' => $data['section_key'] ?? ($data['section_type'].'-'.Str::random(6)),
                'title' => $data['title'] ?? null,
                'subtitle' => $data['subtitle'] ?? null,
                'content' => $data['content'] ?? null,
                'settings' => $data['settings'] ?? [],
                'sort_order' => $data['sort_order'] ?? $i,
                'is_active' => $data['is_active'] ?? true,
            ]);
        }
    }
}
