<?php

namespace App\Models\AcademicReports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicReportAnswer extends Model
{
    protected $table = 'academic_report_answers';

    protected $fillable = [
        'submission_id',
        'question_id',
        'value_text',
        'value_json',
        'file_path',
    ];

    protected $casts = [
        'value_json' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AcademicReportSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(AcademicReportQuestion::class, 'question_id');
    }
}

