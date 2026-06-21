<?php

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->when($request->route('slug'), $this->body),
            'featured_image' => $this->featuredImageUrl(),
            'author' => $this->author?->name,
            'published_at' => $this->published_at?->toIso8601String(),
            'seo' => [
                'title' => $this->title,
                'description' => $this->excerpt,
            ],
        ];
    }
}
