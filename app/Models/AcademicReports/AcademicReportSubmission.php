<?php

namespace App\Models\AcademicReports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicReportSubmission extends Model
{
    protected $table = 'academic_report_submissions';

    protected $fillable = [
        'template_id',
        'submitted_by_user_id',
        'is_anonymous',
        'submitted_for',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'submitted_for' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(AcademicReportTemplate::class, 'template_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AcademicReportAnswer::class, 'submission_id');
    }
}

