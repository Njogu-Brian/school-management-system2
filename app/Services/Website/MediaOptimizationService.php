<?php

namespace App\Services\Website;

use App\Models\Website\MediaLibraryItem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MediaOptimizationService
{
    /** @var array<int, string> */
    public const SIZES = [
        480 => 'sm',
        768 => 'md',
        1200 => 'lg',
        1920 => 'xl',
    ];

    public function __construct(private WebsiteMediaService $media)
    {
    }

    public function supportsOptimization(): bool
    {
        return extension_loaded('gd') && function_exists('imagewebp');
    }

    /**
     * @return array<string, array{webp: string, w: int, h: int}>|null
     */
    public function optimize(MediaLibraryItem $item): ?array
    {
        if ($item->type !== 'image') {
            $item->update(['optimization_status' => 'skipped']);

            return null;
        }

        if (! $this->supportsOptimization()) {
            $item->update(['optimization_status' => 'skipped']);

            return null;
        }

        $sourcePath = $this->media->absolutePath($item->file_path);

        if (! is_file($sourcePath)) {
            $item->update(['optimization_status' => 'failed']);

            return null;
        }

        $image = $this->loadImage($sourcePath);

        if ($image === null) {
            $item->update(['optimization_status' => 'failed']);

            return null;
        }

        $origW = imagesx($image);
        $origH = imagesy($image);

        $this->deleteVariants($item);

        $baseName = pathinfo($item->file_path, PATHINFO_FILENAME);
        $subdir = trim(pathinfo($item->file_path, PATHINFO_DIRNAME), '.\\/');
        $outDir = $this->media->directory().DIRECTORY_SEPARATOR.'optimized';
        if ($subdir !== '') {
            $outDir .= DIRECTORY_SEPARATOR.$subdir;
        }
        if (! is_dir($outDir)) {
            @mkdir($outDir, 0755, true);
        }

        $variants = [];

        foreach (self::SIZES as $targetWidth => $key) {
            if ($origW < 320 && $key !== 'sm') {
                continue;
            }

            $newW = min($targetWidth, $origW);
            $newH = (int) max(1, round($origH * ($newW / $origW)));
            $resized = imagecreatetruecolor($newW, $newH);

            if ($resized === false) {
                continue;
            }

            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

            $filename = $baseName.'-'.$key.'.webp';
            $absoluteOut = $outDir.DIRECTORY_SEPARATOR.$filename;
            $relativeOut = 'optimized'.($subdir !== '' ? '/'.$subdir : '').'/'.$filename;

            if (imagewebp($resized, $absoluteOut, 82)) {
                $variants[$key] = [
                    'webp' => str_replace('\\', '/', $relativeOut),
                    'w' => $newW,
                    'h' => $newH,
                ];
            }

            imagedestroy($resized);
        }

        imagedestroy($image);

        $primary = $variants['lg']['webp'] ?? $variants['md']['webp'] ?? $variants['sm']['webp'] ?? null;

        $item->update([
            'variants' => $variants,
            'optimized_path' => $primary,
            'optimization_status' => $variants !== [] ? 'completed' : 'failed',
            'width' => $origW,
            'height' => $origH,
        ]);

        return $variants;
    }

    public function deleteVariants(MediaLibraryItem $item): void
    {
        $variants = $item->variants ?? [];

        foreach ($variants as $variant) {
            if (! empty($variant['webp'])) {
                $this->media->delete($variant['webp']);
            }
        }

        if ($item->optimized_path) {
            $this->media->delete($item->optimized_path);
        }
    }

    /**
     * @return resource|null
     */
    private function loadImage(string $path)
    {
        $info = @getimagesize($path);

        if ($info === false) {
            return null;
        }

        return match ($info['mime'] ?? '') {
            'image/jpeg' => @imagecreatefromjpeg($path) ?: null,
            'image/png' => @imagecreatefrompng($path) ?: null,
            'image/webp' => @imagecreatefromwebp($path) ?: null,
            'image/gif' => @imagecreatefromgif($path) ?: null,
            default => null,
        };
    }

    public function optimizeAllPending(): int
    {
        $count = 0;

        MediaLibraryItem::query()
            ->where('type', 'image')
            ->whereIn('optimization_status', ['pending', 'failed'])
            ->orderBy('id')
            ->chunkById(50, function ($items) use (&$count) {
                foreach ($items as $item) {
                    try {
                        $this->optimize($item);
                        $count++;
                    } catch (\Throwable $e) {
                        Log::warning('Media optimization failed', ['id' => $item->id, 'error' => $e->getMessage()]);
                        $item->update(['optimization_status' => 'failed']);
                    }
                }
            });

        return $count;
    }
}
