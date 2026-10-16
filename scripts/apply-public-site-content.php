<?php

use App\Models\Website\Page;
use App\Models\Website\PageSection;
use App\Models\Website\WebsiteBrandItem;
use App\Models\Website\WebsiteSetting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$retired = ['2025-admissions', 'whatsapp-image-2024-10-28-at-18.13.54'];
$banner = asset('website/imported/2027-admissions-open.jpg');

$settings = WebsiteSetting::query()->first();
if ($settings) {
    $settings->school_name = 'Royal Kings Premier School';
    $settings->save();
    echo "School name updated\n";
}

$home = Page::query()->where('is_homepage', true)->first();
if ($home) {
    PageSection::query()->updateOrCreate(
        ['page_id' => $home->id, 'section_key' => 'home_promos'],
        [
            'section_type' => 'photo_grid',
            'title' => '2027 Admissions Open',
            'subtitle' => null,
            'settings' => [
                'photos' => [[
                    'src' => $banner,
                    'title' => '2027 Admissions Now Open',
                    'href' => '/admissions/apply',
                ]],
            ],
            'sort_order' => 1,
            'is_active' => true,
        ]
    );
    echo "Homepage banner updated\n";
}

PageSection::query()->orderBy('id')->chunkById(50, function ($sections) use ($retired) {
    foreach ($sections as $section) {
        if ($section->section_key === 'home_promos') {
            continue;
        }
        $next = stripRetired($section->settings ?? [], $retired);
        if ($next != $section->settings) {
            $section->settings = $next;
            $section->save();
            echo "Cleaned section {$section->section_key}\n";
        }
    }
});

WebsiteBrandItem::query()->where('block_type', 'trust_pill')->update(['is_active' => false]);
WebsiteBrandItem::query()
    ->where(function ($query) {
        $query->where('image_url', 'like', '%2025-admissions%')
            ->orWhere('image_url', 'like', '%whatsapp-image-2024-10-28-at-18.13.54%');
    })
    ->update(['image_url' => null, 'is_active' => false]);

$pages = [
    'academics' => 'Academics',
    'leadership' => 'Leadership',
    'transport' => 'Transport',
    'child-safety' => 'Child Safety',
    'campus-life' => 'Campus Life',
    'community' => 'Community',
    'calendar' => 'Calendar',
    'admissions-faq' => 'Admissions FAQ',
    'contact' => 'Contact',
    'blog' => 'News',
];

foreach ($pages as $slug => $name) {
    Page::query()->firstOrCreate(
        ['slug' => $slug],
        [
            'name' => $name,
            'title' => $name,
            'status' => Page::STATUS_PUBLISHED,
            'is_homepage' => false,
            'published_at' => now(),
        ]
    );
}

foreach (['website.api.homepage', 'website.api.settings.v2', 'website.api.brand', 'website.api.media.hero', 'website.api.testimonials'] as $key) {
    Cache::forget($key);
}
foreach (array_keys($pages) as $slug) {
    Cache::forget('website.api.page.'.$slug);
}
Cache::forget('website.api.page.about');
Cache::forget('website.api.page.fees');
Cache::forget('website.api.page.gallery');
Cache::forget('website.api.page.admissions');
Cache::forget('website.api.page.home');

echo "Done\n";

function stripRetired(mixed $value, array $retired): mixed
{
    if (is_string($value)) {
        foreach ($retired as $marker) {
            if (str_contains($value, $marker)) {
                return null;
            }
        }

        return $value;
    }

    if (! is_array($value)) {
        return $value;
    }

    $list = array_is_list($value);
    $out = [];
    foreach ($value as $key => $child) {
        if ($list && is_array($child) && isset($child['src']) && is_string($child['src'])) {
            $drop = false;
            foreach ($retired as $marker) {
                if (str_contains($child['src'], $marker)) {
                    $drop = true;
                }
            }
            if ($drop) {
                continue;
            }
        }
        $out[$key] = stripRetired($child, $retired);
    }

    return $list ? array_values($out) : $out;
}
