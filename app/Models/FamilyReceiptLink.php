<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FamilyReceiptLink extends Model
{
    protected $fillable = ['family_id', 'token', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($link) {
            if (empty($link->token)) {
                $link->token = static::generateToken();
            }
        });
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (static::where('token', $token)->exists());
        return $token;
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function getUrl(): string
    {
        return route('receipts.my-receipts', $this->token);
    }
}
