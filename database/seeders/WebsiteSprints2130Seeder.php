<?php

namespace Database\Seeders;

use App\Models\Website\SectionTemplate;
use App\Models\Website\ServiceAreaPage;
use App\Models\Website\TestimonialCategory;
use Illuminate\Database\Seeder;

class WebsiteSprints2130Seeder extends Seeder
{
    public function run(): void
    {
        $blocks = [
            ['name' => 'Hero Banner', 'type' => 'hero', 'default_content' => ['title' => 'Welcome to Royal Kings', 'subtitle' => 'Where Little Steps Grow Into Great Futures']],
            ['name' => 'Image Gallery', 'type' => 'gallery'],
            ['name' => 'Stats Counters', 'type' => 'stats'],
            ['name' => 'Testimonials', 'type' => 'testimonials'],
            ['name' => 'Timeline', 'type' => 'timeline'],
            ['name' => 'Call to Action', 'type' => 'cta'],
            ['name' => 'FAQ Accordion', 'type' => 'faq'],
            ['name' => 'Video Block', 'type' => 'videos'],
            ['name' => 'Student Spotlight', 'type' => 'spotlight'],
            ['name' => 'Blog Feed', 'type' => 'blog_feed'],
            ['name' => 'Admissions Banner', 'type' => 'admissions_banner'],
            ['name' => 'Scripture Verse', 'type' => 'scripture_block', 'default_content' => ['content' => 'Train up a child in the way he should go. — Proverbs 22:6']],
            ['name' => 'Leadership Message', 'type' => 'leadership_message'],
        ];

        foreach ($blocks as $block) {
            SectionTemplate::firstOrCreate(['type' => $block['type']], $block + ['is_active' => true]);
        }

        foreach (['parent', 'alumni', 'staff', 'community'] as $cat) {
            TestimonialCategory::firstOrCreate(['slug' => $cat], ['name' => ucfirst($cat)]);
        }

        foreach (['Wangige', 'Lower Kabete', 'Kikuyu', 'Gitaru', 'Uthiru'] as $area) {
            ServiceAreaPage::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($area)],
                [
                    'area_name' => $area,
                    'headline' => "Christian Education Near {$area}",
                    'description' => "Royal Kings Education Centre serves families in {$area} and surrounding communities.",
                    'published' => true,
                ]
            );
        }
    }
}
