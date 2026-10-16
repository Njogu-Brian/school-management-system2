<?php

namespace App\Services\Website;

use App\Models\Website\MediaLibraryItem;
use App\Models\Website\PageSection;
use App\Models\Website\WebsiteBrandItem;
use App\Models\Website\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class WebsiteMediaService
{
    public function directory(): string
    {
        $path = public_path('website');

        if (! is_dir($path) && ! @mkdir($path, 0775, true) && ! is_dir($path)) {
            throw new \RuntimeException('Unable to create the website media directory.');
        }

        return $path;
    }

    public function absolutePath(string $relativePath): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    }

    public function store(UploadedFile $file, string $subdir = ''): string
    {
        $targetDir = $this->directory();

        if ($subdir !== '') {
            $targetDir .= DIRECTORY_SEPARATOR.trim($subdir, '/\\');
        }

        if (! is_dir($targetDir) && ! @mkdir($targetDir, 0775, true) && ! is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create the website media directory. Ask an administrator to make public/website writable by the web server.');
        }

        $filename = time().'_'.uniqid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move($targetDir, $filename);

        return $subdir !== '' ? trim($subdir, '/\\').'/'.$filename : $filename;
    }

    public function delete(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        $path = $this->absolutePath($relativePath);

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    public function deleteItem(MediaLibraryItem $item, MediaOptimizationService $optimizer): void
    {
        $optimizer->deleteVariants($item);
        $this->delete($item->file_path);
    }

    public function detectType(UploadedFile $file): string
    {
        $mime = '';
        if (is_file($file->getPathname())) {
            $mime = $file->getMimeType() ?? '';
        }
        if ($mime === '') {
            $mime = $file->getClientMimeType() ?? '';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        return 'document';
    }

    public function storeLibraryImage(UploadedFile $file, string $title, ?int $userId = null): MediaLibraryItem
    {
        $type = $this->detectType($file);
        $originalName = $file->getClientOriginalName();
        $path = $this->store($file, 'sections');
        $size = @getimagesize($this->absolutePath($path)) ?: [null, null];

        return MediaLibraryItem::create([
            'title' => $title !== '' ? $title : pathinfo($originalName, PATHINFO_FILENAME),
            'file_path' => $path,
            'type' => $type,
            'category' => 'website',
            'alt_text' => $title,
            'width' => $size[0] ?: null,
            'height' => $size[1] ?: null,
            'optimization_status' => MediaLibraryItem::OPT_SKIPPED,
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function applySectionUploads(Request $request, array $settings): array
    {
        if ($request->hasFile('section_image')) {
            $item = $this->storeLibraryImage(
                $request->file('section_image'),
                (string) $request->input('title', 'Website image'),
                $request->user()?->id
            );
            $settings['image_url'] = $item->url();
        }

        $photos = is_array($settings['photos'] ?? null) ? $settings['photos'] : [];
        foreach ($request->file('photo_replace', []) as $index => $file) {
            if (! $file instanceof UploadedFile || ! isset($photos[$index]) || ! is_array($photos[$index])) {
                continue;
            }
            $item = $this->storeLibraryImage($file, (string) ($photos[$index]['title'] ?? 'Gallery photo'), $request->user()?->id);
            $photos[$index]['src'] = $item->url();
        }

        foreach ($request->file('photo_files', []) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $item = $this->storeLibraryImage($file, 'Website photo', $request->user()?->id);
            $photos[] = [
                'src' => $item->url(),
                'title' => $item->title,
                'caption' => '',
                'href' => '',
            ];
        }

        if ($photos !== []) {
            $settings['photos'] = array_values($photos);
        }

        return $settings;
    }

    /**
     * @return list<array{type: string, label: string}>
     */
    public function usages(MediaLibraryItem $item): array
    {
        $needle = basename((string) $item->file_path);
        if ($needle === '') {
            return [];
        }

        $hits = [];

        PageSection::query()->select(['id', 'section_key', 'title', 'settings'])->orderBy('id')->chunk(100, function ($sections) use ($needle, &$hits) {
            foreach ($sections as $section) {
                $blob = json_encode($section->settings);
                if (is_string($blob) && str_contains($blob, $needle)) {
                    $hits[] = [
                        'type' => 'Page section',
                        'label' => $section->title ?: $section->section_key,
                    ];
                }
            }
        });

        WebsiteBrandItem::query()->where('image_url', 'like', '%'.$needle.'%')->orderBy('id')->each(function (WebsiteBrandItem $brand) use (&$hits) {
            $hits[] = ['type' => 'Brand block', 'label' => $brand->title ?: $brand->block_type];
        });

        $settings = WebsiteSetting::query()->first();
        if ($settings) {
            foreach (['logo', 'favicon', 'hero_video'] as $field) {
                $value = (string) ($settings->{$field} ?? '');
                if ($value !== '' && str_contains($value, $needle)) {
                    $hits[] = ['type' => 'Site setting', 'label' => $field];
                }
            }
        }

        return $hits;
    }

    public function replaceEverywhere(MediaLibraryItem $item, MediaLibraryItem $replacement): void
    {
        $needle = basename((string) $item->file_path);
        $replacementUrl = $replacement->url();
        if ($needle === '') {
            return;
        }

        PageSection::query()->orderBy('id')->chunk(50, function ($sections) use ($needle, $replacementUrl) {
            foreach ($sections as $section) {
                $settings = $section->settings ?? [];
                $updated = $this->replaceImageValue($settings, $needle, $replacementUrl);
                if ($updated !== $settings) {
                    $section->settings = $updated;
                    $section->save();
                }
            }
        });

        WebsiteBrandItem::query()->where('image_url', 'like', '%'.$needle.'%')->each(function (WebsiteBrandItem $brand) use ($replacementUrl) {
            $brand->image_url = $replacementUrl;
            $brand->save();
        });

        $settings = WebsiteSetting::query()->first();
        if ($settings) {
            $dirty = false;
            foreach (['logo', 'favicon'] as $field) {
                $value = (string) ($settings->{$field} ?? '');
                if ($value !== '' && str_contains($value, $needle)) {
                    $settings->{$field} = $replacement->file_path;
                    $dirty = true;
                }
            }
            if ($dirty) {
                $settings->save();
            }
        }
    }

    private function replaceImageValue(mixed $value, string $needle, string $replacementUrl): mixed
    {
        if (is_string($value)) {
            if (str_contains($value, $needle) && (str_contains($value, '/') || str_starts_with($value, 'http'))) {
                return $replacementUrl;
            }

            return $value;
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $child) {
            $value[$key] = $this->replaceImageValue($child, $needle, $replacementUrl);
        }

        return $value;
    }
}
