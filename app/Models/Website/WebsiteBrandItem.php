<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class WebsiteBrandItem extends Model
{
    public const TYPE_TRUST_PILL = 'trust_pill';

    public const TYPE_SCHOOL_CARD = 'school_card';

    public const TYPE_JOURNEY_MILESTONE = 'journey_milestone';

    public const TYPE_COCURRICULAR = 'cocurricular';

    public const TYPE_FAITH_PILLAR = 'faith_pillar';

    public const TYPE_LEADER = 'leader';

    public const TYPE_SCRIPTURE = 'scripture';

    public const TYPE_CHAPLAIN = 'chaplain';

    public const TYPE_PRAYER_HIGHLIGHT = 'prayer_highlight';

    protected $fillable = [
        'block_type',
        'title',
        'subtitle',
        'body',
        'image_url',
        'link_url',
        'video_url',
        'settings',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('block_type', $type);
    }
}
