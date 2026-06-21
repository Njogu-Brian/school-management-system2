<?php

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebsiteSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'school_name' => $this->school_name,
            'tagline' => $this->tagline,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'google_map' => $this->google_map,
            'whatsapp' => $this->whatsapp,
            'social' => [
                'facebook' => $this->facebook,
                'instagram' => $this->instagram,
                'youtube' => $this->youtube,
                'tiktok' => $this->tiktok,
            ],
            'hero_video' => $this->hero_video,
            'logo' => $this->logo ? asset('website/'.$this->logo) : null,
            'favicon' => $this->favicon ? asset('website/'.$this->favicon) : null,
            'admissions_open' => $this->admissions_open,
            'current_term' => $this->current_term,
            'seo' => $this->seo_defaults ?? [],
        ];
    }
}
