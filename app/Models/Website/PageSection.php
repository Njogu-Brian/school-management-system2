<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    protected $fillable = [
        'page_id',
        'section_type',
        'section_key',
        'title',
        'subtitle',
        'content',
        'settings',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        $bust = function (PageSection $section): void {
            app(\App\Services\Website\WebsitePageCacheService::class)->bustForSection($section);
        };

        static::saved($bust);
        static::deleted($bust);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
