<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'page_type', 'reference_id', 'meta_title', 'meta_description',
        'keywords', 'og_image', 'schema_markup',
    ];

    protected $casts = ['schema_markup' => 'array'];
}
