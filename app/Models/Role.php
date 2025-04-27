<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Permission;

class Role extends Model
{
    protected $fillable = ['name'];

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class);
    }
    public function permissions()
    {
        return $this->belongsToMany(Permission::class)
                    ->withPivot(['can_view', 'can_add', 'can_edit', 'can_delete'])
                    ->withTimestamps();
    }
}
