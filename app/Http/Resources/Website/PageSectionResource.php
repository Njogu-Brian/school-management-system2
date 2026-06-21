<?php

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->section_type,
            'key' => $this->section_key,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'content' => $this->content,
            'settings' => $this->settings ?? [],
            'sort_order' => $this->sort_order,
        ];
    }
}
