<?php

namespace App\Models\Academics;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ExamSession extends Model
{
    protected $fillable = [
        'exam_type_id',
        'academic_year_id',
        'term_id',
        'classroom_id',
        'stream_id',
        'name',
        'modality',
        'weight',
        'starts_on',
        'ends_on',
        'status',
        'created_by',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'weight' => 'decimal:2',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class, 'exam_type_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function stream()
    {
        return $this->belongsTo(\App\Models\Stream::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Subject papers (exams) belonging to this sitting. */
    public function papers()
    {
        return $this->hasMany(Exam::class, 'exam_session_id');
    }

    public function scopeForScope(Builder $q, int $examTypeId, int $academicYearId, int $termId, int $classroomId, ?int $streamId): Builder
    {
        return $q->where('exam_type_id', $examTypeId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('classroom_id', $classroomId)
            ->when($streamId, fn ($qq) => $qq->where('stream_id', $streamId), fn ($qq) => $qq->whereNull('stream_id'));
    }

    public static function findOrCreateForScope(
        int $examTypeId,
        int $academicYearId,
        int $termId,
        int $classroomId,
        ?int $streamId,
        string $name,
        string $modality = 'physical',
        float $weight = 100.0,
        ?string $startsOn = null,
        ?string $endsOn = null
    ): self {
        $existing = static::query()
            ->forScope($examTypeId, $academicYearId, $termId, $classroomId, $streamId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'exam_type_id' => $examTypeId,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'classroom_id' => $classroomId,
            'stream_id' => $streamId,
            'name' => $name,
            'modality' => $modality,
            'weight' => $weight,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);
    }
}
