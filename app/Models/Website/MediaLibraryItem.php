<?php

namespace App\Models\Website;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class MediaLibraryItem extends Model
{
    public const OPT_PENDING = 'pending';

    public const OPT_COMPLETED = 'completed';

    public const OPT_FAILED = 'failed';

    public const OPT_SKIPPED = 'skipped';

    protected $table = 'media_library';

    protected $fillable = [
        'title',
        'file_path',
        'optimized_path',
        'variants',
        'optimization_status',
        'width',
        'height',
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
        'variants' => 'array',
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

    public function optimizedUrl(): ?string
    {
        if (! $this->optimized_path) {
            return null;
        }

        return asset('website/'.$this->optimized_path);
    }

    /**
     * @return array<string, array{webp: string, w: int, h: int}>
     */
    public function variantMap(): array
    {
        return is_array($this->variants) ? $this->variants : [];
    }

    public function urlForSize(string $size = 'lg'): string
    {
        $variants = $this->variantMap();
        $order = ['xl', 'lg', 'md', 'sm'];

        if (isset($variants[$size]['webp'])) {
            return asset('website/'.$variants[$size]['webp']);
        }

        foreach ($order as $key) {
            if (isset($variants[$key]['webp'])) {
                return asset('website/'.$variants[$key]['webp']);
            }
        }

        return $this->optimizedUrl() ?? $this->url();
    }

    public function srcset(): string
    {
        $parts = [];

        foreach ($this->variantMap() as $variant) {
            if (! empty($variant['webp']) && ! empty($variant['w'])) {
                $parts[] = asset('website/'.$variant['webp']).' '.$variant['w'].'w';
            }
        }

        if ($parts === []) {
            return $this->url().' '.$this->width.'w';
        }

        return implode(', ', $parts);
    }

    public function isPremiumApproved(): bool
    {
        $flag = $this->qualityFlag;

        return $flag && $flag->approved && $flag->homepage_ready;
    }

    public function isHeroApproved(): bool
    {
        $flag = $this->qualityFlag;

        return $flag && $flag->approved && $flag->hero_ready;
    }

    public function scopePremiumApproved($query)
    {
        return $query->whereHas('qualityFlag', fn ($f) => $f->where('approved', true)->where('homepage_ready', true));
    }

    public function scopeHeroApproved($query)
    {
        return $query->whereHas('qualityFlag', fn ($f) => $f->where('approved', true)->where('hero_ready', true));
    }

    public function scopeOrderByMediaPriority($query)
    {
        return $query
            ->join('media_quality_flags', 'media_library.id', '=', 'media_quality_flags.media_id')
            ->orderByDesc('media_quality_flags.priority')
            ->select('media_library.*');
    }
}
