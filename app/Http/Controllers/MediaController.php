<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Short, app-domain URL that redirects to the real storage URL.
     * Uses Laravel signed URLs to prevent arbitrary path access.
     */
    public function signedRedirect(Request $request, string $disk, string $encodedPath)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $path = $this->decodePath($encodedPath);
        if ($path === '') {
            abort(404);
        }

        // Only allow known disks (prevents probing arbitrary configured disks).
        $allowed = [
            config('filesystems.public_disk', 'public'),
            config('filesystems.private_disk', 'private'),
            'public',
            'private',
            's3_public',
            's3_private',
        ];
        $allowed = array_values(array_unique($allowed));
        if (! in_array($disk, $allowed, true)) {
            abort(404);
        }

        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            abort(404);
        }

        // Cache the redirect itself. Without this every avatar render costs a
        // PHP request to re-issue the same 302 — scrolling a 40-student list was
        // 40 framework boots. The signed URL is stable within its window (see
        // media_signed_url), so caching the hop is safe for that long.
        $minutes = max(1, min((int) $request->query('m', 10), MEDIA_SIGNED_URL_MAX_MINUTES));
        $cacheHeaders = [
            'Cache-Control' => 'public, max-age=' . ($minutes * 60),
        ];

        // If disk supports temporaryUrl (S3), redirect to it (keeps the browser URL short).
        if (method_exists($storage, 'temporaryUrl')) {
            return redirect()
                ->away($storage->temporaryUrl($path, now()->addMinutes($minutes)))
                ->withHeaders($cacheHeaders);
        }

        // Local: redirect to the normal URL.
        $u = $storage->url($path);
        return redirect()
            ->to(str_starts_with($u, 'http') ? $u : url($u))
            ->withHeaders($cacheHeaders);
    }

    protected function decodePath(string $encoded): string
    {
        $encoded = trim($encoded);
        if ($encoded === '') {
            return '';
        }

        $b64 = strtr($encoded, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($b64, true);
        if ($decoded === false) {
            return '';
        }

        // Normalize and disallow traversal.
        $decoded = str_replace('\\', '/', $decoded);
        $decoded = ltrim($decoded, '/');
        if ($decoded === '' || str_contains($decoded, '..')) {
            return '';
        }

        return $decoded;
    }
}

