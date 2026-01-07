<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationLog extends Model
{
    protected $fillable = [
        'recipient_type', 'recipient_id', 'contact', 'channel',
        'title', 'message', 'status', 'type', 'sent_at',
        'classroom_id', 'stream_id', 'scope', 'response',
        'provider_id', 'provider_status', 'delivered_at', 'error_code',
        'payment_id'
    ];
    
    protected $guarded = []; 

    protected $casts = [
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'response'     => 'array',   
    ];

    public function classroom() {
        return $this->belongsTo(Classroom::class);
    }
    
    public function stream() {
        return $this->belongsTo(Stream::class);
    }
    
    public function payment() {
        return $this->belongsTo(Payment::class);
    }
    
}
