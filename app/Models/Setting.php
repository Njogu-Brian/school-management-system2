<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'category',
        'key',
        'value',
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
