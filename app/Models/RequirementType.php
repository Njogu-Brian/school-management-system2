<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequirementType extends Model
{
    protected $fillable = ['name', 'category', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function templates()
    {
        return $this->hasMany(RequirementTemplate::class);
    }

    public function studentRequirements()
    {
        return $this->hasManyThrough(StudentRequirement::class, RequirementTemplate::class);
    }
}
