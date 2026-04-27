<?php

namespace App\Models\AcademicReports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicReportTemplate extends Model
{
    protected $table = 'academic_report_templates';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'status',
        'created_by_user_id',
        'open_from',
        'open_until',
    ];

    protected $casts = [
        'open_from' => 'datetime',
        'open_until' => 'datetime',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(AcademicReportQuestion::class, 'template_id')->orderBy('display_order')->orderBy('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AcademicReportAssignment::class, 'template_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AcademicReportSubmission::class, 'template_id');
    }
}

