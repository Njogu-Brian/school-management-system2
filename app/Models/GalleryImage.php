<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $fillable = ['filename', 'sort_order', 'caption'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function getUrlAttribute(): string
    {
        return public_image_url($this->filename) ?? '';
    }
}
