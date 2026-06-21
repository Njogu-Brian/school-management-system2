<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class AssistantKnowledgeArticle extends Model
{
    protected $fillable = ['title', 'topic', 'content', 'page_context', 'published', 'priority'];

    protected $casts = [
        'page_context' => 'array',
        'published' => 'boolean',
    ];
}
