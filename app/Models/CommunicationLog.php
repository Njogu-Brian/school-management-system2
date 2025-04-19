<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationLog extends Model
{
    protected $fillable = [
        'recipient_type', 'recipient_id', 'contact', 'channel',
        'message', 'status', 'type', 'sent_at', 'created_at',
        'classroom_id', 'stream_id', 'scope'
    ];
    

    public function classroom() {
        return $this->belongsTo(Classroom::class);
    }
    
    public function stream() {
        return $this->belongsTo(Stream::class);
    }
    
}
