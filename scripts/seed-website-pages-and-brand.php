<?php

/**
 * Ensure public site pages exist and refresh cocurricular brand images.
 * Usage: php scripts/seed-website-pages-and-brand.php
 */

use App\Models\Website\Page;
use App\Models\Website\WebsiteBrandItem;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$pages = [
    'gallery' => 'Gallery',
    'blog' => 'News',
    'campus-life' => 'Campus Life',
    'academics' => 'Academics',
    'admissions' => 'Admissions',
    'about' => 'About',
    'contact' => 'Contact',
    'fees' => 'Fees',
    'leadership' => 'Leadership',
    'transport' => 'Transport',
    'child-safety' => 'Child Safety',
    'community' => 'Community',
    'calendar' => 'Calendar',
    'admissions-faq' => 'Admissions FAQ',
];

foreach ($pages as $slug => $name) {
    $page = Page::query()->firstOrCreate(
        ['slug' => $slug],
        [
            'name' => $name,
            'title' => $name,
            'status' => Page::STATUS_PUBLISHED,
            'is_homepage' => false,
            'published_at' => now(),
        ]
    );
    if (! $page->wasRecentlyCreated && $page->status !== Page::STATUS_PUBLISHED) {
        $page->status = Page::STATUS_PUBLISHED;
        $page->published_at = $page->published_at ?: now();
        $page->save();
    }
    echo ($page->wasRecentlyCreated ? 'Created' : 'Exists').": {$slug}\n";
}

$imageMap = [
    'Skating' => '/images/campus-life-2026/curated/01-preschool-trip.jpg',
    'Ballet' => '/images/campus-life-2026/curated/37-career-day.jpg',
    'Robotics' => '/images/campus-life-2026/curated/35-career-day.jpg',
    'Field Trips' => '/images/campus-life-2026/curated/25-nakuru-trip.jpg',
    'Archery' => '/images/campus-life-2026/curated/25-nakuru-trip.jpg',
    'Music' => '/images/campus-life-2026/curated/33-career-day.jpg',
    'Sports' => '/images/campus-life-2026/curated/13-lower-primary-trip.jpg',
];

foreach (WebsiteBrandItem::query()->where('block_type', 'cocurricular')->get() as $item) {
    $title = (string) $item->title;
    if (! isset($imageMap[$title])) {
        continue;
    }
    $item->image_url = $imageMap[$title];
    if ($title === 'Archery') {
        $item->title = 'Field Trips';
        $item->body = 'Nakuru wildlife, soft-play adventures, and learning beyond the gate.';
    }
    $item->save();
    echo "Brand cocurricular updated: {$item->title}\n";
}

Cache::flush();
echo "Done.\n";
