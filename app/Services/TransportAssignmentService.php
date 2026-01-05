<?php

namespace App\Services;

use App\Models\{
    Student, Trip, Vehicle, TransportSpecialAssignment, StudentAssignment,
    DropOffPoint, SchoolDay
};
use Carbon\Carbon;

/**
 * Transport Assignment Service
 * Handles resolution of student transport assignments with priority:
 * 1. Special assignment
 * 2. Term assignment
 * 3. Default assignment
 */
class TransportAssignmentService
{
    /**
     * Get active transport assignment for a student on a specific date
     * Returns: array with trip, drop_off_point, vehicle, etc.
     */
    public function getStudentAssignment(Student $student, Carbon $date, string $direction = 'pickup'): ?array
    {
        // Priority 1: Check for special assignment
        $specialAssignment = TransportSpecialAssignment::where('student_id', $student->id)
            ->where('start_date', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date->toDateString());
            })
            ->where('status', 'active')
            ->first();

        if ($specialAssignment && $specialAssignment->isActive()) {
            // Handle special assignment modes
            if ($specialAssignment->transport_mode === 'own_means') {
                return [
                    'type' => 'own_means',
                    'reason' => $specialAssignment->reason,
                    'special_assignment_id' => $specialAssignment->id,
                ];
            }

            if ($specialAssignment->transport_mode === 'vehicle' && $specialAssignment->vehicle_id) {
                return [
                    'type' => 'vehicle',
                    'vehicle_id' => $specialAssignment->vehicle_id,
                    'vehicle' => $specialAssignment->vehicle,
                    'special_assignment_id' => $specialAssignment->id,
                ];
            }

            if ($specialAssignment->transport_mode === 'trip' && $specialAssignment->trip_id) {
                $trip = Trip::with(['vehicle', 'driver', 'stops.dropOffPoint'])->find($specialAssignment->trip_id);
                return [
                    'type' => 'trip',
                    'trip_id' => $specialAssignment->trip_id,
                    'trip' => $trip,
                    'drop_off_point_id' => $specialAssignment->drop_off_point_id,
                    'drop_off_point' => $specialAssignment->dropOffPoint,
                    'special_assignment_id' => $specialAssignment->id,
                ];
            }
        }

        // Priority 2: Check term-based assignment (StudentAssignment)
        $assignment = StudentAssignment::where('student_id', $student->id)
            ->where(function ($q) use ($direction) {
                if ($direction === 'pickup') {
                    $q->whereNotNull('morning_trip_id');
                } else {
                    $q->whereNotNull('evening_trip_id');
                }
            })
            ->first();

        if ($assignment) {
            $tripId = $direction === 'pickup' ? $assignment->morning_trip_id : $assignment->evening_trip_id;
            $dropOffPointId = $direction === 'pickup' 
                ? $assignment->morning_drop_off_point_id 
                : $assignment->evening_drop_off_point_id;

            $trip = Trip::with(['vehicle', 'driver', 'stops.dropOffPoint'])->find($tripId);
            $dropOffPoint = DropOffPoint::find($dropOffPointId);

            return [
                'type' => 'trip',
                'trip_id' => $tripId,
                'trip' => $trip,
                'drop_off_point_id' => $dropOffPointId,
                'drop_off_point' => $dropOffPoint,
                'assignment_id' => $assignment->id,
            ];
        }

        // Priority 3: Default assignment (from student table - legacy)
        // This can be enhanced based on your default assignment logic
        if ($student->trip_id) {
            // Legacy support - return default assignment
            return [
                'type' => 'default',
                'trip_id' => $student->trip_id,
                'drop_off_point_id' => $student->drop_off_point_id,
            ];
        }

        return null;
    }

    /**
     * Check if a date is a school day (for trip scheduling)
     */
    public function isSchoolDay(Carbon $date): bool
    {
        return SchoolDay::isSchoolDay($date->toDateString());
    }

    /**
     * Get trips for a specific date and direction
     */
    public function getTripsForDate(Carbon $date, string $direction = 'pickup'): \Illuminate\Database\Eloquent\Collection
    {
        $dayOfWeek = $date->dayOfWeek; // 0=Sunday, 6=Saturday

        // Convert to 1=Monday, 7=Sunday format
        $dayOfWeekAdjusted = $dayOfWeek === 0 ? 7 : $dayOfWeek;

        // Only get trips for school days
        if (!$this->isSchoolDay($date)) {
            return collect([]);
        }

        return Trip::where('direction', $direction)
            ->where(function ($q) use ($dayOfWeekAdjusted) {
                $q->whereNull('day_of_week') // All days
                  ->orWhere('day_of_week', $dayOfWeekAdjusted);
            })
            ->with(['vehicle', 'driver', 'stops.dropOffPoint'])
            ->get();
    }

    /**
     * Get students assigned to a trip
     */
    public function getStudentsForTrip(Trip $trip, Carbon $date): \Illuminate\Database\Eloquent\Collection
    {
        // Get students from StudentAssignment (morning or evening trip)
        $studentIds = StudentAssignment::where(function ($q) use ($trip) {
            $q->where('morning_trip_id', $trip->id)
              ->orWhere('evening_trip_id', $trip->id);
        })
        ->pluck('student_id');

        // Also check for special assignments
        $specialStudentIds = TransportSpecialAssignment::where('trip_id', $trip->id)
            ->where('start_date', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date->toDateString());
            })
            ->where('status', 'active')
            ->pluck('student_id');

        $allStudentIds = $studentIds->merge($specialStudentIds)->unique();

        if ($allStudentIds->isEmpty()) {
            return collect([]);
        }

        return Student::whereIn('id', $allStudentIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->with(['classroom', 'category'])
            ->orderBy('first_name')
            ->get();
    }
}
