<?php

namespace App\Models\AcademicReports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicReportAssignment extends Model
{
    protected $table = 'academic_report_assignments';

    protected $fillable = [
        'template_id',
        'target_type',
        'role_name',
        'user_id',
        'classroom_id',
        'stream_id',
        'subject_id',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(AcademicReportTemplate::class, 'template_id');
    }
}

