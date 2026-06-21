<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class ExitIntentCampaign extends Model
{
    protected $fillable = [
        'title', 'message', 'button_label', 'button_url', 'pages', 'is_active', 'impressions', 'conversions',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
