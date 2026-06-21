<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentCalendarItem extends Model
{
    protected $table = 'content_calendar';

    protected $fillable = ['title', 'type', 'publish_date', 'status', 'notes', 'ai_content_log_id'];

    protected $casts = ['publish_date' => 'date'];

    public function aiLog(): BelongsTo
    {
        return $this->belongsTo(AiContentLog::class, 'ai_content_log_id');
    }
}
