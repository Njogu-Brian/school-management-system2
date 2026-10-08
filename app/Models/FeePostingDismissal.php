<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePostingDismissal extends Model
{
    protected $fillable = [
        'student_id',
        'votehead_id',
        'year',
        'term',
        'new_amount_cents',
        'action',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }
}
