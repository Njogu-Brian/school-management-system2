<?php

namespace App\Models;

use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantClassTeacherAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'stream_id',
        'staff_id',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
