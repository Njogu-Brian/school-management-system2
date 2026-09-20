<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_number',
        'driver_name',
        'make',
        'model',
        'type',
        'capacity',
        'chassis_number',
        'insurance_document',
        'logbook_document',
        'photo',
    ];

    // Relationship with Trips
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        $path = str_replace('\\', '/', trim((string) $this->photo));
        $path = ltrim($path, '/');
        if ($path === '') {
            return null;
        }

        try {
            if (storage_public()->exists($path)) {
                $url = storage_public_url($path);
                if ($url) {
                    return $url;
                }
            }
        } catch (\Throwable $e) {
            // Fall through to local public storage.
        }

        try {
            $fullPath = storage_path('app/public/'.$path);
            if (file_exists($fullPath)) {
                $url = asset('storage/'.$path);

                return str_starts_with($url, 'http') ? $url : url($url);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
