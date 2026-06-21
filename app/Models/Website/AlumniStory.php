<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class AlumniStory extends Model
{
    protected $fillable = [
        'name',
        'graduation_year',
        'headline',
        'story',
        'photo',
        'published',
    ];

    protected $casts = [
        'published' => 'boolean',
    ];

    public function photoUrl(): ?string
    {
        return $this->photo ? asset('website/'.$this->photo) : null;
    }
}
