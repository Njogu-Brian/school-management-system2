<?php

namespace App\Models\Pos;

use App\Models\Student;
use App\Models\Academics\Classroom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PublicShopLink extends Model
{
    protected $table = 'pos_public_shop_links';

    protected $fillable = [
        'token', 'name', 'student_id', 'classroom_id',
        'access_type', 'show_requirements_only', 'allow_custom_items',
        'expires_at', 'usage_count', 'is_active'
    ];

    protected $casts = [
        'show_requirements_only' => 'boolean',
        'allow_custom_items' => 'boolean',
        'expires_at' => 'date',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
    ];

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

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function getUrl(): string
    {
        return route('pos.shop.public', ['token' => $this->token]);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', now());
            });
    }
}



