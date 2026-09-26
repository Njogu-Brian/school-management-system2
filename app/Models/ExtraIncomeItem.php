<?php

namespace App\Models;

use App\Models\Academics\Classroom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'extra_income_item_classroom')
            ->withTimestamps()
            ->orderBy('name');
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

    /**
     * Classroom IDs this activity applies to (pivot + legacy classroom_id).
     *
     * @return list<int>
     */
    public function classroomIds(): array
    {
        $ids = $this->relationLoaded('classrooms')
            ? $this->classrooms->pluck('id')->all()
            : $this->classrooms()->pluck('classrooms.id')->all();

        if ($ids === [] && $this->classroom_id) {
            $ids = [(int) $this->classroom_id];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function hasClassRestriction(): bool
    {
        return $this->classroomIds() !== [];
    }

    public function allowsClassroom(?int $classroomId): bool
    {
        $ids = $this->classroomIds();
        if ($ids === []) {
            return true;
        }

        return $classroomId !== null && in_array((int) $classroomId, $ids, true);
    }

    public function classroomNames(): string
    {
        if ($this->relationLoaded('classrooms') && $this->classrooms->isNotEmpty()) {
            return $this->classrooms->pluck('name')->join(', ');
        }

        $names = $this->classrooms()->pluck('name');
        if ($names->isNotEmpty()) {
            return $names->join(', ');
        }

        return $this->classroom?->name ?? 'Any class';
    }

    /**
     * Sync selected classrooms and keep legacy classroom_id as the first one.
     *
     * @param  array<int, int|string>|Collection  $classroomIds
     */
    public function syncClassrooms(array|Collection $classroomIds): void
    {
        $ids = collect($classroomIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->classrooms()->sync($ids);
        $this->update(['classroom_id' => $ids[0] ?? null]);
        $this->unsetRelation('classrooms');
        $this->unsetRelation('classroom');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
