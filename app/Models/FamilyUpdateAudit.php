<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyUpdateAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'student_id',
        'changed_by_user_id',
        'source',
        'field',
        'before',
        'after',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}

