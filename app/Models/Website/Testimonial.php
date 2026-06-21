<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'name',
        'relationship',
        'message',
        'photo',
        'video_url',
        'featured',
        'approved',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'approved' => 'boolean',
    ];

    public function photoUrl(): ?string
    {
        return $this->photo ? asset('website/'.$this->photo) : null;
    }
}
