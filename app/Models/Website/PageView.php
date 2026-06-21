<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = ['page', 'visitor_id', 'device', 'source', 'duration', 'viewed_at'];

    protected $casts = ['viewed_at' => 'datetime'];
}
