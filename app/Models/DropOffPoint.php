<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DropOffPoint extends Model
{
    use HasFactory, SoftDeletes;

    public const OWN_MEANS_NAME = 'OWN MEANS';

    protected $fillable = [
        'name',
        'route_id',
        'two_way_amount',
        'one_way_amount',
    ];

    protected $casts = [
        'two_way_amount' => 'decimal:2',
        'one_way_amount' => 'decimal:2',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function assignments()
    {
        return $this->hasMany(StudentAssignment::class, 'drop_off_point_id');
    }

    public function morningAssignments()
    {
        return $this->hasMany(StudentAssignment::class, 'morning_drop_off_point_id');
    }

    public function eveningAssignments()
    {
        return $this->hasMany(StudentAssignment::class, 'evening_drop_off_point_id');
    }

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'drop_off_point_vehicle')
            ->withTimestamps();
    }

    public function isOwnMeans(): bool
    {
        return self::nameIsOwnMeans($this->name);
    }

    public static function nameIsOwnMeans(?string $name): bool
    {
        if (!$name) {
            return false;
        }

        $normalized = Str::upper(trim($name));
        $variants = ['OWN', 'OWNMEANS', 'OWN MEANS', 'OWN MEAN', 'OWN TRANSPORT'];

        if (in_array($normalized, $variants, true)) {
            return true;
        }

        return Str::startsWith($normalized, 'OWN') && Str::contains($normalized, 'MEAN');
    }

    /**
     * Resolve or create the system OWN MEANS drop-off point (rates = 0).
     */
    public static function ownMeans(): self
    {
        $point = static::withTrashed()
            ->whereRaw('UPPER(name) = ?', [self::OWN_MEANS_NAME])
            ->first();

        if ($point) {
            if ($point->trashed()) {
                $point->restore();
            }
            $point->fill([
                'two_way_amount' => 0,
                'one_way_amount' => 0,
            ]);
            if ($point->isDirty()) {
                $point->save();
            }

            return $point;
        }

        return static::create([
            'name' => self::OWN_MEANS_NAME,
            'two_way_amount' => 0,
            'one_way_amount' => 0,
        ]);
    }
}
