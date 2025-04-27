<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['module', 'feature'];

    public function roles()
    {
        return $this->belongsToMany(Role::class)
                    ->withPivot(['can_view', 'can_add', 'can_edit', 'can_delete'])
                    ->withTimestamps();
    }
}
