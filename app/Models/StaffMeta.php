<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMeta extends Model
{
    use HasFactory;

    protected $fillable = ['staff_id','field_key','field_value'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
