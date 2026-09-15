<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MobileAppApkService
{
    public const STORAGE_KEY = 'mobile/royal-kings-users.apk';

    public function diskName(): string
    {
        $configured = (string) config('filesystems.public_disk', 'public');
        if (in_array($configured, ['s3_public', 's3'], true)) {
            return $configured;
        }

        if (filled(config('filesystems.disks.s3_public.key')) && filled(config('filesystems.disks.s3_public.bucket'))) {
            return 's3_public';
        }

        return $configured !== '' ? $configured : 'public';
    }

    /**
     * Replace the stored APK. Previous object is deleted first.
     *
     * @return array{disk: string, path: string, url: string, filename: string}
     */
    public function store(UploadedFile $file): array
    {
        $disk = $this->diskName();
        $path = self::STORAGE_KEY;
        $storage = Storage::disk($disk);

        if ($storage->exists($path)) {
            $storage->delete($path);
        }

        $previousPath = trim((string) Setting::get('mobile_app_apk_path', ''));
        $previousDisk = trim((string) Setting::get('mobile_app_apk_disk', ''));
        if ($previousPath !== '' && $previousPath !== $path) {
            try {
                $oldDisk = $previousDisk !== '' ? $previousDisk : $disk;
                if (Storage::disk($oldDisk)->exists($previousPath)) {
                    Storage::disk($oldDisk)->delete($previousPath);
                }
            } catch (\Throwable) {
                // ignore missing previous object
            }
        }

        foreach ([
            public_path('downloads/royal-kings-users.apk'),
            storage_path('app/public/downloads/royal-kings-users.apk'),
            storage_path('app/downloads/royal-kings-users.apk'),
        ] as $local) {
            if (is_file($local)) {
                @unlink($local);
            }
        }

        $storage->put($path, file_get_contents($file->getRealPath()) ?: $file->getContent());

        $filename = $file->getClientOriginalName() ?: 'RoyalKingsUsers.apk';
        $url = url('/app/android.apk');

        Setting::set('mobile_app_download_url', $url);
        Setting::set('mobile_app_apk_disk', $disk);
        Setting::set('mobile_app_apk_path', $path);
        Setting::set('mobile_app_apk_filename', $filename);
        Setting::set('mobile_app_apk_uploaded_at', now()->toIso8601String());

        return compact('disk', 'path', 'url', 'filename');
    }

    public function storedPath(): ?string
    {
        $disk = trim((string) Setting::get('mobile_app_apk_disk', ''));
        $path = trim((string) Setting::get('mobile_app_apk_path', ''));
        if ($disk === '' || $path === '') {
            return null;
        }
        try {
            if (Storage::disk($disk)->exists($path)) {
                return $path;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    public function downloadResponse()
    {
        $disk = trim((string) Setting::get('mobile_app_apk_disk', ''));
        $path = $this->storedPath();
        if ($disk !== '' && $path) {
            return Storage::disk($disk)->download($path, 'RoyalKingsUsers.apk', [
                'Content-Type' => 'application/vnd.android.package-archive',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        return null;
    }

    public function publicUrl(): ?string
    {
        $fromSettings = trim((string) Setting::get('mobile_app_download_url', ''));
        if ($fromSettings !== '') {
            return $fromSettings;
        }
        $fromEnv = trim((string) config('app.mobile_app_download_url', ''));

        return $fromEnv !== '' ? $fromEnv : null;
    }
}
