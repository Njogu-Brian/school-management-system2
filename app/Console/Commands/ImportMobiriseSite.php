<?php

namespace App\Console\Commands;

use App\Models\Website\MediaAlbum;
use App\Models\Website\MediaLibraryItem;
use App\Models\Website\MediaQualityFlag;
use App\Models\Website\Page;
use App\Models\Website\PageSection;
use App\Models\Website\Testimonial;
use App\Models\Website\WebsiteSetting;
use App\Services\Website\WebsitePageCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ImportMobiriseSite extends Command
{
    protected $signature = 'website:import-mobirise
        {path : Mobirise public_html directory, or a .tar.gz archive of it}
        {--dry-run : Parse and report without writing files or database rows}';

    protected $description = 'Import the Mobirise Royal Kings site into the Website CMS, including images';

    private bool $dry = false;

    private string $root = '';

    /** @var array<string, true> */
    private array $galleryFiles = [];

    /** @var array<string, array{title: string, caption: string}>|null */
    private ?array $captionMap = null;

    private int $homepageRank = 0;

    public function handle(WebsitePageCacheService $cache): int
    {
        $this->dry = (bool) $this->option('dry-run');
        $this->root = $this->resolveRoot($this->argument('path'));

        if (! is_file($this->root.'/index.html')) {
            $this->error('No index.html found in '.$this->root);

            return self::FAILURE;
        }

        $this->info('Site root: '.$this->root);

        $copied = $this->copyAssets();
        $media = $this->importMedia();
        $this->importSettings();
        $this->importPages();
        $rewritten = $this->rewriteHotlinks();

        if (! $this->dry) {
            $cache->bustHomepage();
            foreach (['about', 'fees', 'gallery', 'admissions', 'contact', 'home'] as $slug) {
                $cache->bustPageSlug($slug);
            }
            Cache::forget('website.api.settings.v2');
            Cache::forget('website.api.testimonials');
            Cache::forget('website.api.media.hero');
            foreach ([24, 48, 200, 500] as $perPage) {
                foreach (['all', 'gallery'] as $category) {
                    Cache::forget("website.api.gallery.{$category}.std.nohero.{$perPage}");
                }
            }
        }

        $this->info("Copied {$copied} asset file(s). Media records: {$media}. Hotlinks rewritten: {$rewritten}.");
        if ($this->dry) {
            $this->comment('Dry run — nothing was written.');
        }

        return self::SUCCESS;
    }

    private function resolveRoot(string $path): string
    {
        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        if (is_dir($path)) {
            return $this->findHtmlRoot($path);
        }

        if (! is_file($path)) {
            throw new \InvalidArgumentException("Path not found: {$path}");
        }

        $dest = storage_path('app/mobirise-import/site');
        if (! is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        $this->info('Extracting archive...');
        $command = 'tar -xzf '.escapeshellarg($path)
            .' -C '.escapeshellarg($dest)
            .' --exclude=admissions --exclude=biodata';
        exec($command, $output, $code);
        if ($code !== 0) {
            throw new \RuntimeException('tar extract failed (exit '.$code.').');
        }

        return $this->findHtmlRoot($dest);
    }

    private function findHtmlRoot(string $dir): string
    {
        if (is_file($dir.DIRECTORY_SEPARATOR.'index.html')) {
            return $dir;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $child = $dir.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($child) && is_file($child.DIRECTORY_SEPARATOR.'index.html')) {
                return $child;
            }
        }

        return $dir;
    }

    private function copyAssets(): int
    {
        $count = 0;
        $pairs = [
            $this->root.'/assets/images' => [
                public_path('website/imported'),
                public_path('legacy-mobirise/assets/images'),
            ],
            $this->root.'/assets/files' => [
                public_path('website/imported/files'),
                public_path('legacy-mobirise/assets/files'),
            ],
        ];

        foreach ($pairs as $source => $destinations) {
            if (! is_dir($source)) {
                $this->warn('Missing '.$source);
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $relative = substr($file->getPathname(), strlen($source) + 1);
                foreach ($destinations as $destRoot) {
                    $target = $destRoot.DIRECTORY_SEPARATOR.$relative;
                    if ($this->dry) {
                        continue;
                    }
                    $dir = dirname($target);
                    if (! is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    if (is_file($target) && filesize($target) === $file->getSize()) {
                        continue;
                    }
                    copy($file->getPathname(), $target);
                }
                $count++;
            }
        }

        return $count;
    }

    private function importMedia(): int
    {
        $imagesDir = $this->root.'/assets/images';
        if (! is_dir($imagesDir)) {
            return 0;
        }

        $this->galleryFiles = $this->imageNamesIn($this->load($this->root.'/Gallery.html'));
        $heroFile = $this->heroBackgroundFile();

        $album = null;
        if (! $this->dry) {
            $album = MediaAlbum::query()->updateOrCreate(
                ['slug' => 'campus-gallery'],
                [
                    'title' => 'Campus Gallery',
                    'category' => 'gallery',
                    'description' => 'Photos imported from the Royal Kings Mobirise gallery.',
                    'is_featured' => true,
                ]
            );
        }

        $count = 0;
        $cover = null;
        foreach (scandir($imagesDir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $full = $imagesDir.DIRECTORY_SEPARATOR.$name;
            if (! is_file($full) || ! $this->isImage($name)) {
                continue;
            }

            $inGallery = isset($this->galleryFiles[$name]);
            $isHero = $name === $heroFile;
            $filePath = 'imported/'.$name;
            $caption = $this->captionFor($name);
            $alt = $caption !== null ? $caption['title'] : ($inGallery || $isHero ? 'Royal Kings School' : null);

            if (! $this->dry) {
                $size = @getimagesize($full) ?: [null, null];
                $item = MediaLibraryItem::query()->updateOrCreate(
                    ['file_path' => $filePath],
                    [
                        'title' => $caption !== null ? $caption['title'] : $this->titleFromFilename($name),
                        'type' => 'image',
                        'category' => $inGallery ? 'gallery' : 'website',
                        'alt_text' => $alt,
                        'width' => $size[0] ?: null,
                        'height' => $size[1] ?: null,
                        'optimization_status' => MediaLibraryItem::OPT_SKIPPED,
                        'is_featured' => $isHero,
                        'album_id' => $inGallery && $album ? $album->id : null,
                    ]
                );

                if ($isHero || $caption) {
                    MediaQualityFlag::query()->updateOrCreate(
                        ['media_id' => $item->id],
                        [
                            'approved' => true,
                            'hero_ready' => true,
                            'homepage_ready' => true,
                            'priority' => $isHero ? 100 : max(10, 90 - $this->homepageRank++),
                        ]
                    );
                }

                if ($inGallery && $cover === null) {
                    $cover = $filePath;
                }
            }

            $count++;
        }

        if (! $this->dry && $album && $cover) {
            $album->cover_image = $cover;
            $album->save();
        }

        return $count;
    }

    private function importSettings(): void
    {
        $dom = $this->load($this->root.'/index.html');
        $xp = new \DOMXPath($dom);

        $tagline = $this->firstText($xp, '//section[contains(@class,"header16")]//h1');
        $email = $this->decodeEmails($dom)[0] ?? 'info@royalkingsschools.sc.ke';
        $map = '';
        $iframe = $xp->query('//iframe[contains(@src,"google.com/maps")]')->item(0);
        if ($iframe instanceof \DOMElement) {
            $map = $dom->saveHTML($iframe) ?: '';
        }

        $social = $this->socialLinks($dom);
        $logo = 'imported/royal-logo-small-192x192.png';
        $favicon = 'imported/royal-logo-128x128.png';

        $payload = [
            'school_name' => 'Royal Kings Premier School',
            'tagline' => $tagline !== '' ? $tagline : 'Building a Sure Foundation',
            'phone' => '+254 719 396 233',
            'whatsapp' => '254719396233',
            'email' => $email,
            'address' => 'Riverside Wangige, Western Bypass, Kiambu. P.O BOX 10804-00100 Nairobi',
            'google_map' => $map,
            'facebook' => $social['facebook'] ?? null,
            'instagram' => $social['instagram'] ?? null,
            'tiktok' => $social['tiktok'] ?? null,
            'logo' => is_file(public_path('website/'.$logo)) || $this->dry ? $logo : null,
            'favicon' => is_file(public_path('website/'.$favicon)) || $this->dry ? $favicon : null,
            'admissions_open' => true,
        ];

        $this->line('Settings: '.$payload['school_name'].' / '.$payload['tagline'].' / '.$payload['email']);

        if ($this->dry) {
            return;
        }

        $setting = WebsiteSetting::current();
        $setting->fill(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
        $setting->save();
    }

    private function importPages(): void
    {
        $home = $this->load($this->root.'/index.html');
        $this->importHomepage($home);
        $this->importTestimonials($home);

        $this->importAbout($this->load($this->root.'/About Us.html'));
        $this->importFees($this->load($this->root.'/Fees.html'));
        $this->importGalleryPage($this->load($this->root.'/Gallery.html'));
        $this->importAdmissions($this->load($this->root.'/Enroll Now.html'));
        $contactFile = $this->root.'/Contac Us.html';
        $this->importContactPage(is_file($contactFile) ? $this->load($contactFile) : $home);
    }

    private function importHomepage(\DOMDocument $dom): void
    {
        $page = $this->page('home', 'Homepage', 'Royal Kings Education Centre', $dom, true);
        $xp = new \DOMXPath($dom);

        $hero = $xp->query('//section[contains(@class,"header16")]')->item(0);
        $heroTitle = $hero instanceof \DOMElement ? $this->heading($hero) : 'Building a Sure Foundation';
        $heroSubtitle = $hero instanceof \DOMElement ? $this->firstText($xp, '//section[contains(@class,"header16")]//h2') : 'Where Learning is Fun!';
        $heroBody = $hero instanceof \DOMElement ? $this->cleanCopy($this->paragraphs($hero)) : '';
        $heroFile = $this->heroBackgroundFile();

        $this->section($page, 'hero_main', [
            'section_type' => 'hero',
            'title' => $heroTitle,
            'subtitle' => $heroSubtitle,
            'content' => $heroBody,
            'settings' => [
                'image_url' => $this->imageUrl($heroFile),
                'href' => '/admissions',
                'label' => 'Join Us',
            ],
            'sort_order' => 0,
        ]);

        $banner = '2027-admissions-open.jpg';
        $this->section($page, 'home_promos', [
            'section_type' => 'photo_grid',
            'title' => '2027 Admissions Open',
            'settings' => [
                'photos' => [[
                    'src' => $this->imageUrl($banner),
                    'title' => '2027 Admissions Now Open',
                    'href' => '/admissions/apply',
                ]],
            ],
            'sort_order' => 1,
        ]);

        $empowering = $xp->query('//section[contains(@class,"article4 ")]')->item(0);
        if ($empowering instanceof \DOMElement) {
            $image = $this->images($empowering)[0]['file'] ?? null;
            $this->section($page, 'school_story_empowering', [
                'section_type' => 'school_story',
                'title' => $this->heading($empowering),
                'subtitle' => 'Since 2006',
                'content' => $this->paragraphs($empowering),
                'settings' => array_filter([
                    'variant' => 'empowering',
                    'image_url' => $image ? $this->imageUrl($image) : null,
                    'href' => '/about',
                    'label' => 'Our Story',
                ]),
                'sort_order' => 2,
            ]);
        }

        $mission = $xp->query('//section[contains(@class,"article05")]')->item(0);
        $pillars = $xp->query('//section[contains(@class,"features037")]')->item(0);
        if ($mission instanceof \DOMElement) {
            $image = $this->images($mission)[0]['file'] ?? null;
            $items = $pillars instanceof \DOMElement ? $this->cards($pillars) : [];
            $this->section($page, 'school_story_mission', [
                'section_type' => 'school_story',
                'title' => $this->heading($mission),
                'subtitle' => 'Our Mission',
                'content' => $this->paragraphs($mission),
                'settings' => array_filter([
                    'variant' => 'mission',
                    'image_url' => $image ? $this->imageUrl($image) : null,
                    'items' => $items,
                ]),
                'sort_order' => 3,
            ]);
        }

        if ($pillars instanceof \DOMElement) {
            $this->section($page, 'home_values', [
                'section_type' => 'card_grid',
                'title' => $this->heading($pillars) ?: 'Unleash Your Potential Today',
                'subtitle' => $this->firstText(new \DOMXPath($dom), '//section[contains(@class,"features037")]//h6'),
                'settings' => [
                    'items' => $this->cards($pillars),
                    'href' => '/admissions',
                    'label' => 'Start Your Adventure',
                ],
                'sort_order' => 4,
            ]);

            $this->section($page, 'home_cta', [
                'section_type' => 'cta',
                'title' => $this->heading($pillars) ?: 'Unleash Your Potential Today',
                'subtitle' => 'Embrace education that goes beyond textbooks and unlocks your true capabilities.',
                'settings' => ['href' => '/admissions', 'label' => 'Start Your Adventure'],
                'sort_order' => 5,
            ]);
        }
    }

    private function importTestimonials(\DOMDocument $dom): void
    {
        $xp = new \DOMXPath($dom);
        $block = $xp->query('//section[contains(@class,"people05")]')->item(0);
        if (! $block instanceof \DOMElement) {
            return;
        }

        $quotes = [];
        foreach ($xp->query('.//*[contains(@class,"card-text")]', $block) as $node) {
            $message = $this->plain($node);
            if ($message === '') {
                continue;
            }
            $card = $node->parentNode;
            $photo = null;
            if ($card instanceof \DOMElement) {
                $imgs = $this->images($card);
                $photo = $imgs[0]['file'] ?? null;
            }
            $quotes[] = ['message' => $message, 'photo' => $photo];
        }

        $this->line('Testimonials: '.count($quotes));

        foreach ($quotes as $index => $quote) {
            if ($this->dry) {
                continue;
            }
            Testimonial::query()->updateOrCreate(
                ['message' => $quote['message']],
                [
                    'name' => 'Anonymous Parent',
                    'relationship' => 'Parent',
                    'photo' => $quote['photo'] ? 'imported/'.$quote['photo'] : null,
                    'featured' => $index === 0,
                    'approved' => true,
                ]
            );
        }
    }

    private function importAbout(\DOMDocument $dom): void
    {
        $page = $this->page('about', 'About', 'About Royal Kings', $dom, false);
        $xp = new \DOMXPath($dom);

        $welcome = $xp->query('//section[contains(@class,"article05")]')->item(0);
        $story = $xp->query('//section[contains(@class,"article12")]')->item(0);
        $facilities = $xp->query('//section[contains(@class,"features030")]')->item(0);

        if ($welcome instanceof \DOMElement) {
            $image = $this->images($welcome)[0]['file'] ?? null;
            $this->section($page, 'about_hero', [
                'section_type' => 'page_hero',
                'title' => $this->heading($welcome),
                'subtitle' => $this->paragraphs($welcome),
                'settings' => array_filter([
                    'image_url' => $image ? $this->imageUrl($image) : null,
                ]),
                'sort_order' => 0,
            ]);
        }

        if ($story instanceof \DOMElement) {
            $this->section($page, 'about_story', [
                'section_type' => 'rich_text',
                'title' => $this->heading($story) ?: 'Our Story',
                'content' => $this->paragraphs($story),
                'sort_order' => 1,
            ]);
        }

        if ($facilities instanceof \DOMElement) {
            $labels = ['Location', 'Facilities', 'Transport', 'Classrooms'];
            $items = [];
            foreach ($facilities->getElementsByTagName('li') as $li) {
                $text = $this->plain($li);
                if ($text === '') {
                    continue;
                }
                $items[] = [
                    'title' => $labels[count($items)] ?? 'Campus',
                    'description' => $text,
                ];
            }
            $image = $this->images($facilities)[0]['file'] ?? null;
            $this->section($page, 'about_facilities', [
                'section_type' => 'info_grid',
                'title' => $this->heading($facilities) ?: 'Facilities',
                'settings' => array_filter([
                    'items' => $items,
                    'image_url' => $image ? $this->imageUrl($image) : null,
                ]),
                'sort_order' => 2,
            ]);
        }
    }

    private function importFees(\DOMDocument $dom): void
    {
        $page = $this->page('fees', 'Fees', 'School Fees', $dom, false);
        $xp = new \DOMXPath($dom);
        $block = $xp->query('//section[contains(@class,"article021")]')->item(0);
        if (! $block instanceof \DOMElement) {
            return;
        }

        $pdf = null;
        foreach ($block->getElementsByTagName('a') as $anchor) {
            $href = $anchor->getAttribute('href');
            if (str_contains($href, 'assets/files/')) {
                $pdf = '/assets/files/'.basename($href);
            }
        }

        $included = $this->firstText($xp, '//section[contains(@class,"article021")]//h6');

        $this->section($page, 'fees_hero', [
            'section_type' => 'page_hero',
            'title' => 'Fees',
            'subtitle' => $included,
            'sort_order' => 0,
        ]);

        $this->section($page, 'fees_intro', [
            'section_type' => 'editorial_intro',
            'title' => 'Fees',
            'content' => 'The following is included in school fees: academic tuition, meals, sports and clubs, and house and interclass activities.',
            'subtitle' => $included,
            'sort_order' => 1,
        ]);

        $this->section($page, 'fees_cta', [
            'section_type' => 'cta_banner',
            'title' => 'Latest Fee Structure',
            'content' => 'Download the current combined fee structure, or contact admissions if you need help.',
            'settings' => [
                'href' => $pdf ?: '/contact',
                'label' => $pdf ? 'Download Latest Fees' : 'Contact Admissions',
            ],
            'sort_order' => 3,
        ]);
    }

    private function importGalleryPage(\DOMDocument $dom): void
    {
        $page = $this->page('gallery', 'Gallery', 'Gallery', $dom, false);
        $photos = [];
        foreach ($this->imagesFrom($dom) as $image) {
            if (str_contains($image['file'], 'royal-logo') || $this->isRetiredImage($image['file'])) {
                continue;
            }
            $caption = $this->captionFor($image['file']);
            $photos[] = [
                'src' => $this->imageUrl($image['file']),
                'title' => $caption['title'] ?? $this->titleFromFilename($image['file']),
                'caption' => $caption['caption'] ?? 'Campus life at Royal Kings, Wangige',
            ];
        }

        $this->section($page, 'gallery_hero', [
            'section_type' => 'page_hero',
            'title' => 'Gallery',
            'subtitle' => $this->meta($dom, 'description') ?: 'A sneak peek of how learning is fun at Royal Kings.',
            'settings' => [
                'image_url' => $photos[0]['src'] ?? null,
            ],
            'sort_order' => 0,
        ]);

        $this->section($page, 'gallery_grid', [
            'section_type' => 'photo_grid',
            'title' => 'Campus Life in Pictures',
            'subtitle' => count($photos).' photos from the Royal Kings gallery.',
            'settings' => ['photos' => $photos],
            'sort_order' => 1,
        ]);

        $this->line('Gallery photos: '.count($photos));
    }

    private function importAdmissions(\DOMDocument $dom): void
    {
        $page = $this->page('admissions', 'Admissions', 'Admissions', $dom, false);
        $xp = new \DOMXPath($dom);
        $hero = $xp->query('//section[contains(@class,"header16")]')->item(0);
        $reqs = $xp->query('//section[contains(@class,"article07")]')->item(0);

        if ($hero instanceof \DOMElement) {
            $image = $this->images($hero)[0]['file'] ?? null;
            $this->section($page, 'admissions_hero', [
                'section_type' => 'page_hero',
                'title' => $this->heading($hero) ?: 'Admissions',
                'subtitle' => $this->paragraphs($hero),
                'settings' => array_filter([
                    'image_url' => $image ? $this->imageUrl($image) : null,
                ]),
                'sort_order' => 0,
            ]);
        }

        if ($reqs instanceof \DOMElement) {
            $items = [];
            foreach ($xp->query('.//h4', $reqs) as $heading) {
                $title = $this->plain($heading);
                $parent = $heading->parentNode;
                $description = $parent instanceof \DOMElement ? $this->paragraphs($parent) : '';
                if ($title !== '') {
                    $items[] = ['title' => $title, 'description' => $description];
                }
            }
            $this->section($page, 'admissions_requirements', [
                'section_type' => 'list_columns',
                'title' => 'Application Requirements',
                'settings' => ['items' => $items],
                'sort_order' => 1,
            ]);
        }

        $this->section($page, 'admissions_cta', [
            'section_type' => 'cta_banner',
            'title' => 'Your Journey with Us Starts Here',
            'content' => 'Complete the online application. An admissions representative will contact you to arrange a school visit.',
            'settings' => ['href' => '/admissions/apply', 'label' => 'Apply Now'],
            'sort_order' => 2,
        ]);
    }

    private function importContactPage(\DOMDocument $home): void
    {
        $page = $this->page('contact', 'Contact', 'Contact Us', $home, false);
        $xp = new \DOMXPath($home);
        $block = $xp->query('//section[contains(@class,"contacts03")]')->item(0);
        $body = $block instanceof \DOMElement ? $this->plain($block) : '';

        $this->section($page, 'contact_details', [
            'section_type' => 'rich_text',
            'title' => 'Contact Us',
            'content' => $body,
            'sort_order' => 0,
        ]);
    }

    private function rewriteHotlinks(): int
    {
        $count = 0;
        if ($this->dry) {
            return 0;
        }

        PageSection::query()->orderBy('id')->each(function (PageSection $section) use (&$count) {
            $settings = $section->settings;
            if (! is_array($settings)) {
                return;
            }
            $next = $this->rewriteValue($settings, $changed);
            if ($changed) {
                $section->settings = $next;
                $section->save();
                $count++;
            }
        });

        return $count;
    }

    private function rewriteValue(mixed $value, ?bool &$changed = null): mixed
    {
        $changed = false;
        if (is_string($value)) {
            $next = preg_replace(
                '#https?://royalkingsschools\.sc\.ke/assets/images/#',
                rtrim($this->imageUrl(''), '/').'/',
                $value
            ) ?? $value;
            $changed = $next !== $value;

            return $next;
        }

        if (! is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $key => $item) {
            $out[$key] = $this->rewriteValue($item, $childChanged);
            $changed = $changed || $childChanged;
        }

        return $out;
    }

    private function page(string $slug, string $name, string $title, \DOMDocument $dom, bool $homepage): Page
    {
        $metaTitle = $this->documentTitle($dom) ?: $title;
        $description = $this->meta($dom, 'description');
        $attributes = [
            'name' => $name,
            'title' => $title,
            'meta_title' => $metaTitle,
            'meta_description' => $description !== '' ? $description : null,
            'status' => Page::STATUS_PUBLISHED,
            'is_homepage' => $homepage,
            'published_at' => now(),
        ];

        if ($this->dry) {
            $page = new Page($attributes);
            $page->id = 0;

            return $page;
        }

        return Page::query()->updateOrCreate(['slug' => $slug], $attributes);
    }

    /**
     * title and subtitle are VARCHAR(255). Longer hero copy belongs in content.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fitSectionColumns(array $data): array
    {
        foreach (['title', 'subtitle'] as $column) {
            $value = $data[$column] ?? null;
            if (! is_string($value) || mb_strlen($value) <= 250) {
                continue;
            }
            $existing = trim((string) ($data['content'] ?? ''));
            $data['content'] = $existing === '' ? $value : $value."\n\n".$existing;
            $data[$column] = Str::limit($value, 240, '…');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function section(Page $page, string $key, array $data): void
    {
        if ($this->dry || ! $page->id) {
            $this->line("  section {$key} ({$data['section_type']})");

            return;
        }

        $data = $this->fitSectionColumns($data);

        PageSection::query()->updateOrCreate(
            ['page_id' => $page->id, 'section_key' => $key],
            $data + ['is_active' => true]
        );
    }

    private function load(string $path): \DOMDocument
    {
        $html = is_file($path) ? (string) file_get_contents($path) : '';
        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $dom;
    }

    private function imageUrl(string $filename): string
    {
        $base = rtrim(asset('website/imported'), '/');
        if ($filename === '') {
            return $base;
        }

        return $base.'/'.str_replace('%2F', '/', rawurlencode($filename));
    }

    private function heroBackgroundFile(): string
    {
        $fallback = 'bg-1-1920x1280.jpg';
        $cssPath = $this->root.'/assets/mobirise/css/mbr-additional.css';
        if (! is_file($cssPath)) {
            return $fallback;
        }

        $css = (string) file_get_contents($cssPath);
        if (preg_match('/cid-ua6ITwx3ST[^{]*\{[^}]*background-image:\s*url\(([^)]+)\)/s', $css, $match)) {
            return basename(trim($match[1], "\"' "));
        }

        if (preg_match('#assets/images/([^)\'"]+)#', $css, $match)) {
            return basename($match[1]);
        }

        return $fallback;
    }

    /**
     * @return array<string, true>
     */
    private function imageNamesIn(\DOMDocument $dom): array
    {
        $names = [];
        foreach ($this->imagesFrom($dom) as $image) {
            $names[$image['file']] = true;
        }

        return $names;
    }

    /**
     * @return list<array{file: string, alt: string}>
     */
    private function imagesFrom(\DOMDocument $dom): array
    {
        $body = $dom->getElementsByTagName('body')->item(0);

        return $body instanceof \DOMElement ? $this->images($body) : [];
    }

    /**
     * @return list<array{file: string, alt: string}>
     */
    private function images(\DOMElement $scope): array
    {
        $found = [];
        foreach ($scope->getElementsByTagName('img') as $img) {
            $src = html_entity_decode($img->getAttribute('src'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (! str_contains($src, 'assets/images/')) {
                continue;
            }
            $path = parse_url($src, PHP_URL_PATH) ?: $src;
            $file = basename($path);
            if ($file === '' || str_starts_with($file, 'data:')) {
                continue;
            }
            $found[] = ['file' => $file, 'alt' => trim($img->getAttribute('alt'))];
        }

        return $found;
    }

    private function heading(\DOMElement $section): string
    {
        foreach (['h1', 'h2', 'h3', 'h4', 'h5'] as $tag) {
            $node = $section->getElementsByTagName($tag)->item(0);
            if ($node) {
                $text = $this->plain($node);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    private function paragraphs(\DOMElement $section): string
    {
        $parts = [];
        foreach ($section->getElementsByTagName('p') as $paragraph) {
            $class = $paragraph->getAttribute('class');
            if ($class !== '' && ! str_contains($class, 'mbr-text') && ! str_contains($class, 'card-text') && ! str_contains($class, 'mbr-section-subtitle')) {
                continue;
            }
            $text = $this->plain($paragraph);
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * @return list<array{title: string, description: string}>
     */
    private function cards(\DOMElement $section): array
    {
        $items = [];
        $xp = new \DOMXPath($section->ownerDocument);
        foreach ($xp->query('.//*[contains(@class,"card-title")]', $section) as $titleNode) {
            if (! $titleNode instanceof \DOMElement) {
                continue;
            }
            $title = $this->plain($titleNode);
            if ($title === '' || strcasecmp($title, 'Anonymous Parent') === 0) {
                continue;
            }
            $parent = $titleNode->parentNode;
            $description = '';
            if ($parent instanceof \DOMElement) {
                foreach ($parent->getElementsByTagName('p') as $paragraph) {
                    $description = $this->plain($paragraph);
                    if ($description !== '') {
                        break;
                    }
                }
            }
            $items[] = ['title' => $title, 'description' => $description];
        }

        return $items;
    }

    private function plain(\DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument?->saveHTML($child) ?? '';
        }
        if ($html === '') {
            $html = $node->textContent ?? '';
        }
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(p|div|li|h[1-6])>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n[ \t]+/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function firstText(\DOMXPath $xp, string $query): string
    {
        $node = $xp->query($query)->item(0);

        return $node ? $this->plain($node) : '';
    }

    private function meta(\DOMDocument $dom, string $name): string
    {
        $node = (new \DOMXPath($dom))->query('//meta[@name="'.$name.'"]')->item(0);

        return $node instanceof \DOMElement ? trim($node->getAttribute('content')) : '';
    }

    private function documentTitle(\DOMDocument $dom): string
    {
        $node = $dom->getElementsByTagName('title')->item(0);

        return $node ? trim($node->textContent) : '';
    }

    /**
     * @return list<string>
     */
    private function decodeEmails(\DOMDocument $dom): array
    {
        $emails = [];
        $xp = new \DOMXPath($dom);
        foreach ($xp->query('//*[@data-cfemail]') as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }
            $decoded = $this->decodeCfEmail($node->getAttribute('data-cfemail'));
            if (str_contains($decoded, '@')) {
                $emails[] = $decoded;
            }
        }

        return array_values(array_unique($emails));
    }

    private function decodeCfEmail(string $hex): string
    {
        if (strlen($hex) < 4 || strlen($hex) % 2 !== 0) {
            return '';
        }
        $key = hexdec(substr($hex, 0, 2));
        $out = '';
        for ($i = 2; $i < strlen($hex); $i += 2) {
            $out .= chr(hexdec(substr($hex, $i, 2)) ^ $key);
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function socialLinks(\DOMDocument $dom): array
    {
        $links = [];
        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $href = $anchor->getAttribute('href');
            foreach (['facebook', 'instagram', 'tiktok', 'youtube'] as $network) {
                if (str_contains($href, $network)) {
                    $links[$network] = $href;
                }
            }
        }

        return $links;
    }

    private function isImage(string $name): bool
    {
        return (bool) preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $name);
    }

    private function titleFromFilename(string $name): string
    {
        $base = preg_replace('/\.[^.]+$/', '', $name) ?? $name;
        $base = preg_replace('/-\d+x\d+$/', '', $base) ?? $base;

        return Str::limit(str_replace(['-', '_'], ' ', $base), 180, '');
    }

    private function isRetiredImage(string $file): bool
    {
        $file = strtolower($file);

        return str_contains($file, '2025-admissions')
            || str_contains($file, 'whatsapp-image-2024-10-28-at-18.13.54');
    }

    private function cleanAlt(string $alt, string $filename): string
    {
        if ($alt === '' || stripos($alt, 'mobirise') !== false) {
            return $this->titleFromFilename($filename);
        }

        return $alt;
    }

    /**
     * @return array{title: string, caption: string}|null
     */
    private function captionFor(string $filename): ?array
    {
        $map = $this->captions();
        $row = $map[strtolower($filename)] ?? null;
        if (! is_array($row) || empty($row['title'])) {
            return null;
        }

        return [
            'title' => (string) $row['title'],
            'caption' => (string) ($row['caption'] ?? 'Campus life at Royal Kings, Wangige'),
        ];
    }

    /**
     * @return array<string, array{title?: string, caption?: string}>
     */
    private function captions(): array
    {
        if ($this->captionMap !== null) {
            return $this->captionMap;
        }

        $path = database_path('data/mobirise-captions.json');
        $decoded = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
        $this->captionMap = is_array($decoded) ? $decoded : [];

        return $this->captionMap;
    }

    private function cleanCopy(string $text): string
    {
        return str_replace(
            ['instituiton', 'speaks Volumes'],
            ['institution', 'speaks volumes'],
            $text
        );
    }
}
