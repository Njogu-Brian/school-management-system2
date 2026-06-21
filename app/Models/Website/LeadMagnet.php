<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LeadMagnet extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'file_path', 'cover_image', 'published', 'download_count',
    ];

    protected $casts = ['published' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (LeadMagnet $m) {
            if (empty($m->slug)) {
                $m->slug = Str::slug($m->title);
            }
        });
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(LeadMagnetDownload::class);
    }
}
