<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebsiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_name' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:500',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:2000',
            'google_map' => 'nullable|string|max:5000',
            'whatsapp' => 'nullable|string|max:50',
            'facebook' => 'nullable|url|max:500',
            'instagram' => 'nullable|url|max:500',
            'youtube' => 'nullable|url|max:500',
            'tiktok' => 'nullable|url|max:500',
            'hero_video' => 'nullable|string|max:500',
            'admissions_open' => 'nullable|boolean',
            'current_term' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
            'favicon' => 'nullable|image|mimes:jpg,jpeg,png,webp,ico|max:2048',
            'seo_defaults' => 'nullable|array',
            'seo_defaults.title' => 'nullable|string|max:255',
            'seo_defaults.description' => 'nullable|string|max:2000',
            'seo_defaults.keywords' => 'nullable|string|max:500',
            'seo_defaults.og_image' => 'nullable|string|max:500',
        ];
    }
}
