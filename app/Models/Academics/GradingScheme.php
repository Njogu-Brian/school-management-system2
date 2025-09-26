<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class GradingScheme extends Model
{
    protected $fillable = ['name','type','meta','is_default'];

    protected $casts = [
        'meta' => 'array',
        'is_default' => 'boolean',
    ];

    public function bands() {
        return $this->hasMany(GradingBand::class);
    }
}
