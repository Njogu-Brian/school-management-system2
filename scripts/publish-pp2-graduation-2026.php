<?php

/**
 * Publish the 2026 PP2 Graduation story:
 * - Blog post (next to / ahead of admissions news)
 * - Homepage promo beside 2027 Admissions Open
 * - Media Library gallery images (category: gallery)
 *
 * Usage: php scripts/publish-pp2-graduation-2026.php
 */

use App\Models\Website\Blog;
use App\Models\Website\MediaLibraryItem;
use App\Models\Website\MediaQualityFlag;
use App\Models\Website\Page;
use App\Models\Website\PageSection;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$featuredRelative = 'imported/pp2-graduation-2026/pp2-graduation-2026-featured.jpg';
$featuredAbs = public_path('website/'.$featuredRelative);
$albumDir = public_path('website/imported/pp2-graduation-2026');

if (! is_file($featuredAbs)) {
    fwrite(STDERR, "Missing featured image at {$featuredAbs}\n");
    exit(1);
}

$featuredUrl = asset('website/'.$featuredRelative);
$admissionsUrl = asset('website/imported/2027-admissions-open.jpg');
if (! is_file(public_path('website/imported/2027-admissions-open.jpg'))) {
    $admissionsUrl = asset('images/2027-admissions-open.jpg');
}

$body = <<<'HTML'
<p>On Tuesday, 3rd November 2026, Royal Kings Premier School celebrated a milestone of greatness as our PP2 Class of 2026 graduated — little stars ready for their next big step into Grade 1.</p>

<p>From confident smiles and proud moments on stage to certificates awarded by our Guest of Honour and School Directors, the day was filled with joy, culture, and the promise of bright futures. Our facilitators shared words of wisdom, reminding every graduate to remain curious, confident, disciplined, and eager to learn.</p>

<p>We also thank our amazing parents for their love, trust, and partnership. Your support shapes our little stars every day. Together, we are building bright futures.</p>

<p><strong>Highlights from the ceremony</strong></p>
<ul>
  <li>PP2 graduands shining on stage — next stop, Grade 1</li>
  <li>Certificate presentation with our Guest of Honour and School Directors</li>
  <li>Words of wisdom from our facilitators</li>
  <li>Foundation Class poem: small voices, big confidence</li>
  <li>Grade 3 cultural drama celebrating our heritage</li>
</ul>

<p>The journey continues, little stars. Keep shining!</p>

<p><em>Royal Kings Premier School — Riverside, Wangige · Where Learning Is Fun · A Sure Foundation</em></p>
HTML;

$blog = Blog::query()->updateOrCreate(
    ['slug' => 'pp2-graduation-2026'],
    [
        'title' => 'PP2 Graduation 2026: Our Little Stars Are Shining Bright',
        'excerpt' => 'Celebrating our PP2 Class of 2026 as they step proudly into Grade 1 — certificates, culture, and proud family moments at Riverside, Wangige.',
        'body' => $body,
        'featured_image' => $featuredRelative,
        'published' => true,
        'published_at' => '2026-11-03 09:00:00',
        'is_featured' => true,
    ]
);
echo "Blog published: {$blog->slug} (id {$blog->id})\n";

$home = Page::query()->where('is_homepage', true)->first()
    ?? Page::query()->where('slug', 'home')->first();

if (! $home) {
    $home = Page::query()->create([
        'name' => 'Home',
        'slug' => 'home',
        'title' => 'Royal Kings Premier School',
        'status' => Page::STATUS_PUBLISHED,
        'is_homepage' => true,
        'published_at' => now(),
    ]);
    echo "Created homepage page (id {$home->id})\n";
} elseif (! $home->is_homepage) {
    $home->is_homepage = true;
    $home->save();
}

PageSection::query()->updateOrCreate(
    ['page_id' => $home->id, 'section_key' => 'home_promos'],
    [
        'section_type' => 'photo_grid',
        'title' => 'Latest at Royal Kings',
        'subtitle' => null,
        'settings' => [
            'photos' => [
                [
                    'src' => $admissionsUrl,
                    'title' => '2027 Admissions Now Open',
                    'href' => '/admissions/apply',
                ],
                [
                    'src' => $featuredUrl,
                    'title' => 'PP2 Graduation 2026',
                    'href' => '/blog/pp2-graduation-2026',
                ],
            ],
        ],
        'sort_order' => 1,
        'is_active' => true,
    ]
);
echo "Homepage promos updated (admissions + graduation)\n";

$captionMap = [
    '1768419731949803' => 'PP2 Graduation 2026 — ceremony stage',
    '1768419561949820' => 'Guest of Honour and directors celebrate our graduates',
    '1768419395283170' => 'Proud Royal Kings parents cheering our little stars',
    '1768417801949996' => 'Families celebrating Class of 2026',
    '1768411995283910' => 'Certificate moment — PP2 graduate Class of 2026',
    '1768399981951778' => 'Our little star ready for Grade 1',
    '1768211425303967' => 'Cultural celebration on graduation day',
];

$imported = 0;
$files = collect(File::files($albumDir))
    ->filter(fn ($f) => str_ends_with(strtolower($f->getFilename()), '.jpg'))
    ->sortBy(fn ($f) => $f->getFilename())
    ->values();

foreach ($files as $file) {
    $name = $file->getFilename();
    if ($name === 'pp2-graduation-2026-featured.jpg') {
        continue;
    }

    $relative = 'imported/pp2-graduation-2026/'.$name;
    $fbid = null;
    if (preg_match('/-(\d{13,})\.jpg$/', $name, $m)) {
        $fbid = $m[1];
    }

    $title = $captionMap[$fbid] ?? 'PP2 Graduation 2026';
    $alt = 'Royal Kings Premier School PP2 Graduation 2026';

    $item = MediaLibraryItem::query()->updateOrCreate(
        ['file_path' => $relative],
        [
            'title' => $title,
            'type' => 'image',
            'category' => 'gallery',
            'alt_text' => $alt,
            'is_featured' => in_array($fbid, ['1768411995283910', '1768399981951778', '1768211425303967'], true),
            'optimization_status' => MediaLibraryItem::OPT_SKIPPED,
        ]
    );

    MediaQualityFlag::query()->updateOrCreate(
        ['media_id' => $item->id],
        [
            'approved' => true,
            'homepage_ready' => (bool) $item->is_featured,
            'hero_ready' => $fbid === '1768411995283910',
            'priority' => $item->is_featured ? 90 : 40,
        ]
    );

    $imported++;
}

echo "Gallery media imported/updated: {$imported}\n";

foreach ([
    'website.api.homepage',
    'website.api.settings.v2',
    'website.api.brand',
    'website.api.media.hero',
    'website.api.testimonials',
    'website.api.page.home',
    'website.api.page.gallery',
    'website.api.page.blog',
] as $key) {
    Cache::forget($key);
}

// Clear gallery list caches with common keys
Cache::flush();

echo "Done.\n";
