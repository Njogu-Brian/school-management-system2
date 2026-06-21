<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class SchoolMeal extends Model
{
    protected $fillable = [
        'meal_date',
        'day_of_week',
        'breakfast',
        'lunch',
        'snack',
        'notes',
    ];

    protected $casts = [
        'meal_date' => 'date',
    ];
}
