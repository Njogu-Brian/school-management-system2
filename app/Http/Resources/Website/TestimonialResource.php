<?php

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'relationship' => $this->relationship,
            'message' => $this->message,
            'photo' => $this->photoUrl(),
            'video_url' => $this->video_url,
            'featured' => $this->featured,
        ];
    }
}
