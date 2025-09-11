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
        'department_id','job_title_id','role_id','supervisor_id',
        'photo','status'
    ];

    /** 🔗 Relationships */

    // Each staff has a corresponding user account
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Supervisor ↔ Subordinates
    public function supervisor()
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Staff::class, 'supervisor_id');
    }

    // Extra profile data via StaffMeta
    public function meta()
    {
        return $this->hasMany(StaffMeta::class);
    }

    // Staff Role (e.g., Teacher, Accountant, Driver)
    public function role()
    {
        return $this->belongsTo(StaffRole::class, 'role_id');
    }

    // Department (e.g., Academics, Transport, Finance)
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Job title (e.g., Principal, Class Teacher, Chef)
    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }
}
