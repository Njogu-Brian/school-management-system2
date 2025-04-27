<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'content', 'active', 'expires_at',
    ];

    // protected $dates = ['expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];    

    public function isActive()
    {
        return $this->active && (!$this->expires_at || $this->expires_at->isFuture());
    }
}
