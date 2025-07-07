<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'app_version', 'maintenance_mode', 'show_announcements',
        'staff_id_prefix', 'staff_id_start',
        'student_id_prefix', 'student_id_start',
    ];

    public $timestamps = true;

    // ✅ Add this static method
    public static function set($key, $value)
    {
        $system = self::first();

        if (!$system) {
            $system = self::create([$key => $value]);
        } else {
            $system->update([$key => $value]);
        }

        return $system;
    }

    public static function getValue($key, $default = null)
    {
        return optional(self::first())->$key ?? $default;
    }
    
    public static function incrementValue($key, $default = 0)
    {
        $setting = static::firstOrCreate(['key' => $key], ['value' => $default]);
        $value = (int) $setting->value + 1;
        $setting->update(['value' => $value]);
        return $value;
    }

}
