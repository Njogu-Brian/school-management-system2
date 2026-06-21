<?php

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaLibraryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url(),
            'type' => $this->type,
            'category' => $this->category,
            'alt_text' => $this->alt_text,
            'is_featured' => $this->is_featured,
        ];
    }
}
