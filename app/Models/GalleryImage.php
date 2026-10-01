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

    /**
     * Smaller derivative for grids/thumbnails (generated on upload when possible).
     */
    public function getThumbUrlAttribute(): ?string
    {
        $thumb = $this->thumbFilename();
        if (! $thumb) {
            return null;
        }

        $path = public_images_path($thumb);
        if (! is_file($path)) {
            return null;
        }

        return public_image_url($thumb);
    }

    public function thumbFilename(): ?string
    {
        if (! $this->filename) {
            return null;
        }

        $info = pathinfo($this->filename);

        return ($info['filename'] ?? '').'_thumb.'.($info['extension'] ?? 'jpg');
    }

    public function fileExists(): bool
    {
        return $this->filename !== '' && is_file(public_images_path($this->filename));
    }
}
