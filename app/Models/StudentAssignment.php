<?php

namespace App\Models;

use App\Services\TransportFeeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'year',
        'term',
        'academic_year_id',
        'morning_trip_id',
        'evening_trip_id',
        'morning_drop_off_point_id',
        'evening_drop_off_point_id',
    ];

    public function student()
    {
        // Bypass Student "active" scope so archived/alumni students still resolve.
        return $this->belongsTo(Student::class)->withoutGlobalScope('active');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function morningTrip()
    {
        return $this->belongsTo(Trip::class, 'morning_trip_id');
    }

    public function eveningTrip()
    {
        return $this->belongsTo(Trip::class, 'evening_trip_id');
    }

    public function morningDropOffPoint()
    {
        return $this->belongsTo(DropOffPoint::class, 'morning_drop_off_point_id');
    }

    public function eveningDropOffPoint()
    {
        return $this->belongsTo(DropOffPoint::class, 'evening_drop_off_point_id');
    }

    public function scopeForTerm(Builder $query, ?int $year = null, ?int $term = null): Builder
    {
        [$year, $term] = TransportFeeService::resolveYearAndTerm($year, $term);

        return $query->where('year', $year)->where('term', $term);
    }

    public static function forStudent(int $studentId, ?int $year = null, ?int $term = null, bool $fallback = false): ?self
    {
        [$year, $term] = TransportFeeService::resolveYearAndTerm($year, $term);

        $row = static::query()
            ->where('student_id', $studentId)
            ->where('year', $year)
            ->where('term', $term)
            ->first();

        if ($row || ! $fallback) {
            return $row;
        }

        return static::query()
            ->where('student_id', $studentId)
            ->orderByDesc('year')
            ->orderByDesc('term')
            ->orderByDesc('id')
            ->first();
    }

    public static function firstOrNewForTerm(int $studentId, ?int $year = null, ?int $term = null): self
    {
        [$year, $term, $academicYearId] = TransportFeeService::resolveYearAndTerm($year, $term);

        $row = static::firstOrNew([
            'student_id' => $studentId,
            'year' => $year,
            'term' => $term,
        ]);
        if (! $row->academic_year_id) {
            $row->academic_year_id = $academicYearId;
        }

        return $row;
    }

    public static function keyedForStudents($studentIds, ?int $year = null, ?int $term = null, bool $prefillLatest = false): \Illuminate\Support\Collection
    {
        [$year, $term] = TransportFeeService::resolveYearAndTerm($year, $term);
        $ids = collect($studentIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = static::query()
            ->whereIn('student_id', $ids)
            ->where('year', $year)
            ->where('term', $term)
            ->get()
            ->keyBy(fn ($row) => (int) $row->student_id);

        if (! $prefillLatest) {
            return $rows;
        }

        $missing = $ids->reject(fn ($id) => $rows->has($id));
        if ($missing->isEmpty()) {
            return $rows;
        }

        $latest = static::query()
            ->whereIn('student_id', $missing)
            ->orderByDesc('year')
            ->orderByDesc('term')
            ->orderByDesc('id')
            ->get()
            ->unique('student_id');

        foreach ($latest as $row) {
            $row->setAttribute('prefilled_from_prior_term', true);
            $rows->put((int) $row->student_id, $row);
        }

        return $rows;
    }
}
