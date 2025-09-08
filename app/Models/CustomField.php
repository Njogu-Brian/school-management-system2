<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',      // e.g. 'staff'
        'name',        // Label shown in form
        'field_type',  // text, number, email, file, date, select, etc.
        'options',     // for select/radio/checkbox (JSON)
        'required',    // boolean
    ];

    protected $casts = [
        'options' => 'array',
        'required' => 'boolean',
    ];
}
