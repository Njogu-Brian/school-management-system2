<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone_number',
        'id_number',
        'date_of_birth',
        'gender',
        'marital_status',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
    ];
    

    protected $hidden = [
        'password',
    ];

    // App\Models\Staff.php

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

}
