<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'app_version',
        'maintenance_mode',
        'show_announcements',
        'staff_id_prefix',
        'staff_id_start',
        'student_id_prefix',
        'student_id_start',
        'student_id_counter',
        // Optional common fields you referenced in helpers:
        'school_name',
        'phone',
        'email',
        'address',
        'current_term',
        'current_year',
    ];

    public $timestamps = true;

    // Set a single column on the single-row settings
    public static function set(string $key, $value)
    {
        $row = static::first();
        if (!$row) {
            $row = static::create([$key => $value]);
        } else {
            $row->update([$key => $value]);
        }
        return $row;
    }

    public static function getValue(string $key, $default = null)
    {
        $row = static::first();
        return $row ? ($row->{$key} ?? $default) : $default;
    }

    /**
     * Numeric increment for a single column (single-row schema).
     */
    public static function incrementColumn(string $key, int $by = 1, $defaultStart = 0): int
    {
        $row = static::first();
        if (!$row) {
            $row = static::create([$key => $defaultStart]);
        }

        $current = (int) ($row->{$key} ?? $defaultStart);
        $new = $current + $by;

        $row->{$key} = $new;
        $row->save();

        return $new;
    }

    /**
     * Increment a counter column while returning the value that should be used
     * for the current operation. The stored counter is advanced for next time.
     */
    public static function incrementValue(string $key, int $defaultStart = 0, int $step = 1): int
    {
        $row = static::first();
        if (!$row) {
            $row = static::create([$key => $defaultStart]);
            return $defaultStart;
        }

        $current = (int) ($row->{$key} ?? $defaultStart);
        if ($current < $defaultStart) {
            $current = $defaultStart;
        }

        $row->{$key} = $current + $step;
        $row->save();

        return $current;
    }
}
