<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $guarded = []; // Allow mass assignment for key-value flexibility

    public $timestamps = true;

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Increment a numeric setting value
     */
    public static function incrementValue(string $key, int $by = 1, $defaultStart = 0): int
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            // Create with default start value
            $setting = static::create([
                'key' => $key,
                'value' => (string) $defaultStart,
            ]);
            $current = $defaultStart;
        } else {
            $current = (int) $setting->value;
        }

        $new = $current + $by;
        $setting->value = (string) $new;
        $setting->save();

        return $new;
    }

    /**
     * Get boolean setting value
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::get($key, $default ? '1' : '0');
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Set boolean setting value
     */
    public static function setBool(string $key, bool $value)
    {
        return static::set($key, $value ? '1' : '0');
    }

    /**
     * Get integer setting value
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = static::get($key, (string) $default);
        return (int) $value;
    }

    /**
     * Set integer setting value
     */
    public static function setInt(string $key, int $value)
    {
        return static::set($key, (string) $value);
    }

    /**
     * Get JSON setting value
     */
    public static function getJson(string $key, $default = [])
    {
        $value = static::get($key);
        if ($value === null) {
            return $default;
        }
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $default;
    }

    /**
     * Set JSON setting value
     */
    public static function setJson(string $key, $value)
    {
        return static::set($key, json_encode($value));
    }
}

