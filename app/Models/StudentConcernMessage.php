<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentConcernMessage extends Model
{
    protected $fillable = [
        'student_concern_id',
        'user_id',
        'body',
        'is_staff',
    ];

    protected $casts = [
        'is_staff' => 'boolean',
    ];

    public function concern(): BelongsTo
    {
        return $this->belongsTo(StudentConcern::class, 'student_concern_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
