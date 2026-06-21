<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteEventRegistration extends Model
{
    protected $fillable = [
        'website_event_id', 'name', 'phone', 'email', 'attendees', 'status',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(WebsiteEvent::class, 'website_event_id');
    }
}
