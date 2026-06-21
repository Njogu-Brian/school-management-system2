<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogTag extends Model
{
    protected $fillable = ['name', 'slug'];

    protected static function booted(): void
    {
        static::saving(fn ($t) => $t->slug = $t->slug ?: Str::slug($t->name));
    }

    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_blog_tag');
    }
}
