<?php

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'title' => $this->title,
            'is_homepage' => $this->is_homepage,
            'published_at' => $this->published_at?->toIso8601String(),
            'seo' => [
                'title' => $this->meta_title ?: $this->title,
                'description' => $this->meta_description,
            ],
            'sections' => PageSectionResource::collection($this->whenLoaded('activeSections')),
        ];
    }
}
