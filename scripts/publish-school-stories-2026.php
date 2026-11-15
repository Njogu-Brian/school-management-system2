<?php

/**
 * Publish Facebook-inspired school stories + Google Photos campus gallery:
 * - Blog posts (ballet, green Grade 3, break time, art, happy learners, advantage, trips, career day)
 * - Media Library items from curated campus-life-2026 photos
 * - Refresh website API caches
 *
 * Usage: php scripts/publish-school-stories-2026.php
 */

use App\Models\Website\Blog;
use App\Models\Website\MediaLibraryItem;
use App\Models\Website\MediaQualityFlag;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$campusDir = public_path('website/imported/campus-life-2026');
if (! is_dir($campusDir)) {
    fwrite(STDERR, "Missing campus album at {$campusDir}\n");
    exit(1);
}

$admissionsFeatured = 'imported/2027-admissions-open.jpg';
if (! is_file(public_path('website/'.$admissionsFeatured))) {
    $admissionsFeatured = null;
}

$stories = [
    [
        'slug' => 'ballet-grace-elegance-confidence',
        'title' => 'Grace, Elegance & Confidence Through Ballet',
        'excerpt' => 'Ballet at Royal Kings is a journey of creativity, discipline, balance, and self-expression — helping every child discover their gifts.',
        'published_at' => '2026-10-14 09:00:00',
        'featured' => 'imported/campus-life-2026/37-career-day.jpg',
        'is_featured' => false,
        'body' => <<<'HTML'
<p>Ballet at Royal Kings Premier School is a beautiful journey of creativity, discipline, balance, and self-expression. Our young dancers grow in coordination, flexibility, and artistic talent — and in the quiet confidence that comes from practice and performance.</p>
<p>We believe every child deserves an opportunity to discover their gifts and reach their full potential. That is why co-curricular programmes like ballet sit at the heart of our <em>Learning Is Fun</em> philosophy.</p>
<p><strong>2027 admissions are open.</strong> Give your child a place to learn, grow, explore, and shine. Call/WhatsApp 0719 396 233 · Riverside, Wangige.</p>
HTML,
    ],
    [
        'slug' => 'little-hands-big-impact',
        'title' => 'Little Hands, Big Impact — Growing a Greener Tomorrow',
        'excerpt' => 'Grade 3 learners plant, water, and care for our environment — building responsible citizens beyond books and classrooms.',
        'published_at' => '2026-10-14 10:00:00',
        'featured' => 'imported/campus-life-2026/23-nakuru-trip.jpg',
        'is_featured' => false,
        'body' => <<<'HTML'
<p>Small hands can make a big difference. Our Grade 3 learners are growing into environmental champions — planting, watering, and nurturing nature so we leave a healthier world for tomorrow.</p>
<p>Through hands-on CBC activities they learn how plants clean the air, prevent soil erosion, and beautify our surroundings. Education at Royal Kings is not only about books; it is about building responsible citizens who care for the world around them.</p>
<p>Royal Kings School — Where Learning Is Fun. <strong>2027 enrolment ongoing.</strong></p>
HTML,
    ],
    [
        'slug' => 'break-time-at-royal-kings',
        'title' => 'Break Time at Royal Kings — Learn · Grow · Excel',
        'excerpt' => 'Break time is where friendships form — connect, share, play, and grow together beyond the classroom.',
        'published_at' => '2026-10-13 09:00:00',
        'featured' => 'imported/campus-life-2026/13-lower-primary-trip.jpg',
        'is_featured' => false,
        'body' => <<<'HTML'
<p>Break time at Royal Kings is more than a pause between lessons. It is a chance for learners to connect, share, play, and grow together — building lasting friendships, teamwork, respect, kindness, and cooperation.</p>
<p>We believe great learning goes beyond the classroom. Happy playgrounds grow confident hearts.</p>
<p>Royal Kings School — Learn · Grow · Excel!</p>
HTML,
    ],
    [
        'slug' => 'creativity-in-every-stroke',
        'title' => 'Creativity in Every Stroke — Art by Our Learners',
        'excerpt' => 'From nature-inspired drawings to textured masterpieces, every piece tells a unique story from a young artist’s mind.',
        'published_at' => '2026-10-08 09:00:00',
        'featured' => 'imported/campus-life-2026/07-preschool-trip.jpg',
        'is_featured' => false,
        'body' => <<<'HTML'
<p>At Royal Kings Premier School we nurture creativity, confidence, and talent. Learners explore imagination through colour, texture, and design — flowers, wildlife, and stories told with every stroke.</p>
<p>Art gives our learners a chance to learn by creating. Every piece tells a unique story from the mind of a young artist.</p>
HTML,
    ],
    [
        'slug' => 'happy-learners-great-memories',
        'title' => 'Happy Learners Make Great Memories',
        'excerpt' => 'Every smile, friendship, and learning experience creates memories that last a lifetime at Riverside, Wangige.',
        'published_at' => '2026-10-05 09:00:00',
        'featured' => 'imported/campus-life-2026/17-lower-primary-trip.jpg',
        'is_featured' => false,
        'body' => <<<'HTML'
<p>At Royal Kings School, every smile, every friendship, and every learning experience creates memories that last a lifetime. Children learn best when they feel happy, valued, inspired, and free to discover their talents.</p>
<p>Royal Kings School — Where Learning Is Fun!<br/>
<strong>2027 enrolment &amp; intake ongoing</strong><br/>
+254 719 396 233 · Riverside, Wangige</p>
HTML,
    ],
    [
        'slug' => 'the-royal-kings-advantage',
        'title' => 'The Royal Kings Advantage — A Sure Foundation',
        'excerpt' => 'CBC-compliant, Christian-centred, caring facilitators, safe transport, rich co-curriculars — and learning that is fun.',
        'published_at' => '2026-07-26 09:00:00',
        'featured' => $admissionsFeatured ?: 'imported/campus-life-2026/35-career-day.jpg',
        'is_featured' => true,
        'body' => <<<'HTML'
<p>Give your child the Royal Kings Advantage — where learning is fun, purposeful, and built on a sure foundation.</p>
<ul>
<li>CBC compliant</li>
<li>A Christian school nurturing values &amp; confidence</li>
<li>Loving, energetic &amp; trained facilitators</li>
<li>Strong academic support and confident readers</li>
<li>Affordable fees</li>
<li>Reliable, safe school transport</li>
<li>Wide variety of extracurricular activities</li>
<li>A caring environment that nurtures every learner’s potential</li>
</ul>
<p>Riverside, Wangige · 0719 396 233 · <a href="/admissions/apply">Apply for 2027</a></p>
HTML,
    ],
    [
        'slug' => 'learning-beyond-the-gate-school-trips-2026',
        'title' => 'Learning Beyond the Gate — School Trips 2026',
        'excerpt' => 'Preschool soft-play adventures, lower primary outings, and a Nakuru wildlife experience for Grades 4–9.',
        'published_at' => '2026-08-03 09:00:00',
        'featured' => 'imported/campus-life-2026/25-nakuru-trip.jpg',
        'is_featured' => true,
        'body' => <<<'HTML'
<p>Some of the best lessons happen beyond our Riverside campus. In 2026 our learners explored soft-play adventures for preschool, outdoor discovery for lower primary, and a memorable Nakuru trip for Grades 4–9 — buffalo, fever trees, and wide open skies included.</p>
<p>Field experiences deepen CBC learning: observation, teamwork, courage, and wonder. That is the Royal Kings way — Learning Is Fun, on campus and beyond.</p>
HTML,
    ],
    [
        'slug' => 'career-day-dreams-in-action',
        'title' => 'Career Day — Dreams in Action',
        'excerpt' => 'Doctors, engineers, and builders for a day — Career Day helps every Royal Kings learner imagine their future with confidence.',
        'published_at' => '2025-08-01 09:00:00',
        'featured' => 'imported/campus-life-2026/35-career-day.jpg',
        'is_featured' => false,
        'body' => <<<'HTML'
<p>White coats, hard hats, and big smiles — Career Day at Royal Kings turns imagination into practice. Learners try on roles, ask questions, and discover that their future can be as bold as their dreams.</p>
<p>We nurture not only bright minds, but confident speakers, caring hearts, and purposeful futures.</p>
HTML,
    ],
];

$published = 0;
foreach ($stories as $story) {
    $featured = $story['featured'];
    if (! is_file(public_path('website/'.$featured))) {
        fwrite(STDERR, "Missing featured image for {$story['slug']}: {$featured}\n");
        continue;
    }

    $blog = Blog::query()->updateOrCreate(
        ['slug' => $story['slug']],
        [
            'title' => $story['title'],
            'excerpt' => $story['excerpt'],
            'body' => $story['body'],
            'featured_image' => $featured,
            'published' => true,
            'published_at' => $story['published_at'],
            'is_featured' => (bool) $story['is_featured'],
        ]
    );
    echo "Blog: {$blog->slug} (id {$blog->id})\n";
    $published++;
}

$captionByFile = [
    '01-preschool-trip.jpg' => 'Play with purpose — preschool soft-play adventure',
    '02-preschool-trip.jpg' => 'Little explorers discovering beyond the classroom',
    '05-preschool-trip.jpg' => 'Preschool trip smiles and soft-play joy',
    '07-preschool-trip.jpg' => 'Trip day joy for our youngest learners',
    '09-preschool-trip.jpg' => 'Friendship and belonging on preschool outing',
    '11-lower-primary-trip.jpg' => 'Lower primary learners outdoors',
    '13-lower-primary-trip.jpg' => 'Happy break — friendship and play',
    '15-lower-primary-trip.jpg' => 'Curious minds on lower primary outing',
    '17-lower-primary-trip.jpg' => 'Team spirit on trip day',
    '20-lower-primary-trip.jpg' => 'CBC learning alive outside the classroom',
    '21-nakuru-trip.jpg' => 'Nakuru horizons — Grades 4–9 field experience',
    '23-nakuru-trip.jpg' => 'Learning beyond the school gate',
    '25-nakuru-trip.jpg' => 'Wildlife wonders on our Nakuru trip',
    '27-nakuru-trip.jpg' => 'Nature classroom — Kenya’s landscapes first-hand',
    '30-nakuru-trip.jpg' => 'Adventure and awe that last a lifetime',
    '32-nakuru-trip.jpg' => 'Field study for Grades 4–9',
    '33-career-day.jpg' => 'Career Day — dreaming big in character',
    '35-career-day.jpg' => 'Future doctors and builders on Career Day',
    '37-career-day.jpg' => 'Confidence on show — every child discovering gifts',
    '40-career-day.jpg' => 'Ambition starts early at Career Day',
];

$featuredMedia = [
    '05-preschool-trip.jpg',
    '13-lower-primary-trip.jpg',
    '25-nakuru-trip.jpg',
    '35-career-day.jpg',
    '37-career-day.jpg',
];

$imported = 0;
$files = collect(File::files($campusDir))
    ->filter(fn ($f) => str_ends_with(strtolower($f->getFilename()), '.jpg'))
    ->sortBy(fn ($f) => $f->getFilename())
    ->values();

foreach ($files as $file) {
    $name = $file->getFilename();
    $relative = 'imported/campus-life-2026/'.$name;
    $title = $captionByFile[$name] ?? 'Campus life at Royal Kings Premier School';
    $isFeatured = in_array($name, $featuredMedia, true);

    $item = MediaLibraryItem::query()->updateOrCreate(
        ['file_path' => $relative],
        [
            'title' => $title,
            'type' => 'image',
            'category' => 'gallery',
            'alt_text' => 'Royal Kings Premier School — '.$title,
            'is_featured' => $isFeatured,
            'optimization_status' => MediaLibraryItem::OPT_SKIPPED,
        ]
    );

    MediaQualityFlag::query()->updateOrCreate(
        ['media_id' => $item->id],
        [
            'approved' => true,
            'homepage_ready' => $isFeatured,
            'hero_ready' => in_array($name, ['25-nakuru-trip.jpg', '35-career-day.jpg'], true),
            'priority' => $isFeatured ? 85 : 45,
        ]
    );

    $imported++;
}

echo "Campus gallery media imported/updated: {$imported}\n";
echo "Stories published: {$published}\n";

foreach ([
    'website.api.homepage',
    'website.api.settings.v2',
    'website.api.brand',
    'website.api.media.hero',
    'website.api.testimonials',
    'website.api.page.home',
    'website.api.page.gallery',
    'website.api.page.blog',
    'website.api.page.campus-life',
] as $key) {
    Cache::forget($key);
}

Cache::flush();
echo "Done.\n";
