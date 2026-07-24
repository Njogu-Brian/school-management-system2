<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_name', // actual column
        'type',
        'vehicle_id',
        'driver_id',
        'day_of_week',
        'direction',
    ];

    protected $casts = [
        'day_of_week' => 'array',
    ];

    /**
     * Backwards-compatibility: allow $trip->name and mass-assigning `name`
     * even though the database column is `trip_name`.
     */
    public function getNameAttribute(): ?string
    {
        return $this->trip_name;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['trip_name'] = $value;
    }

    // Relationship with Vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Students assigned via legacy trip_id column.
     */
    public function assignments()
    {
        return $this->hasMany(StudentAssignment::class, 'trip_id');
    }

    public function morningAssignments()
    {
        return $this->hasMany(StudentAssignment::class, 'morning_trip_id');
    }

    public function eveningAssignments()
    {
        return $this->hasMany(StudentAssignment::class, 'evening_trip_id');
    }

    /**
     * Whether any student assignment references this trip (legacy, morning, or evening).
     */
    public function hasStudentAssignments(): bool
    {
        return $this->assignments()->exists()
            || $this->morningAssignments()->exists()
            || $this->eveningAssignments()->exists();
    }

    /**
     * Clear all student assignment references to this trip.
     */
    public function detachStudentAssignments(): int
    {
        $count = 0;

        $count += StudentAssignment::where('morning_trip_id', $this->id)->update(['morning_trip_id' => null]);
        $count += StudentAssignment::where('evening_trip_id', $this->id)->update(['evening_trip_id' => null]);

        if (\Illuminate\Support\Facades\Schema::hasColumn('student_assignments', 'trip_id')) {
            $count += StudentAssignment::where('trip_id', $this->id)->update(['trip_id' => null]);
        }

        return $count;
    }

    /**
     * Which assignment leg this trip maps to based on direction or type.
     * pickup/Morning → morning, dropoff/Evening → evening.
     */
    public function assignmentLeg(): ?string
    {
        if ($this->direction === 'pickup' || $this->type === 'Morning') {
            return 'morning';
        }
        if ($this->direction === 'dropoff' || $this->type === 'Evening') {
            return 'evening';
        }

        return null;
    }

    // Relationship with Driver (Staff)
    public function driver()
    {
        return $this->belongsTo(Staff::class, 'driver_id');
    }

    // Relationship with Trip Stops
    public function stops()
    {
        return $this->hasMany(TripStop::class)->orderBy('sequence_order');
    }

    // Relationship with Trip Attendances
    public function attendances()
    {
        return $this->hasMany(TripAttendance::class);
    }

    public function runs()
    {
        return $this->hasMany(TripRun::class);
    }
}
