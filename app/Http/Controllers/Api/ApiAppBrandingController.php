<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Public branding for mobile / clients (no auth). Mirrors portal Settings → General & Branding.
 */
class ApiAppBrandingController extends Controller
{
    public function show(): \Illuminate\Http\JsonResponse
    {
        $schoolName = trim((string) (Setting::get('school_name') ?: config('app.name', 'School'))) ?: 'School';

        $logoFilename = Setting::get('school_logo');
        $logoUrl = $this->resolvePublicImageUrl($logoFilename);

        $loginBgFilename = Setting::get('login_background');
        $loginBackgroundUrl = $this->resolvePublicImageUrl($loginBgFilename);

        $colors = $this->portalColors();

        return response()->json([
            'school_name' => $schoolName,
            'logo_url' => $logoUrl,
            'login_background_url' => $loginBackgroundUrl,
            'colors' => $colors,
        ]);
    }

    /**
     * Finance / portal brand colors (Settings → Branding). Keys match mobile `COLORS` where possible.
     */
    private function portalColors(): array
    {
        $defaults = [
            'primary' => '#004A99',
            'primary_dark' => '#003d7a',
            'primary_light' => '#1a6bc4',
            'secondary' => '#14b8a6',
            'success' => '#059669',
            'warning' => '#d97706',
            'error' => '#dc2626',
            'info' => '#2563eb',
            'surface_light' => '#ffffff',
            'border_light' => '#E5E7EB',
            'text_main_light' => '#0f172a',
            'text_sub_light' => '#64748b',
            'accent_light' => '#e6f0fa',
        ];

        $keys = [
            'primary' => 'finance_primary_color',
            'secondary' => 'finance_secondary_color',
            'success' => 'finance_success_color',
            'warning' => 'finance_warning_color',
            'error' => 'finance_danger_color',
            'info' => 'finance_info_color',
            'surface_light' => 'finance_surface_color',
            'border_light' => 'finance_border_color',
            'text_main_light' => 'finance_text_color',
            'text_sub_light' => 'finance_muted_color',
        ];

        $out = [];
        foreach ($keys as $jsonKey => $settingKey) {
            $v = trim((string) (Setting::get($settingKey) ?? ''));
            if ($v !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $v)) {
                $out[$jsonKey] = $v;
            } else {
                $out[$jsonKey] = $defaults[$jsonKey];
            }
        }

        // Derive primary_light / primary_dark from primary if not stored separately
        $primary = $out['primary'];
        $out['primary_dark'] = $this->shadeHex($primary, -0.12);
        $out['primary_light'] = $this->shadeHex($primary, 0.18);
        $out['accent_light'] = $this->mixHex($primary, '#ffffff', 0.88);

        return $out;
    }

    private function shadeHex(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#'.$hex;
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $f = fn ($c) => (int) max(0, min(255, $amount >= 0 ? $c + (255 - $c) * $amount : $c * (1 + $amount)));

        return sprintf('#%02x%02x%02x', $f($r), $f($g), $f($b));
    }

    private function mixHex(string $a, string $b, float $t): string
    {
        $pa = $this->hexToRgb($a);
        $pb = $this->hexToRgb($b);
        if (! $pa || ! $pb) {
            return $a;
        }
        $mix = fn ($x, $y) => (int) round($x * (1 - $t) + $y * $t);

        return sprintf('#%02x%02x%02x', $mix($pa['r'], $pb['r']), $mix($pa['g'], $pb['g']), $mix($pa['b'], $pb['b']));
    }

    /** @return array{r: int, g: int, b: int}|null */
    private function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return null;
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    private function resolvePublicImageUrl(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $filename = ltrim($filename, '/');

        if (file_exists(public_images_path($filename))) {
            return public_image_url($filename);
        }

        $disk = config('filesystems.public_disk', 'public');
        if (Storage::disk($disk)->exists($filename)) {
            return url(Storage::disk($disk)->url($filename));
        }

        return null;
    }
}
