<?php

namespace Database\Seeders;

use App\Models\Website\Page;
use App\Models\Website\PageSection;
use Illuminate\Database\Seeder;

/**
 * Seeds CMS page sections for About, Fees, Gallery, Admissions content pages
 * and homepage School Story blocks — editable via Visual Page Builder.
 */
class WebsiteCmsPageSectionsSeeder extends Seeder
{
    private string $img;

    public function run(): void
    {
        $this->img = 'https://royalkingsschools.sc.ke/assets/images';

        $this->seedHomepageSchoolStory();
        $this->seedAboutPage();
        $this->seedFeesPage();
        $this->seedGalleryPage();
        $this->seedAdmissionsPage();
    }

    private function seedHomepageSchoolStory(): void
    {
        $page = Page::query()->where('is_homepage', true)->first();
        if (! $page) {
            return;
        }

        $this->upsertSection($page->id, 'school_story_empowering', [
            'section_type' => 'school_story',
            'title' => 'Empowering Minds, Shaping Futures',
            'subtitle' => 'Since 2006',
            'content' => "Welcome to the realm of Royal Kings Premier School, where education meets excellence in a whirlwind of Christian values and boundless potential. Our institution in Wangige, Kenya, is not just a school — it is a vibrant ecosystem where learners thrive under the guidance of dedicated professionals.\n\nJoin us on a journey of holistic education that sparks curiosity, ignites passion, and shapes tomorrow's leaders. We are dedicated to nurturing well-rounded individuals, emphasizing academic achievement alongside emotional, spiritual, and social development.",
            'settings' => [
                'variant' => 'empowering',
                'image_url' => $this->img.'/325404592-1597148387416946-3846122370734442560-n-1-906x604.jpeg',
                'href' => '/about',
                'label' => 'Our Story',
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page->id, 'school_story_mission', [
            'section_type' => 'school_story',
            'title' => 'Our Mission',
            'subtitle' => 'Our Mission',
            'content' => 'At Royal Kings Premier School, we ignite a passion for learning and growth by providing a holistic educational foundation that nurtures Christian values and moral character. Our friendly learning environment is where dedicated professionals guide students to reach their full potential.',
            'settings' => [
                'variant' => 'mission',
                'image_url' => $this->img.'/fb-img-1713928866746-1036x691.jpg',
                'items' => [
                    ['title' => 'Holistic Learning', 'description' => 'We go beyond academic success to develop well-rounded individuals.', 'icon' => '🌱'],
                    ['title' => 'Nurturing Environment', 'description' => "Discover a nurturing environment where every child's potential is unlocked.", 'icon' => '🏡'],
                    ['title' => 'Collaborative Community', 'description' => 'We believe in the power of collaboration with parents and professionals.', 'icon' => '🤝'],
                    ['title' => 'Core Christian Values', 'description' => 'Kindness, respect, truth, and love form the bedrock of our journey.', 'icon' => '✝️'],
                ],
            ],
            'sort_order' => 2,
        ]);
    }

    private function seedAboutPage(): void
    {
        $page = $this->page('about', 'About Royal Kings');
        if (! $page) {
            return;
        }

        $this->upsertSection($page->id, 'about_hero', [
            'section_type' => 'page_hero',
            'title' => 'Welcome to Royal Kings',
            'subtitle' => "Where we build a sure foundation for the little ones' future — CBC-aligned, Christian-centred, and rooted in Wangige since 2006.",
            'settings' => ['image_url' => $this->img.'/326278193-5858633464182480-7349085187052583899-n-2048x1365.jpg'],
            'sort_order' => 0,
        ]);

        $this->upsertSection($page->id, 'about_stats', [
            'section_type' => 'stats',
            'settings' => [
                'items' => [
                    ['title' => '2006', 'description' => 'Founded'],
                    ['title' => '355+', 'description' => 'Learners'],
                    ['title' => 'Creche–G9', 'description' => 'Levels'],
                    ['title' => '20 yrs', 'description' => 'Excellence'],
                ],
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page->id, 'about_story', [
            'section_type' => 'rich_text',
            'title' => 'Our Story',
            'content' => "Welcome to Royal Kings Premier School, where education is our legacy and our passion. We have proudly served as a leading child-centered institution for nearly two decades, shaping young minds and nurturing future leaders since 2006.\n\nRoyal Kings Premier School LTD is a Christian-centered institution serving families across Wangige, Lower Kabete, Kikuyu, Gitaru, Uthiru and surrounding communities.\n\nOur school offers the Competency-Based Curriculum (CBC) to provide your child with a modern, effective approach to learning. We wholeheartedly embrace the \"Learning is Fun\" philosophy — we inspire, empower, and mould the leaders of tomorrow.",
            'settings' => [
                'image_url' => $this->img.'/fb-img-1713928866746-1036x691.jpg',
                'image_position' => 'right',
            ],
            'sort_order' => 2,
        ]);

        $this->upsertSection($page->id, 'about_mission', [
            'section_type' => 'rich_text',
            'title' => 'Our Mission',
            'content' => 'At Royal Kings Premier School, we ignite a passion for learning and growth by providing a holistic educational foundation that nurtures Christian values and moral character. Join us on a journey of discovery and empowerment, where every learner is valued and supported to shine bright.',
            'settings' => [
                'image_url' => $this->img.'/325404592-1597148387416946-3846122370734442560-n-1-906x604.jpeg',
                'image_position' => 'left',
            ],
            'sort_order' => 3,
        ]);

        $this->upsertSection($page->id, 'about_facilities', [
            'section_type' => 'info_grid',
            'title' => 'Facilities & Campus',
            'subtitle' => 'Riverside Wangige, along the Western Bypass — a trusted choice for families in Lower Kabete and surrounding communities.',
            'settings' => [
                'items' => [
                    ['title' => 'ICT & Internet', 'description' => 'Computer rooms and high-speed internet for learners and staff.', 'icon' => '💻'],
                    ['title' => 'STEAM Labs', 'description' => 'Science, Technology, Engineering, Art & Mathematics laboratories.', 'icon' => '🔬'],
                    ['title' => 'Arts Studios', 'description' => 'Dance, drama, music, and art studios.', 'icon' => '🎭'],
                    ['title' => 'Sports Facilities', 'description' => 'Football, volleyball, netball, basketball, tennis, and cultural hall.', 'icon' => '⚽'],
                    ['title' => 'School Transport', 'description' => 'Fleet of buses and vans for daily routes.', 'icon' => '🚌'],
                    ['title' => 'Modern Classrooms', 'description' => 'Quality furniture, laptops, and CBC learning resources.', 'icon' => '🏫'],
                ],
                'image_url' => $this->img.'/screenshot-2024-04-16-162903-1101x619.png',
            ],
            'sort_order' => 4,
        ]);

        $this->upsertSection($page->id, 'about_pillars', [
            'section_type' => 'card_grid',
            'title' => 'What Makes Us Royal Kings',
            'settings' => [
                'items' => [
                    ['title' => 'Holistic Learning', 'description' => 'Preparing learners for today and a future where adaptability and innovation are key.', 'icon' => '🌱'],
                    ['title' => 'Nurturing Environment', 'description' => "Every child's potential unlocked and dreams realized.", 'icon' => '🏡'],
                    ['title' => 'Collaborative Community', 'description' => 'Parents and professionals join our school family.', 'icon' => '🤝'],
                    ['title' => 'Core Christian Values', 'description' => 'Kindness, respect, truth, and love.', 'icon' => '✝️'],
                    ['title' => 'Character Building', 'description' => 'Integrity and compassion through intentional programmes.', 'icon' => '💎'],
                    ['title' => 'Future Leaders', 'description' => 'Skills, confidence, and faith for a changing world.', 'icon' => '👑'],
                ],
            ],
            'sort_order' => 5,
        ]);

        $this->upsertSection($page->id, 'about_cta', [
            'section_type' => 'social_cta',
            'title' => 'Stay Connected',
            'subtitle' => 'Unleash Your Potential Today',
            'content' => "Embrace education that goes beyond textbooks and unlocks your child's true capabilities at Royal Kings Premier School.",
            'settings' => ['href' => '/admissions', 'label' => 'Start Your Adventure', 'cta_title' => 'Unleash Your Potential Today'],
            'sort_order' => 6,
        ]);
    }

    private function seedFeesPage(): void
    {
        $page = $this->page('fees', 'School Fees');
        if (! $page) {
            return;
        }

        $this->upsertSection($page->id, 'fees_hero', [
            'section_type' => 'page_hero',
            'title' => 'School Fees',
            'subtitle' => 'Transparent value — tuition, meals, sports, and pastoral care in one investment.',
            'settings' => ['image_url' => $this->img.'/332419888-1246340212647102-4730361110570400332-n-1101x734.jpeg'],
            'sort_order' => 0,
        ]);

        $this->upsertSection($page->id, 'fees_intro', [
            'section_type' => 'editorial_intro',
            'content' => 'Royal Kings Premier School offers premium Christian education structured for families seeking lasting value. We believe every child deserves excellence without compromise.',
            'subtitle' => 'Included in school fees: Academic tuition · Meals · Sports and clubs · House and inter-class activities',
            'sort_order' => 1,
        ]);

        $this->upsertSection($page->id, 'fees_covers', [
            'section_type' => 'info_grid',
            'title' => 'What Your Fees Cover',
            'subtitle' => 'Your investment supports the whole child — academically, spiritually, and socially.',
            'settings' => [
                'items' => [
                    ['title' => 'Academic Tuition', 'description' => 'CBC-aligned learning from Creche through Grade 9.', 'icon' => '📚'],
                    ['title' => 'Meals', 'description' => 'Balanced, nutritious meals prepared daily.', 'icon' => '🍽️'],
                    ['title' => 'Sports & Clubs', 'description' => 'Football, skating, ballet, coding, music, worship, and more.', 'icon' => '⚽'],
                    ['title' => 'House Activities', 'description' => 'Inter-class competitions and sports days.', 'icon' => '🏆'],
                    ['title' => 'Pastoral Care', 'description' => 'Daily devotions and Christian character formation.', 'icon' => '✝️'],
                    ['title' => 'Transport (Optional)', 'description' => 'Routes serving Wangige, Lower Kabete, Kikuyu, Gitaru & Uthiru.', 'icon' => '🚌'],
                ],
            ],
            'sort_order' => 2,
        ]);

        $this->upsertSection($page->id, 'fees_payment', [
            'section_type' => 'payment_methods',
            'title' => 'Accepted Payment Methods',
            'settings' => [
                'bank' => [
                    'name' => 'Equity Bank Kenya',
                    'branch' => 'Tom Mboya',
                    'account_name' => 'ROYAL KINGS EDUCATION CENTRE LTD',
                    'account_number' => '0120263149140',
                    'swift' => 'EQBLKENA XXX',
                    'bank_code' => '68-012',
                ],
                'mpesa' => [
                    'paybill' => '4068473',
                    'account_hint' => 'Student number or learner name',
                    'steps' => [
                        'Open M-Pesa → Lipa na M-Pesa → Pay Bill',
                        'Business No: 4068473',
                        'Account No: Student number or name',
                        'Enter amount and confirm with your M-Pesa PIN',
                    ],
                ],
                'equity_paybill' => [
                    'paybill' => '247247',
                    'account_hint' => "149140#(Child's name or admission number)",
                ],
                'notice' => 'We do not accept cash payments or M-Pesa Send Money.',
            ],
            'sort_order' => 3,
        ]);

        $this->upsertSection($page->id, 'fees_cta', [
            'section_type' => 'cta_banner',
            'title' => 'Request Current Fee Structure',
            'content' => 'Contact admissions or WhatsApp us for the current fee structure.',
            'settings' => ['href' => '/contact', 'label' => 'Contact Admissions'],
            'sort_order' => 4,
        ]);
    }

    private function seedGalleryPage(): void
    {
        $page = $this->page('gallery', 'Gallery');
        if (! $page) {
            return;
        }

        $this->upsertSection($page->id, 'gallery_hero', [
            'section_type' => 'page_hero',
            'title' => 'Gallery',
            'subtitle' => 'A sneak peek of how Learning is Fun! — real photos from classrooms, sports, arts, and celebrations at Royal Kings Wangige.',
            'settings' => ['image_url' => $this->img.'/20241024-img-1319-5184x3456.jpg'],
            'sort_order' => 0,
        ]);

        $this->upsertSection($page->id, 'gallery_grid', [
            'section_type' => 'photo_grid',
            'title' => 'Campus Life in Pictures',
            'subtitle' => 'Edit photos in CMS → settings → photos JSON, or upload via Media Library and paste URLs.',
            'settings' => ['use_gallery_catalog' => true],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page->id, 'gallery_cta', [
            'section_type' => 'cta_banner',
            'title' => 'See It In Person',
            'content' => 'Book a campus tour and experience Royal Kings for yourself.',
            'settings' => ['href' => '/admissions', 'label' => 'Book a Tour'],
            'sort_order' => 2,
        ]);
    }

    private function seedAdmissionsPage(): void
    {
        $page = $this->page('admissions', 'Admissions');
        if (! $page) {
            return;
        }

        $this->upsertSection($page->id, 'admissions_hero', [
            'section_type' => 'page_hero',
            'title' => 'Admissions',
            'subtitle' => 'Welcome to a journey of excellence. Discover a nurturing environment where every child\'s potential is unlocked. Join our Royal Kings family today.',
            'settings' => ['image_url' => $this->img.'/326278193-5858633464182480-7349085187052583899-n-1695x1130.jpg'],
            'sort_order' => 0,
        ]);

        $this->upsertSection($page->id, 'admissions_requirements', [
            'section_type' => 'list_columns',
            'title' => 'Application Requirements',
            'settings' => [
                'items' => [
                    [
                        'title' => 'New Student (beginners)',
                        'description' => "Preschool learners should be at least 2.5 years old by January\nAuthentic birth certificate required\nApplicants undergo screening and assessment\nComplete the online application form",
                    ],
                    [
                        'title' => 'Transfer (from another school)',
                        'description' => "Complete the online application form\nLearners invited for entry assessment after application\nUpon clearance: birth certificate, transfer letter, NEMIS number, KNEC assessment number",
                    ],
                ],
                'promo_image' => $this->img.'/2025-admissions-815x815.png',
            ],
            'sort_order' => 1,
        ]);

        $this->upsertSection($page->id, 'admissions_cta', [
            'section_type' => 'cta_banner',
            'title' => '2025 Admissions Open',
            'content' => 'Limited spaces available. Begin your child\'s Royal Kings journey today.',
            'settings' => ['href' => '/admissions/apply', 'label' => 'Apply Now'],
            'sort_order' => 2,
        ]);
    }

    private function page(string $slug, string $title): ?Page
    {
        return Page::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $title, 'title' => $title, 'status' => Page::STATUS_PUBLISHED, 'published_at' => now()]
        );
    }

    private function upsertSection(int $pageId, string $key, array $data): void
    {
        PageSection::query()->updateOrCreate(
            ['page_id' => $pageId, 'section_key' => $key],
            $data + ['page_id' => $pageId, 'is_active' => true]
        );
    }
}
