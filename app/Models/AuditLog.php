<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_type',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action
     */
    public function user()
    {
        return $this->morphTo('user');
    }

    /**
     * Get the auditable model
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeForEvent(Builder $query, $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForUser(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAuditable(Builder $query, $type, $id)
    {
        return $query->where('auditable_type', $type)
            ->where('auditable_id', $id);
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Log an audit event
     */
    public static function log($event, $auditable, $oldValues = null, $newValues = null, $tags = [])
    {
        $user = auth()->user();

        return self::create([
            'user_type' => $user ? get_class($user) : null,
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tags' => $tags,
        ]);
    }
}

