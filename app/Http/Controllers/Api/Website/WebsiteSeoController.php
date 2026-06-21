<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\Website\Blog;
use App\Models\Website\Page;
use App\Models\Website\WebsiteSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class WebsiteSeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('website.sitemap.xml', 3600, function () {
            $base = rtrim(config('app.url'), '/');
            $urls = [];

            foreach (['', '/about', '/academics', '/admissions', '/contact', '/blog', '/events', '/gallery'] as $path) {
                $urls[] = ['loc' => $base.$path, 'priority' => $path === '' ? '1.0' : '0.8'];
            }

            Page::query()->where('status', 'published')->each(function (Page $page) use (&$urls, $base) {
                if (! $page->is_homepage) {
                    $urls[] = ['loc' => $base.'/'.$page->slug, 'priority' => '0.7'];
                }
            });

            Blog::query()->where('published', true)->each(function (Blog $blog) use (&$urls, $base) {
                $urls[] = ['loc' => $base.'/blog/'.$blog->slug, 'priority' => '0.6'];
            });

            $body = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach ($urls as $u) {
                $body .= '<url><loc>'.e($u['loc']).'</loc><priority>'.$u['priority'].'</priority></url>';
            }
            $body .= '</urlset>';

            return $body;
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $content = "User-agent: *\nAllow: /\nSitemap: {$base}/api/website/sitemap.xml\n";

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    public static function jsonLdSchool(): array
    {
        $settings = WebsiteSetting::current();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'School',
            'name' => $settings->school_name,
            'description' => $settings->tagline,
            'telephone' => $settings->phone,
            'email' => $settings->email,
            'address' => $settings->address,
        ];
    }
}
