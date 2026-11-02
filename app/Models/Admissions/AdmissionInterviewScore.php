<?php

namespace App\Models\Admissions;

use App\Models\OnlineAdmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionInterviewScore extends Model
{
    protected $fillable = [
        'online_admission_id',
        'subject_id',
        'subject_name',
        'subject_code',
        'score',
        'out_of',
        'notes',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'out_of' => 'decimal:2',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(OnlineAdmission::class, 'online_admission_id');
    }
}
