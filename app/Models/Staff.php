<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','staff_id','first_name','middle_name','last_name',
        'work_email','personal_email','phone_number','id_number',
        'date_of_birth','gender','marital_status','residential_address',
        'emergency_contact_name','emergency_contact_relationship','emergency_contact_phone',
        'kra_pin','nssf','nhif','bank_name','bank_branch','bank_account',
        'department_id','job_title_id','staff_category_id','supervisor_id',
        'photo','status',
        'hire_date','termination_date','employment_status','employment_type',
        'contract_start_date','contract_end_date'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'date_of_birth' => 'date',
    ];

    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) {
            // photo holds a path like "staff_photos/xxx.jpg" saved on the "public" disk
            $path = str_replace('\\', '/', trim($this->photo)); // Normalize path separators and trim
            
            // Remove leading slash if present
            $path = ltrim($path, '/');
            
            // Check if file exists in storage
            $fullPath = storage_path('app/public/'.$path);
            if (file_exists($fullPath)) {
                // Generate URL - asset() will create the correct URL
                return asset('storage/'.$path);
            }
            
            // Also check public/storage (symlink target)
            $publicPath = public_path('storage/'.$path);
            if (file_exists($publicPath)) {
                return asset('storage/'.$path);
            }
        }
        return 'https://ui-avatars.com/api/?name='.urlencode($this->full_name).'&background=0D8ABC&color=fff&size=128';
    }
    public function user(){ return $this->belongsTo(User::class); }
    public function supervisor(){ return $this->belongsTo(Staff::class, 'supervisor_id'); }
    public function subordinates(){ return $this->hasMany(Staff::class, 'supervisor_id'); }
    public function meta(){ return $this->hasMany(StaffMeta::class); }

    public function category(){ return $this->belongsTo(StaffCategory::class, 'staff_category_id'); }
    public function department(){ return $this->belongsTo(Department::class, 'department_id'); }
    public function jobTitle(){ return $this->belongsTo(JobTitle::class, 'job_title_id'); }

    public function getFullNameAttribute(){ return "{$this->first_name} {$this->last_name}"; }

    public function teachesSubjectInClass(int $subjectId, int $classroomId): bool
    {
        return \DB::table('classroom_subjects')
            ->where('staff_id', $this->id)
            ->where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->exists();
    }

    public function leaveBalances()
    {
        return $this->hasMany(StaffLeaveBalance::class);
    }

    public function salaryStructures()
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function activeSalaryStructure()
    {
        return $this->hasOne(SalaryStructure::class)->active()->latest('effective_from');
    }

    public function payrollRecords()
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function salaryHistory()
    {
        return $this->hasMany(SalaryHistory::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function attendance()
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function documents()
    {
        return $this->hasMany(StaffDocument::class);
    }

}
