<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimePeriod extends Model
{
    protected $fillable = [
        'name',
        'level',
        'period_number',
        'start_time',
        'end_time',
        'duration_minutes',
        'is_break',
        'break_type',
        'order',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'duration_minutes' => 'integer',
        'is_break' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
        'period_number' => 'integer',
    ];

    /**
     * Get periods for a specific level/name
     */
    public static function getForLevel(string $name): \Illuminate\Support\Collection
    {
        return static::where('name', $name)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('period_number')
            ->get();
    }

    /**
     * Get all active periods
     */
    public static function getAllActive(): \Illuminate\Support\Collection
    {
        return static::where('is_active', true)
            ->orderBy('name')
            ->orderBy('order')
            ->orderBy('period_number')
            ->get();
    }
}
