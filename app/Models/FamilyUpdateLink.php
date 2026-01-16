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
        return Str::random(12);
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }
}

