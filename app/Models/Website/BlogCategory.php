<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    protected $fillable = ['name', 'slug'];

    protected static function booted(): void
    {
        static::saving(fn ($c) => $c->slug = $c->slug ?: Str::slug($c->name));
    }

    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_blog_category');
    }
}
