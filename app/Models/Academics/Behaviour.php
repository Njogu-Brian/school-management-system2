<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class Behaviour extends Model
{
    // Explicit table name
    protected $table = 'behaviours';

    protected $fillable = [
        'name',
        'type',         // positive | negative
        'description',
    ];
}
