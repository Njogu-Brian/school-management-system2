<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FamilyUpdateLink extends Model
{
    protected $fillable = [
        'family_id',
        'token',
        'is_active',
        'last_sent_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
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
        return Str::random(12);
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }
}

