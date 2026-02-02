<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneNumberNormalizationLog extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'field',
        'old_value',
        'new_value',
        'country_code',
        'source',
        'user_id',
    ];
}
