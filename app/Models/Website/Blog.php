<?php

namespace App\Models\Website;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Blog extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'featured_image',
        'published',
        'published_at',
        'author_id',
        'is_featured',
        'views_count',
    ];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Blog $blog) {
            if (empty($blog->slug) && ! empty($blog->title)) {
                $blog->slug = Str::slug($blog->title);
            }

            if ($blog->published && ! $blog->published_at) {
                $blog->published_at = now();
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories()
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_blog_category');
    }

    public function tags()
    {
        return $this->belongsToMany(BlogTag::class, 'blog_blog_tag');
    }

    public function featuredImageUrl(): ?string
    {
        return $this->featured_image ? asset('website/'.$this->featured_image) : null;
    }
}
