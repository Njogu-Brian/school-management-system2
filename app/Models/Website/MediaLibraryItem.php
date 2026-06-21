<?php

namespace App\Models\Website;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaLibraryItem extends Model
{
    protected $table = 'media_library';

    protected $fillable = [
        'title',
        'file_path',
        'type',
        'category',
        'alt_text',
        'is_featured',
        'uploaded_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(MediaAlbum::class, 'album_id');
    }

    public function url(): string
    {
        return asset('website/'.$this->file_path);
    }
}
