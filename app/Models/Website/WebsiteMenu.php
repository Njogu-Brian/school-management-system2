<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteMenu extends Model
{
    protected $table = 'website_menus';

    protected $fillable = ['name', 'location'];

    public function items(): HasMany
    {
        return $this->hasMany(WebsiteMenuItem::class, 'menu_id')->orderBy('sort_order');
    }
}
