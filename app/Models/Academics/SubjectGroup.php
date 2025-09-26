<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class SubjectGroup extends Model
{
    protected $fillable = ['name','code','display_order','description'];

    public function subjects() {
        return $this->hasMany(Subject::class);
    }
}
