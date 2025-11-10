<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceReasonCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'requires_excuse',
        'is_medical',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_excuse' => 'boolean',
        'is_medical' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'reason_code_id');
    }

    /**
     * Get active reason codes query builder ordered by sort order
     */
    public static function active()
    {
        return static::where('is_active', true)->orderBy('sort_order');
    }
}
