<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledCommunication extends Model
{
    protected $fillable = [
        'type', 'template_id', 'target', 'classroom_id', 'send_at', 'status'
    ];

    protected $dates = ['send_at'];

    public function template()
    {
        return $this->belongsTo(\App\Models\CommunicationTemplate::class);
    }
}
