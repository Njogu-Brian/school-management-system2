<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MediaAlbum extends Model
{
    protected $fillable = ['title', 'slug', 'category', 'description', 'cover_image', 'is_featured'];

    protected $casts = ['is_featured' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (MediaAlbum $album) {
            if (empty($album->slug)) {
                $album->slug = Str::slug($album->title);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(MediaLibraryItem::class, 'album_id');
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image ? asset('website/'.$this->cover_image) : null;
    }
}
