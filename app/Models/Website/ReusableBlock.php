<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class ReusableBlock extends Model
{
    protected $fillable = ['name', 'block_type', 'content', 'settings'];

    protected $casts = ['settings' => 'array'];
}
