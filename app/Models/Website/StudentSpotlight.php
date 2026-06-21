<?php

namespace App\Models\Website;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSpotlight extends Model
{
    protected $fillable = [
        'student_id',
        'title',
        'story',
        'achievement',
        'cover_image',
        'featured',
        'published',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'published' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image ? asset('website/'.$this->cover_image) : null;
    }
}
