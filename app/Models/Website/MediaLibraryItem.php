<?php

namespace App\Models\Website;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class MediaLibraryItem extends Model
{
    protected $table = 'media_library';

    protected $fillable = [
        'title',
        'file_path',
        'optimized_path',
        'type',
        'category',
        'alt_text',
        'focal_x',
        'focal_y',
        'scheduled_publish_at',
        'embed_url',
        'embed_provider',
        'is_featured',
        'uploaded_by',
        'album_id',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'scheduled_publish_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(MediaAlbum::class, 'album_id');
    }

    public function qualityFlag(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MediaQualityFlag::class, 'media_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(MediaTag::class, 'taggable', 'media_taggables');
    }

    public function url(): string
    {
        return asset('website/'.$this->file_path);
    }
}
