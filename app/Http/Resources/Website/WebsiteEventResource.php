<?php

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebsiteEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'cover_image' => $this->coverImageUrl(),
            'location' => $this->location,
            'registration_enabled' => $this->registration_enabled,
            'source' => $this->source ?? 'cms',
            'seo' => [
                'title' => $this->title,
                'description' => str($this->description)->limit(160)->value(),
            ],
        ];
    }
}
