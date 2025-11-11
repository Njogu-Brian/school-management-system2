<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StaffDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'document_type',
        'title',
        'file_path',
        'expiry_date',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    public function isExpiringSoon($days = 30)
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}
