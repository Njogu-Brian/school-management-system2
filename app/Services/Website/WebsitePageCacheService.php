<?php

namespace App\Services\Website;

use App\Models\Website\Page;
use App\Models\Website\PageSection;
use Illuminate\Support\Facades\Cache;

class WebsitePageCacheService
{
    public function bustHomepage(): void
    {
        Cache::forget('website.api.homepage');
    }

    public function bustPageSlug(string $slug): void
    {
        Cache::forget("website.api.page.{$slug}");
    }

    public function bustForPage(?Page $page): void
    {
        if (! $page) {
            return;
        }

        if ($page->is_homepage) {
            $this->bustHomepage();
        }

        $this->bustPageSlug($page->slug);
    }

    public function bustForSection(PageSection $section): void
    {
        $page = $section->relationLoaded('page')
            ? $section->page
            : $section->page()->first();

        $this->bustForPage($page);
    }
}
