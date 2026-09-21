<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBiometricUnlock extends Model
{
    protected $fillable = [
        'user_id',
        'selector',
        'secret_hash',
        'device_name',
        'last_used_at',
    ];

    protected $hidden = [
        'secret_hash',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
