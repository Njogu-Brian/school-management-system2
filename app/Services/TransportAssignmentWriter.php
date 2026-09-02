<?php

namespace App\Services;

use App\Models\DropOffPoint;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\Trip;
use Illuminate\Support\Collection;

class TransportAssignmentWriter
{
    /**
     * Save morning/evening trips + pickup/drop-off, then bill (explicit amount including 0, or calculate).
     *
     * @param  array{
     *     morning_trip_id?: mixed,
     *     evening_trip_id?: mixed,
     *     morning_drop_off_point_id?: mixed,
     *     evening_drop_off_point_id?: mixed,
     *     amount?: mixed,
     *     source?: string,
     *     note?: string,
     *     skip_invoice?: bool,
     *     preserve_fee?: bool
     * }  $payload
     */
    public static function save(Student $student, array $payload): StudentAssignment
    {
        $ownMeansId = (int) DropOffPoint::ownMeans()->id;

        $morningPointId = self::nullableId($payload['morning_drop_off_point_id'] ?? null);
        $eveningPointId = self::nullableId($payload['evening_drop_off_point_id'] ?? null);
        $morningTripId = self::nullableId($payload['morning_trip_id'] ?? null);
        $eveningTripId = self::nullableId($payload['evening_trip_id'] ?? null);

        if ($morningPointId && (int) $morningPointId === $ownMeansId) {
            $morningTripId = null;
        }
        if ($eveningPointId && (int) $eveningPointId === $ownMeansId) {
            $eveningTripId = null;
        }

        $yearIn = $payload['year'] ?? null;
        $termIn = $payload['term'] ?? null;
        $yearIn = ($yearIn === '' || $yearIn === null) ? null : (int) $yearIn;
        $termIn = ($termIn === '' || $termIn === null) ? null : (int) $termIn;
        if (! $yearIn) {
            $yearIn = null;
        }
        if (! $termIn) {
            $termIn = null;
        }

        [$year, $term, $academicYearId] = TransportFeeService::resolveYearAndTerm($yearIn, $termIn);

        $assignment = StudentAssignment::firstOrNew([
            'student_id' => $student->id,
            'year' => $year,
            'term' => $term,
        ]);
        $assignment->academic_year_id = $academicYearId;
        $assignment->morning_drop_off_point_id = $morningPointId;
        $assignment->evening_drop_off_point_id = $eveningPointId;
        $assignment->morning_trip_id = $morningTripId;
        $assignment->evening_trip_id = $eveningTripId;
        $assignment->save();

        $seedPointId = $eveningPointId && (int) $eveningPointId !== $ownMeansId
            ? $eveningPointId
            : ($morningPointId && (int) $morningPointId !== $ownMeansId ? $morningPointId : null);

        $seedPoint = $seedPointId ? DropOffPoint::find($seedPointId) : null;

        [$currentYear, $currentTerm] = TransportFeeService::resolveYearAndTerm();
        if ((int) $year === (int) $currentYear && (int) $term === (int) $currentTerm) {
            $student->drop_off_point_id = $seedPointId;
            $student->drop_off_point = $seedPoint?->name
                ?? (($morningPointId === $ownMeansId && $eveningPointId === $ownMeansId)
                    ? DropOffPoint::OWN_MEANS_NAME
                    : $student->drop_off_point);
            $student->trip_id = $morningTripId ?: $eveningTripId;
            $student->save();
        }

        $amount = self::parseAmount($payload['amount'] ?? null);
        $source = $payload['source'] ?? 'manual';
        $note = $payload['note'] ?? 'Saved from transport assignment';
        $skipInvoice = (bool) ($payload['skip_invoice'] ?? true);

        if (! empty($payload['preserve_fee'])) {
            return $assignment;
        }

        if ($amount !== null) {
            $legacyPointId = $seedPointId;
            $legacyPointName = $seedPoint?->name
                ?? (($amount == 0.0 && $morningPointId === $ownMeansId && $eveningPointId === $ownMeansId)
                    ? DropOffPoint::OWN_MEANS_NAME
                    : null);

            TransportFeeService::upsertFee([
                'student_id' => $student->id,
                'amount' => $amount,
                'drop_off_point_id' => $legacyPointId,
                'drop_off_point_name' => $legacyPointName,
                'source' => $source,
                'note' => $note,
                'pricing_mode' => 'imported',
                'year' => $year,
                'term' => $term,
                'skip_invoice' => $skipInvoice,
            ]);
        } else {
            TransportFeeService::recalculateForStudent(
                (int) $student->id,
                $year,
                $term,
                $skipInvoice,
                'calculated',
                $note
            );
        }

        return $assignment;
    }

    public static function clear(Student $student, bool $skipInvoice = false, ?int $year = null, ?int $term = null, bool $preserveFee = false): void
    {
        [$year, $term] = TransportFeeService::resolveYearAndTerm($year, $term);

        StudentAssignment::where('student_id', $student->id)
            ->where('year', $year)
            ->where('term', $term)
            ->delete();

        [$currentYear, $currentTerm] = TransportFeeService::resolveYearAndTerm();
        if ((int) $year === (int) $currentYear && (int) $term === (int) $currentTerm) {
            $student->trip_id = null;
            $student->drop_off_point_id = null;
            $student->drop_off_point_other = null;
            $student->drop_off_point = null;
            $student->save();
        }

        if ($preserveFee) {
            return;
        }

        TransportFeeService::upsertFee([
            'student_id' => $student->id,
            'amount' => 0,
            'year' => $year,
            'term' => $term,
            'drop_off_point_id' => null,
            'drop_off_point_name' => null,
            'source' => 'manual',
            'note' => 'Transport not required',
            'pricing_mode' => 'imported',
            'skip_invoice' => $skipInvoice,
        ]);
    }

    /**
     * Whether a trip is required for a pickup/drop-off point (not required for OWN MEANS).
     */
    public static function tripRequiredForPoint(mixed $pointId): bool
    {
        $id = self::nullableId($pointId);
        if (!$id) {
            return true;
        }

        $ownMeansId = (int) DropOffPoint::ownMeans()->id;

        return (int) $id !== $ownMeansId;
    }

    public static function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    /**
     * Null = not submitted (calculate from rates). 0 is a valid billed amount.
     */
    public static function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }

    public static function tripLegLabel(Trip $trip): string
    {
        $leg = $trip->assignmentLeg();
        if ($leg === 'morning') {
            return 'Morning';
        }
        if ($leg === 'evening') {
            return 'Evening';
        }

        if ($trip->type) {
            return (string) $trip->type;
        }

        if ($trip->direction) {
            return $trip->direction === 'pickup' ? 'Morning' : ($trip->direction === 'dropoff' ? 'Evening' : ucfirst((string) $trip->direction));
        }

        return 'Unspecified';
    }

    public static function vehicleLabel(?Trip $trip): string
    {
        if (! $trip) {
            return 'No vehicle';
        }

        return $trip->vehicle?->vehicle_number
            ?? $trip->vehicle?->registration_number
            ?? 'No vehicle';
    }

    /**
     * Option text inside a vehicle group: "Morning — CBD Route".
     */
    public static function tripOptionLabel(Trip $trip): string
    {
        return self::tripLegLabel($trip).' — '.$trip->name;
    }

    public static function tripLabel(Trip $trip): string
    {
        return self::vehicleLabel($trip).' · '.self::tripOptionLabel($trip);
    }

    /**
     * Group trips by vehicle, sorted by vehicle number then trip name.
     *
     * @param  iterable<Trip>  $trips
     * @return Collection<int, Collection<int, Trip>>
     */
    public static function groupedByVehicle(iterable $trips): Collection
    {
        return collect($trips)
            ->sortBy(function (Trip $trip) {
                $vehicle = mb_strtolower(self::vehicleLabel($trip));
                $leg = $trip->assignmentLeg() === 'evening' ? '2' : '1';

                return sprintf('%s %s %s', $vehicle, $leg, mb_strtolower((string) $trip->name));
            }, SORT_NATURAL)
            ->groupBy(fn (Trip $trip) => (string) ($trip->vehicle_id ?: 'none'))
            ->values();
    }
}
