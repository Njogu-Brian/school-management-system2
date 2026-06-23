<?php

namespace App\Services\Website;

use App\Models\Website\MediaLibraryItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class WebsiteMediaService
{
    public function directory(): string
    {
        $path = public_path('website');

        if (! is_dir($path)) {
            @mkdir($path, 0755, true);
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
            if (! is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
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
        $mime = $file->getMimeType() ?? '';

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        return 'document';
    }
}
