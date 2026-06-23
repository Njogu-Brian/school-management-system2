<?php

namespace Database\Seeders;

use App\Models\Website\WebsiteBrandItem;
use Illuminate\Database\Seeder;

class WebsiteBrandElevationSeeder extends Seeder
{
    private const IMG = 'https://royalkingsschools.sc.ke/assets/images';

    public function run(): void
    {
        $trustPills = [
            ['title' => 'Since 2006', 'sort_order' => 0],
            ['title' => 'CBC Aligned', 'sort_order' => 1],
            ['title' => 'Safe Transport', 'sort_order' => 2],
            ['title' => 'Christian Values', 'sort_order' => 3],
            ['title' => 'Rich Co-Curricular', 'sort_order' => 4],
        ];
        foreach ($trustPills as $pill) {
            WebsiteBrandItem::query()->firstOrCreate(
                ['block_type' => WebsiteBrandItem::TYPE_TRUST_PILL, 'title' => $pill['title']],
                $pill + ['block_type' => WebsiteBrandItem::TYPE_TRUST_PILL, 'is_active' => true]
            );
        }

        $schools = [
            [
                'title' => 'Creche & Early Years',
                'subtitle' => 'Ages 3–5',
                'body' => 'Strong beginnings through play, care, and foundational learning.',
                'image_url' => self::IMG.'/family-happy-family-portrait-vectorized-character-design-23-2148163542-160x160.jpg',
                'link_url' => '/academics#early-years',
                'sort_order' => 0,
            ],
            [
                'title' => 'Primary School',
                'subtitle' => 'Grade 1–6',
                'body' => 'Academic growth, creativity, and discovery.',
                'image_url' => self::IMG.'/332419888-1246340212647102-4730361110570400332-n-1101x734.jpeg',
                'link_url' => '/academics#primary',
                'sort_order' => 1,
            ],
            [
                'title' => 'Junior Secondary',
                'subtitle' => 'Grades 7–9',
                'body' => 'Leadership, discipline, and future readiness.',
                'image_url' => self::IMG.'/325404592-1597148387416946-3846122370734442560-n-1-906x604.jpeg',
                'link_url' => '/academics#junior-secondary',
                'sort_order' => 2,
            ],
        ];
        foreach ($schools as $card) {
            WebsiteBrandItem::query()->firstOrCreate(
                ['block_type' => WebsiteBrandItem::TYPE_SCHOOL_CARD, 'title' => $card['title']],
                $card + ['block_type' => WebsiteBrandItem::TYPE_SCHOOL_CARD, 'is_active' => true]
            );
        }

        $journey = [
            ['title' => 'First Words', 'subtitle' => 'Age 3', 'body' => 'Joyful beginnings in our creche.', 'sort_order' => 0],
            ['title' => 'First Reading', 'subtitle' => 'Age 5–6', 'body' => 'Phonics and school readiness.', 'sort_order' => 1],
            ['title' => 'First Performance', 'subtitle' => 'Age 7–9', 'body' => 'Music, drama, and confidence on stage.', 'sort_order' => 2],
            ['title' => 'First Competition', 'subtitle' => 'Age 10–12', 'body' => 'Sports, STEM fairs, and talent showcases.', 'sort_order' => 3],
            ['title' => 'First Leadership Role', 'subtitle' => 'Age 13–15', 'body' => 'Prefects, mentors, and role models for younger learners.', 'sort_order' => 4],
            ['title' => 'Graduation', 'subtitle' => 'Grade 9', 'body' => 'Prepared for the next chapter with faith, character, and excellence.', 'sort_order' => 5],
        ];
        foreach ($journey as $m) {
            WebsiteBrandItem::query()->firstOrCreate(
                ['block_type' => WebsiteBrandItem::TYPE_JOURNEY_MILESTONE, 'title' => $m['title']],
                $m + ['block_type' => WebsiteBrandItem::TYPE_JOURNEY_MILESTONE, 'image_url' => self::IMG.'/whatsapp-image-2024-10-28-at-18.13.54-cb80dc6a-705x999.jpg', 'is_active' => true]
            );
        }

        $cocurricular = [
            ['title' => 'Skating', 'settings' => ['size' => 'large', 'icon' => '🛼'], 'sort_order' => 0],
            ['title' => 'Ballet', 'settings' => ['size' => 'medium', 'icon' => '🩰'], 'sort_order' => 1],
            ['title' => 'Coding', 'settings' => ['size' => 'large', 'icon' => '💻'], 'sort_order' => 2],
            ['title' => 'Robotics', 'settings' => ['size' => 'medium', 'icon' => '🤖'], 'sort_order' => 3],
            ['title' => 'Archery', 'settings' => ['size' => 'medium', 'icon' => '🏹'], 'sort_order' => 4],
            ['title' => 'Music', 'settings' => ['size' => 'large', 'icon' => '🎵'], 'sort_order' => 5],
            ['title' => 'Sports', 'settings' => ['size' => 'medium', 'icon' => '⚽'], 'sort_order' => 6],
            ['title' => 'Worship', 'settings' => ['size' => 'medium', 'icon' => '✝️'], 'sort_order' => 7],
        ];
        foreach ($cocurricular as $c) {
            WebsiteBrandItem::query()->firstOrCreate(
                ['block_type' => WebsiteBrandItem::TYPE_COCURRICULAR, 'title' => $c['title']],
                $c + ['block_type' => WebsiteBrandItem::TYPE_COCURRICULAR, 'body' => 'Beyond the classroom excellence.', 'image_url' => self::IMG.'/325404592-1597148387416946-3846122370734442560-n-1-906x604.jpeg', 'is_active' => true]
            );
        }

        $faith = [
            ['title' => 'Faith', 'body' => 'Rooted in Christian values', 'sort_order' => 0],
            ['title' => 'Family', 'body' => 'Partnership with parents', 'sort_order' => 1],
            ['title' => 'Excellence', 'body' => 'Academic and character growth', 'sort_order' => 2],
        ];
        foreach ($faith as $f) {
            WebsiteBrandItem::query()->firstOrCreate(
                ['block_type' => WebsiteBrandItem::TYPE_FAITH_PILLAR, 'title' => $f['title']],
                $f + ['block_type' => WebsiteBrandItem::TYPE_FAITH_PILLAR, 'is_active' => true]
            );
        }

        WebsiteBrandItem::query()->firstOrCreate(
            ['block_type' => WebsiteBrandItem::TYPE_SCRIPTURE, 'title' => 'Weekly Scripture'],
            [
                'body' => 'Train up a child in the way he should go; even when he is old he will not depart from it. — Proverbs 22:6',
                'is_active' => true,
            ]
        );

        WebsiteBrandItem::query()->firstOrCreate(
            ['block_type' => WebsiteBrandItem::TYPE_CHAPLAIN, 'title' => 'Chaplain\'s Message'],
            [
                'body' => 'At Royal Kings Premier School, we nurture hearts as well as minds. Every child is known, loved, and guided in faith.',
                'is_active' => true,
            ]
        );

        $leaders = [
            ['title' => 'School Director', 'subtitle' => 'Leadership', 'body' => 'Visionary leadership committed to excellence since 2006.', 'sort_order' => 0],
            ['title' => 'Head Teacher', 'subtitle' => 'Academics', 'body' => 'Dedicated to CBC excellence and whole-child development.', 'sort_order' => 1],
            ['title' => 'Chaplain', 'subtitle' => 'Spiritual Life', 'body' => 'Guiding learners in faith, character, and compassion.', 'sort_order' => 2],
        ];
        foreach ($leaders as $l) {
            WebsiteBrandItem::query()->firstOrCreate(
                ['block_type' => WebsiteBrandItem::TYPE_LEADER, 'title' => $l['title']],
                $l + ['block_type' => WebsiteBrandItem::TYPE_LEADER, 'image_url' => self::IMG.'/royal-logo-small-192x192.png', 'is_active' => true]
            );
        }
    }
}
