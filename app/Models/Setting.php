<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'app_version', 'maintenance_mode', 'show_announcements',
        'staff_id_prefix', 'staff_id_start',
        'student_id_prefix', 'student_id_start',
    ];

    public $timestamps = false;

    /**
     * Get setting by key (static accessor)
     */
    public static function get($key, $default = null)
    {
        return optional(self::where('key', $key)->first())->value ?? $default;
    }

    /**
     * Set or update a setting
     */
    public static function set($key, $value, $category = 'general')
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'category' => $category]
        );
    }
}
