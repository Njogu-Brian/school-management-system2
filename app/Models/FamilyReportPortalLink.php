<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FamilyReportPortalLink extends Model
{
    protected $fillable = [
        'family_id',
        'student_id',
        'academic_year_id',
        'term_id',
        'token',
        'is_active',
        'last_sent_at',
        'click_count',
        'first_clicked_at',
        'last_clicked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'first_clicked_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'click_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $link) {
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
        do {
            $token = Str::random(32);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function isStudentOnly(): bool
    {
        return $this->student_id !== null && $this->family_id === null;
    }

    public function getUrl(): string
    {
        return route('family.reports.portal', $this->token);
    }

    public function recordClick(): void
    {
        $now = now();
        $this->increment('click_count');
        if (! $this->first_clicked_at) {
            $this->first_clicked_at = $now;
        }
        $this->last_clicked_at = $now;
        $this->saveQuietly();
    }
}
