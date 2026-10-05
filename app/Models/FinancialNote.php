<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'family_id',
        'body',
        'promise_date',
        'is_pinned',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'promise_date' => 'date',
        'is_pinned' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Notes for a student including family-scoped notes.
     */
    public static function forStudentContext(?int $studentId, ?int $familyId = null)
    {
        return static::query()
            ->with(['creator:id,name'])
            ->where(function ($q) use ($studentId, $familyId) {
                if ($studentId) {
                    $q->where('student_id', $studentId);
                }
                if ($familyId) {
                    $q->orWhere('family_id', $familyId);
                }
                if (!$studentId && !$familyId) {
                    $q->whereRaw('1 = 0');
                }
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at');
    }
}
