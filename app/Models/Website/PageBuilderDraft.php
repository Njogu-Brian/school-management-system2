<?php

namespace App\Models\Website;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBuilderDraft extends Model
{
    protected $fillable = ['page_id', 'sections', 'updated_by'];

    protected $casts = ['sections' => 'array'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
