<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SchoolRegistry extends Model
{
    protected $table = 'schools_registry';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_PROVISIONING = 'provisioning';

    protected $fillable = [
        'code',
        'name',
        'slug',
        'api_base_url',
        'status',
        'logo_url',
        'primary_color',
        'contact_email',
        'contact_phone',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Normalize user-entered school codes (trim + uppercase). */
    public static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    /** Public payload for mobile resolve. */
    public function toResolvePayload(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'slug' => $this->slug,
            'api_base_url' => rtrim((string) $this->api_base_url, '/'),
            'status' => $this->status,
            'branding' => [
                'logo_url' => $this->logo_url,
                'primary_color' => $this->primary_color,
            ],
        ];
    }
}
