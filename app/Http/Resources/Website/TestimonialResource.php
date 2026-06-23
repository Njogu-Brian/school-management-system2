<?php

namespace App\Http\Resources\Website;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $premium = $request->boolean('premium');
        $photo = $this->resolvePhoto($premium);
        $srcset = $this->resolveSrcset($premium);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'relationship' => $this->relationship,
            'message' => $this->message,
            'photo' => $photo,
            'photo_srcset' => $srcset,
            'video_url' => $this->video_url,
            'featured' => $this->featured,
        ];
    }

    private function resolvePhoto(bool $premium): ?string
    {
        if ($this->relationLoaded('mediaItem') && $this->mediaItem) {
            if ($premium && ! $this->mediaItem->isPremiumApproved()) {
                return null;
            }

            return $this->mediaItem->urlForSize('md');
        }

        if ($premium) {
            return null;
        }

        return $this->photo ? asset('website/'.$this->photo) : null;
    }

    private function resolveSrcset(bool $premium): ?string
    {
        if ($this->relationLoaded('mediaItem') && $this->mediaItem) {
            if ($premium && ! $this->mediaItem->isPremiumApproved()) {
                return null;
            }

            return $this->mediaItem->srcset();
        }

        return null;
    }
}
