<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransportStop extends Model
{
    public const KIND_MORNING_PICKUP = 'morning_pickup';

    public const KIND_EVENING_DROPOFF = 'evening_dropoff';

    public const KINDS = [
        self::KIND_MORNING_PICKUP,
        self::KIND_EVENING_DROPOFF,
    ];

    protected $fillable = [
        'student_id',
        'kind',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Map a trip direction/type to the stop kind shown on the map.
     */
    public static function kindForTripDirection(?string $direction): string
    {
        $dir = strtolower(trim((string) $direction));

        if (in_array($dir, ['dropoff', 'drop_off', 'evening', 'home'], true)) {
            return self::KIND_EVENING_DROPOFF;
        }

        return self::KIND_MORNING_PICKUP;
    }

    /**
     * Prefer trip direction, then type (Morning/Evening).
     */
    public static function kindForTrip(?\App\Models\Trip $trip): string
    {
        if (! $trip) {
            return self::KIND_MORNING_PICKUP;
        }

        if ($trip->direction) {
            return self::kindForTripDirection($trip->direction);
        }

        return self::kindForTripDirection($trip->type);
    }
}
