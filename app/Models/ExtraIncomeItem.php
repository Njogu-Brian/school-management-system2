<?php

namespace App\Models;

use App\Models\Academics\Classroom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraIncomeItem extends Model
{
    public const KIND_TRIP = 'trip';
    public const KIND_FUN_DAY = 'fun_day';
    public const KIND_SWIMMING = 'swimming';
    public const KIND_OTHER = 'other';

    public const KINDS = [
        self::KIND_TRIP => 'Trip',
        self::KIND_FUN_DAY => 'Fun day',
        self::KIND_SWIMMING => 'Swimming',
        self::KIND_OTHER => 'Other',
    ];

    protected $fillable = [
        'name',
        'kind',
        'classroom_id',
        'votehead_id',
        'academic_year_id',
        'year',
        'term',
        'amount',
        'event_date',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'event_date' => 'date',
        'is_active' => 'boolean',
        'year' => 'integer',
        'term' => 'integer',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ActivityFeeAllocation::class);
    }

    public function isSwimming(): bool
    {
        return $this->kind === self::KIND_SWIMMING;
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? ucfirst((string) $this->kind);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
