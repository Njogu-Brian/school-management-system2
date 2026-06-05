<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Support\Facades\Storage;

trait ResolvesDocumentStorage
{
    protected function resolveDiskForPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $configuredPublic = config('filesystems.public_disk', 'public');
        $configuredPrivate = config('filesystems.private_disk', 'private');

        try {
            if (Storage::disk($configuredPublic)->exists($path)) {
                return $configuredPublic;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        try {
            if (Storage::disk($configuredPrivate)->exists($path)) {
                return $configuredPrivate;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        foreach (['public', 'private', 's3'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return $disk;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }
}
