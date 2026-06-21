<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceAreaPage extends Model
{
    protected $fillable = ['area_name', 'slug', 'headline', 'description', 'map_embed', 'published'];

    protected $casts = ['published' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (ServiceAreaPage $area) {
            if (empty($area->slug)) {
                $area->slug = Str::slug($area->area_name);
            }
        });
    }
}
