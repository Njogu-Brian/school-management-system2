@php
    $faviconSetting = \App\Models\Setting::where('key', 'favicon')->first();
    $logoSetting = \App\Models\Setting::where('key', 'school_logo')->first();
    $faviconSettingValue = $faviconSetting?->value ?? $logoSetting?->value;
    $faviconUrl = null;
    if ($faviconSettingValue && function_exists('public_images_path') && file_exists(public_images_path($faviconSettingValue))) {
        $faviconUrl = public_image_url($faviconSettingValue);
    } elseif ($faviconSettingValue && \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconSettingValue)) {
        $faviconUrl = \Illuminate\Support\Facades\Storage::url($faviconSettingValue);
    } elseif ($logoSetting?->value && function_exists('public_images_path') && file_exists(public_images_path($logoSetting->value))) {
        $faviconUrl = public_image_url($logoSetting->value);
    } elseif ($logoSetting?->value && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoSetting->value)) {
        $faviconUrl = \Illuminate\Support\Facades\Storage::url($logoSetting->value);
    }
    if (!$faviconUrl) {
        $faviconUrl = file_exists(public_path('favicon.ico')) ? asset('favicon.ico') : asset('images/logo.png');
    }
@endphp
<link rel="icon" href="{{ $faviconUrl }}" type="image/x-icon">
