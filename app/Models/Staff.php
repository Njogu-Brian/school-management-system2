<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','staff_id','first_name','middle_name','last_name',
        'email','phone_number','id_number','date_of_birth','gender',
        'marital_status','address','emergency_contact_name','emergency_contact_phone',
        'kra_pin','nssf','nhif','bank_name','bank_branch','bank_account',
        'department_id','job_title_id','staff_category_id','supervisor_id',
        'photo','status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Staff::class, 'supervisor_id');
    }

    public function meta()
    {
        return $this->hasMany(StaffMeta::class);
    }

    // HR Category (formerly staff_roles)
    public function category()
    {
        return $this->belongsTo(StaffCategory::class, 'staff_category_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

}
