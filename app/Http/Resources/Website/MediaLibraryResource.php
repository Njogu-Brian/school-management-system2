<?php

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaLibraryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $flag = $this->whenLoaded('qualityFlag', fn () => $this->qualityFlag);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url(),
            'optimized_url' => $this->optimizedUrl(),
            'url_lg' => $this->urlForSize('lg'),
            'url_md' => $this->urlForSize('md'),
            'url_sm' => $this->urlForSize('sm'),
            'srcset' => $this->srcset(),
            'variants' => $this->variantMap(),
            'width' => $this->width,
            'height' => $this->height,
            'type' => $this->type,
            'category' => $this->category,
            'alt_text' => $this->alt_text,
            'is_featured' => $this->is_featured,
            'optimization_status' => $this->optimization_status,
            'quality' => $flag ? [
                'approved' => (bool) $flag->approved,
                'hero_ready' => (bool) $flag->hero_ready,
                'homepage_ready' => (bool) $flag->homepage_ready,
                'priority' => (int) $flag->priority,
            ] : null,
        ];
    }
}
