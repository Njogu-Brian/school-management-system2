<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Page extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'status',
        'is_homepage',
        'published_at',
    ];

    protected $casts = [
        'is_homepage' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if (empty($page->slug) && ! empty($page->name)) {
                $page->slug = Str::slug($page->name);
            }

            if ($page->is_homepage) {
                static::query()
                    ->where('id', '!=', $page->id ?? 0)
                    ->update(['is_homepage' => false]);
            }

            if ($page->status === self::STATUS_PUBLISHED && ! $page->published_at) {
                $page->published_at = now();
            }
        });
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function activeSections(): HasMany
    {
        return $this->sections()->where('is_active', true);
    }

    public static function homepage(): ?self
    {
        return static::query()
            ->where('is_homepage', true)
            ->where('status', self::STATUS_PUBLISHED)
            ->first();
    }
}
