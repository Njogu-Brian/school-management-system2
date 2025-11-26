<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'hostel_id',
        'meal_type',
        'menu_date',
        'items',
        'prepared_by',
        'notes',
    ];

    protected $casts = [
        'menu_date' => 'date',
        'items' => 'array',
    ];

    /**
     * Get the hostel
     */
    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    /**
     * Get the staff who prepared the menu
     */
    public function preparer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'prepared_by');
    }
}

