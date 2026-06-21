<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadMagnetDownload extends Model
{
    protected $fillable = ['lead_magnet_id', 'name', 'email', 'phone'];

    public function leadMagnet(): BelongsTo
    {
        return $this->belongsTo(LeadMagnet::class);
    }
}
