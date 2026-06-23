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
            'school_name' => 'Royal Kings Premier School LTD',
            'tagline' => 'Where Little Steps Grow Into Great Futures',
            'primary_color' => '#9B1FE8',
            'secondary_color' => '#D4AF37',
            'phone' => '+254 719 396 233',
            'email' => 'info@royalkingsschools.sc.ke',
            'whatsapp' => '254719396233',
            'address' => 'Wangige, Kiambu County, Kenya',
            'admissions_open' => true,
            'current_term' => 'Term 1, 2026',
            'google_map' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15955.5!2d36.705!3d-1.245!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sRoyal%20Kings%20School%20Wangige!5e0!3m2!1sen!2ske!4v1719000000000!5m2!1sen!2ske" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>',
            'seo_defaults' => [
                'title' => 'Royal Kings Premier School | Creche to Grade 9',
                'description' => 'Royal Kings Premier School LTD — Christian-centered education in Wangige, Kiambu. Creche to Grade 9.',
                'keywords' => 'Royal Kings Premier School, Wangige, Kiambu, Christian school, creche, primary',
            ],
        ]);

        $homepage = Page::query()->firstOrCreate(
            ['slug' => 'home'],
            [
                'name' => 'Homepage',
                'title' => 'Royal Kings Premier School',
                'status' => Page::STATUS_PUBLISHED,
                'is_homepage' => true,
                'published_at' => now(),
            ]
        );

        $img = 'https://royalkingsschools.sc.ke/assets/images';

        $sections = [
            ['section_type' => 'hero', 'section_key' => 'hero_main', 'title' => 'Building a Sure Foundation for Lifelong Learning', 'subtitle' => 'Creche to Grade 9', 'sort_order' => 0],
            [
                'section_type' => 'school_pathway',
                'section_key' => 'pathway_early_years',
                'title' => 'Creche & Early Years',
                'subtitle' => 'Age 3–5',
                'content' => 'Strong beginnings through play, care, and foundational learning.',
                'settings' => [
                    'cta_label' => 'Explore Early Years',
                    'link_url' => '/academics#early-years',
                    'image_url' => $img.'/family-happy-family-portrait-vectorized-character-design-23-2148163542-160x160.jpg',
                ],
                'sort_order' => 1,
            ],
            [
                'section_type' => 'school_pathway',
                'section_key' => 'pathway_primary',
                'title' => 'Primary School',
                'subtitle' => 'Grade 1–6',
                'content' => 'Academic growth, creativity, and discovery.',
                'settings' => [
                    'cta_label' => 'Explore Primary',
                    'link_url' => '/academics#primary',
                    'image_url' => $img.'/332419888-1246340212647102-4730361110570400332-n-1101x734.jpeg',
                ],
                'sort_order' => 2,
            ],
            [
                'section_type' => 'school_pathway',
                'section_key' => 'pathway_junior_secondary',
                'title' => 'Junior Secondary',
                'subtitle' => 'Grade 7–9',
                'content' => 'Leadership, discipline, and future readiness.',
                'settings' => [
                    'cta_label' => 'Explore Junior School',
                    'link_url' => '/academics#junior-secondary',
                    'image_url' => $img.'/325404592-1597148387416946-3846122370734442560-n-1-906x604.jpeg',
                ],
                'sort_order' => 3,
            ],
            ['section_type' => 'school_pathways_intro', 'section_key' => 'find_your_place', 'title' => "Find Your Child's Place", 'subtitle' => 'Three pathways, one caring community — help your child find where they belong from their very first day.', 'sort_order' => 0, 'is_active' => true],
            ['section_type' => 'journey', 'section_key' => 'one_journey', 'title' => 'One Journey. One Home.', 'sort_order' => 4],
            ['section_type' => 'programs', 'section_key' => 'beyond_classroom', 'title' => 'Beyond the Classroom', 'sort_order' => 5],
            ['section_type' => 'testimonials', 'section_key' => 'testimonials_carousel', 'title' => 'What Parents Say', 'sort_order' => 6],
            ['section_type' => 'events', 'section_key' => 'latest_events', 'title' => 'Latest Events', 'sort_order' => 7],
            ['section_type' => 'cta', 'section_key' => 'admissions_cta', 'title' => 'Begin Your Journey', 'subtitle' => 'Admissions Now Open', 'sort_order' => 8],
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
            ['name' => 'Parent — Royal Kings Family'],
            [
                'relationship' => 'Parent',
                'message' => 'Royal Kings School transformed my child\'s life, fostering a love for learning and instilling strong values.',
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
