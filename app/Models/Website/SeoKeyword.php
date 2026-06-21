<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoKeyword extends Model
{
    protected $fillable = ['keyword', 'page_id', 'target_url', 'position', 'search_volume', 'priority'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
