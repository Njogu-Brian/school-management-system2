<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Staff;

class StaffWeekly extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_ending',
        'campus',
        'staff_id',
        'on_time_all_week',
        'lessons_missed',
        'books_marked',
        'schemes_updated',
        'class_control',
        'general_performance',
        'notes',
    ];

    protected $casts = [
        'week_ending' => 'date',
        'on_time_all_week' => 'boolean',
        'books_marked' => 'boolean',
        'schemes_updated' => 'boolean',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
