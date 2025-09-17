<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationTemplate extends Model
{
    protected $fillable = [
        'code',        // unique, used across modules
        'title',       // human-readable label
        'type',        // 'email' | 'sms'
        'subject',     // email only (nullable)
        'content',     // body text (email HTML or SMS text)
        'attachment',  // email only (nullable, stored path)
    ];
}
