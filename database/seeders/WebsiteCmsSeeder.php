<?php

namespace Database\Seeders;

use App\Models\Website\Blog;
use App\Models\Website\Faq;
use App\Models\Website\Page;
use App\Models\Website\PageSection;
use App\Models\Website\Testimonial;
use App\Models\Website\WebsiteEvent;
use App\Models\Website\WebsiteSetting;
use Illuminate\Database\Seeder;

class WebsiteCmsSeeder extends Seeder
{
    public function run(): void
    {
        WebsiteSetting::query()->firstOrCreate([], [
            'school_name' => 'Royal Kings Education Centre',
            'tagline' => 'Where Little Steps Grow Into Great Futures',
            'primary_color' => '#5B2C8E',
            'secondary_color' => '#D4AF37',
            'phone' => '+254 700 000 000',
            'email' => 'info@royalkings.ac.ke',
            'address' => 'Nairobi, Kenya',
            'admissions_open' => true,
            'current_term' => 'Term 1, 2026',
            'seo_defaults' => [
                'title' => 'Royal Kings Education Centre | Creche to Grade 9',
                'description' => 'A Christian-centered, family-friendly school nurturing learners from age 3 to Grade 9.',
                'keywords' => 'Royal Kings, school, Nairobi, Christian school, creche, primary',
            ],
        ]);

        $homepage = Page::query()->firstOrCreate(
            ['slug' => 'home'],
            [
                'name' => 'Homepage',
                'title' => 'Royal Kings Education Centre',
                'status' => Page::STATUS_PUBLISHED,
                'is_homepage' => true,
                'published_at' => now(),
            ]
        );

        $sections = [
            ['section_type' => 'hero', 'section_key' => 'hero_main', 'title' => 'Where Little Steps Grow Into Great Futures', 'subtitle' => 'Creche to Grade 9', 'sort_order' => 0],
            ['section_type' => 'age_journey', 'section_key' => 'age_journey_main', 'title' => 'Your Child\'s Journey', 'sort_order' => 1],
            ['section_type' => 'why_us', 'section_key' => 'why_us_cards', 'title' => 'Why Royal Kings', 'sort_order' => 2],
            ['section_type' => 'learning_pathway', 'section_key' => 'pathway_timeline', 'title' => 'Learning Pathway', 'sort_order' => 3],
            ['section_type' => 'programs', 'section_key' => 'programs_grid', 'title' => 'Co-Curricular Programs', 'sort_order' => 4],
            ['section_type' => 'testimonials', 'section_key' => 'testimonials_carousel', 'title' => 'What Parents Say', 'sort_order' => 5],
            ['section_type' => 'gallery', 'section_key' => 'campus_gallery', 'title' => 'Campus Life', 'sort_order' => 6],
            ['section_type' => 'events', 'section_key' => 'latest_events', 'title' => 'Latest Events', 'sort_order' => 7],
            ['section_type' => 'portal_preview', 'section_key' => 'parent_portal', 'title' => 'Parent Portal', 'sort_order' => 8],
            ['section_type' => 'cta', 'section_key' => 'admissions_cta', 'title' => 'Begin Your Journey', 'subtitle' => 'Admissions Now Open', 'sort_order' => 9],
        ];

        foreach ($sections as $section) {
            PageSection::query()->firstOrCreate(
                ['page_id' => $homepage->id, 'section_key' => $section['section_key']],
                $section + ['page_id' => $homepage->id, 'is_active' => true]
            );
        }

        $pages = [
            ['name' => 'About', 'slug' => 'about', 'title' => 'About Royal Kings'],
            ['name' => 'Academics', 'slug' => 'academics', 'title' => 'Academics'],
            ['name' => 'Admissions', 'slug' => 'admissions', 'title' => 'Admissions'],
            ['name' => 'Campus Life', 'slug' => 'campus-life', 'title' => 'Campus Life'],
            ['name' => 'Co-curricular', 'slug' => 'co-curricular', 'title' => 'Co-curricular'],
            ['name' => 'Gallery', 'slug' => 'gallery', 'title' => 'Gallery'],
            ['name' => 'Events', 'slug' => 'events', 'title' => 'Events'],
            ['name' => 'Blog', 'slug' => 'blog', 'title' => 'Blog'],
            ['name' => 'Contact', 'slug' => 'contact', 'title' => 'Contact Us'],
            ['name' => 'Parent Portal', 'slug' => 'parent-portal', 'title' => 'Parent Portal'],
        ];

        foreach ($pages as $page) {
            Page::query()->firstOrCreate(
                ['slug' => $page['slug']],
                $page + ['status' => Page::STATUS_PUBLISHED, 'published_at' => now()]
            );
        }

        Testimonial::query()->firstOrCreate(
            ['name' => 'Mrs. Wanjiku K.'],
            [
                'relationship' => 'Parent, Grade 4',
                'message' => 'Royal Kings has been a blessing for our family. Our children are growing in faith, character, and academic excellence.',
                'featured' => true,
                'approved' => true,
            ]
        );

        Faq::query()->firstOrCreate(
            ['question' => 'What ages do you admit?'],
            [
                'answer' => 'We welcome learners from age 3 (Creche) through Grade 9.',
                'category' => 'Admissions',
                'order' => 1,
            ]
        );

        WebsiteEvent::query()->firstOrCreate(
            ['slug' => 'open-day-2026'],
            [
                'title' => 'Royal Kings Open Day 2026',
                'description' => 'Tour our campus, meet teachers, and discover the Royal Kings difference.',
                'start_date' => now()->addWeeks(2)->toDateString(),
                'location' => 'Main Campus',
                'registration_enabled' => true,
            ]
        );

        Blog::query()->firstOrCreate(
            ['slug' => 'welcome-to-royal-kings'],
            [
                'title' => 'Welcome to Royal Kings Education Centre',
                'excerpt' => 'Discover a warm, Christian-centered community where every child is known and nurtured.',
                'body' => '<p>At Royal Kings, we believe that little steps today become great futures tomorrow.</p>',
                'published' => true,
                'published_at' => now(),
            ]
        );
    }
}
