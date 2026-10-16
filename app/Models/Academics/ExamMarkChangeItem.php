<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamMarkChangeItem extends Model
{
    protected $fillable = [
        'batch_id',
        'exam_id',
        'student_id',
        'editor_id',
        'editor_name',
        'student_name',
        'exam_name',
        'subject_name',
        'classroom_name',
        'score_from',
        'score_to',
        'changed_at',
    ];

    protected $casts = [
        'score_from' => 'float',
        'score_to' => 'float',
        'changed_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ExamMarkChangeBatch::class, 'batch_id');
    }

    public function paperMeta(): string
    {
        $bits = [];
        $classroom = trim((string) $this->classroom_name);
        if ($classroom !== '') {
            $bits[] = $classroom;
        }

        $subject = trim((string) $this->subject_name);
        $examName = mb_strtolower((string) $this->exam_name);
        if ($subject !== '' && ! str_contains($examName, mb_strtolower($subject))) {
            $bits[] = $subject;
        }

        return implode(' · ', $bits);
    }
}
