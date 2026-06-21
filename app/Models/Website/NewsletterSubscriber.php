<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'status', 'source'];
}
