<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class VirtualTourStop extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'image', 'panorama_url', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function imageUrl(): ?string
    {
        return $this->image ? asset('website/'.$this->image) : null;
    }
}
