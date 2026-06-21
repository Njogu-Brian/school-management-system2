<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteMenuItem extends Model
{
    protected $table = 'website_menu_items';

    protected $fillable = ['menu_id', 'title', 'url', 'parent_id', 'sort_order'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(WebsiteMenu::class, 'menu_id');
    }
}
