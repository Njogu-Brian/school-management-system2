<?php

namespace App\Models\AcademicReports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicReportQuestion extends Model
{
    protected $table = 'academic_report_questions';

    protected $fillable = [
        'template_id',
        'type',
        'label',
        'help_text',
        'is_required',
        'options',
        'display_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(AcademicReportTemplate::class, 'template_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AcademicReportAnswer::class, 'question_id');
    }
}

