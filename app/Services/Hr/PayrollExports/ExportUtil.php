<?php

namespace App\Services\Hr\PayrollExports;

use Illuminate\Support\Facades\Storage;

final class ExportUtil
{
    public static function sha256ForDiskPath(string $disk, string $path): ?string
    {
        try {
            $full = Storage::disk($disk)->path($path);
            if (! is_file($full)) {
                return null;
            }
            return hash_file('sha256', $full) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}

