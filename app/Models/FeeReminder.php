<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeReminder extends Model
{
    protected $fillable = [
        'student_id',
        'invoice_id',
        'hashed_id',
        'channel',
        'status',
        'outstanding_amount',
        'due_date',
        'days_before_due',
        'sent_at',
        'message',
        'error_message',
    ];

    protected $casts = [
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'outstanding_amount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Generate hashed ID for secure URL access
     */
    public static function generateHashedId(): string
    {
        do {
            $hash = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, 10);
        } while (self::where('hashed_id', $hash)->exists());
        
        return $hash;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($reminder) {
            if (!$reminder->hashed_id) {
                $reminder->hashed_id = self::generateHashedId();
            }
        });
    }
}
