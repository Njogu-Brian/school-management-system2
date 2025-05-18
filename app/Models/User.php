<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    protected $with = ['roles'];

    protected $fillable = [
        'name', 'email', 'password',
    ];

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isTeacher()
    {
        return $this->role === 'teacher';
    }


    public function isStudent()
    {
        return $this->role === 'student';
    }

    public function roles()
    {
        return $this->belongsToMany(\App\Models\Role::class);
    }

    public function hasRole($roleName)
    {
        return $this->roles->contains('name', $roleName);
    }
    public function streams()
    {
        return $this->belongsToMany(Stream::class, 'stream_teacher', 'teacher_id', 'stream_id');
    }



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Interact with the user's first name.
     *
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    // 0- user 1-admin 2-teacher
    protected function type(): Attribute
    {
        return new Attribute(
            get: fn ($value) => match($value) {
                0 => 'student',
                1 => 'admin',
                2 => 'teacher',
                default => 'unknown',
            },
        );
    }
    
}
