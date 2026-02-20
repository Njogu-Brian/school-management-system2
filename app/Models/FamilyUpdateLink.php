<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FamilyUpdateLink extends Model
{
    protected $fillable = [
        'family_id',
        'student_id',
        'token',
        'is_active',
        'last_sent_at',
        'click_count',
        'first_clicked_at',
        'last_clicked_at',
        'update_count',
        'last_updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'first_clicked_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'click_count' => 'integer',
        'update_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($link) {
            if (empty($link->token)) {
                $link->token = static::generateToken();
            }
            if ($link->is_active === null) {
                $link->is_active = true;
            }
        });
    }

    public static function generateToken(): string
    {
        // Generate a unique token - keep trying until we get a unique one
        do {
            $token = Str::random(32); // Increased length for better uniqueness
        } while (static::where('token', $token)->exists());
        
        return $token;
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    /** Whether this link is for a single student (no family). */
    public function isStudentOnly(): bool
    {
        return $this->student_id !== null && $this->family_id === null;
    }
}

