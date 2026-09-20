<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Downscale and recompress uploaded images before they are stored.
 *
 * Branding images were being saved exactly as the user picked them off their
 * phone or laptop: the school logo was a 3000x3000 JPEG at 356 KB to be drawn in
 * a 40px navbar, and the login background a 1920x1280 JPEG at 472 KB. Both are
 * fetched on effectively every page load.
 *
 * Uses GD, which is already installed — deliberately no new Composer dependency
 * for what amounts to a resize and a re-encode. Animated GIFs are passed through
 * untouched because GD would flatten them to a single frame.
 */
class ImageOptimizer
{
    /**
     * Resize (preserving aspect ratio) and recompress an upload in place.
     *
     * Returns the number of bytes saved, or null when the file was left alone.
     */
    public function optimize(string $path, int $maxWidth, int $maxHeight, int $quality = 82): ?int
    {
        if (! extension_loaded('gd') || ! is_file($path)) {
            return null;
        }

        $before = filesize($path);
        $info = @getimagesize($path);

        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $mime = $info['mime'] ?? '';

        if ($mime === 'image/gif' && $this->isAnimatedGif($path)) {
            return null;
        }

        $source = $this->read($path, $mime);
        if ($source === null) {
            return null;
        }

        // Only ever scale down; enlarging a small logo just wastes bytes.
        $scale = min($maxWidth / $width, $maxHeight / $height, 1);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        // Preserve transparency for formats that support it.
        if (in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($source);

        // Encode to a temporary file and only adopt it if it is genuinely
        // smaller. Re-encoding can lose: a 192x192 PNG that needs no resizing
        // came back 162% larger from GD than the already-optimised original,
        // because GD writes plain truecolor and does no palette quantisation.
        $temp = $path . '.opt';
        $written = $this->write($canvas, $temp, $mime, $quality);
        imagedestroy($canvas);

        if (! $written || ! is_file($temp)) {
            @unlink($temp);

            return null;
        }

        if (filesize($temp) >= $before) {
            @unlink($temp);

            return null;
        }

        if (! @rename($temp, $path)) {
            @unlink($temp);

            return null;
        }

        clearstatcache(true, $path);
        $saved = $before - filesize($path);

        Log::info('Optimised uploaded image.', [
            'file' => basename($path),
            'from' => "{$width}x{$height}",
            'to' => "{$targetWidth}x{$targetHeight}",
            'bytes_saved' => $saved,
        ]);

        return $saved;
    }

    /**
     * Convenience wrapper for an upload that has just been moved into place.
     */
    public function optimizeUpload(UploadedFile $file, string $destination, int $maxWidth, int $maxHeight): ?int
    {
        return $this->optimize($destination, $maxWidth, $maxHeight);
    }

    private function read(string $path, string $mime): ?\GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/gif' => @imagecreatefromgif($path),
            default => false,
        };

        return $image instanceof \GdImage ? $image : null;
    }

    private function write(\GdImage $canvas, string $path, string $mime, int $quality): bool
    {
        return match ($mime) {
            'image/jpeg' => imagejpeg($canvas, $path, $quality),
            // Max compression: this runs once per upload, so the extra CPU is
            // irrelevant and PNG is where GD needs the most help.
            'image/png' => imagepng($canvas, $path, 9),
            'image/webp' => function_exists('imagewebp') && imagewebp($canvas, $path, $quality),
            'image/gif' => imagegif($canvas, $path),
            default => false,
        };
    }

    /**
     * An animated GIF has more than one Graphic Control Extension block.
     */
    private function isAnimatedGif(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return false;
        }

        $frames = 0;
        $buffer = '';

        while (! feof($handle) && $frames < 2) {
            $buffer = substr($buffer, -1) . fread($handle, 16384);
            $frames += preg_match_all('/\x00\x21\xF9\x04.{4}\x00[\x2C\x21]/s', $buffer);
        }

        fclose($handle);

        return $frames > 1;
    }
}
