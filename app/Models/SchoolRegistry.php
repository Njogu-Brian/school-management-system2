<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class SchoolRegistry extends Model
{
    protected $connection = 'control';

    protected $table = 'schools_registry';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_PROVISIONING = 'provisioning';

    public const BILLING_TRIAL = 'trial';

    public const BILLING_CURRENT = 'current';

    public const BILLING_OVERDUE = 'overdue';

    public const BILLING_SUSPENDED = 'suspended';

    protected $fillable = [
        'code',
        'name',
        'slug',
        'api_base_url',
        'status',
        'logo_url',
        'primary_color',
        'secondary_color',
        'contact_email',
        'contact_phone',
        'db_name',
        'db_username',
        'db_password_encrypted',
        'db_host',
        'db_port',
        'billing_status',
        'monthly_fee',
        'next_due_date',
        'student_count_cached',
        'provisioned_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'monthly_fee' => 'decimal:2',
        'next_due_date' => 'date',
        'provisioned_at' => 'datetime',
        'student_count_cached' => 'integer',
        'db_port' => 'integer',
    ];

    protected $hidden = [
        'db_password_encrypted',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SchoolSubscription::class, 'school_registry_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SchoolPayment::class, 'school_registry_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function setDbPasswordAttribute(?string $plain): void
    {
        $this->attributes['db_password_encrypted'] = $plain === null || $plain === ''
            ? null
            : Crypt::encryptString($plain);
    }

    public function getDbPasswordAttribute(): ?string
    {
        $enc = $this->attributes['db_password_encrypted'] ?? null;
        if (! $enc) {
            return null;
        }

        try {
            return Crypt::decryptString($enc);
        } catch (\Throwable) {
            return null;
        }
    }

    public function tenantWebUrl(): string
    {
        $base = rtrim((string) config('edulynk.tenant_base_url'), '/');

        return $base.'/'.$this->slug;
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

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
                'secondary_color' => $this->secondary_color,
            ],
        ];
    }
}
