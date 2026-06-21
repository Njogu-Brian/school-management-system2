<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class CampaignLog extends Model
{
    protected $fillable = ['campaign_name', 'type', 'audience', 'sent_count', 'status', 'metadata'];

    protected $casts = ['metadata' => 'array'];
}
