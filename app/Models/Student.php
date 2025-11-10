<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Transport;
use App\Models\Attendance;
use App\Models\ParentInfo;
use App\Models\StudentCategory;
use App\Models\Academics\Stream;
use App\Models\Academics\Classroom;
use App\Models\StudentAssignment;
use App\Models\DropOffPoint;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Family;
use Illuminate\Database\Eloquent\Builder;


class Student extends Model
{
    protected $fillable = [
        'admission_number',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'dob',
        'parent_id',
        'family_id',
        'classroom_id',
        'stream_id',
        'category_id',
        'nemis_number',
        'knec_assessment_number',
        'archive',
        // Extended demographics
        'national_id_number',
        'passport_number',
        'religion',
        'ethnicity',
        'home_address',
        'home_city',
        'home_county',
        'home_postal_code',
        'language_preference',
        'blood_group',
        'allergies',
        'chronic_conditions',
        'medical_insurance_provider',
        'medical_insurance_number',
        'emergency_medical_contact_name',
        'emergency_medical_contact_phone',
        'previous_schools',
        'transfer_reason',
        'has_special_needs',
        'special_needs_description',
        'learning_disabilities',
        // Status & lifecycle
        'status',
        'admission_date',
        'graduation_date',
        'transfer_date',
        'transfer_to_school',
        'status_change_reason',
        'status_changed_by',
        'status_changed_at',
        'is_readmission',
        'previous_student_id',
    ];

    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceForToday()
    {
        return $this->hasOne(Attendance::class)->whereDate('date', today());
    }

    public function route()
    {
        return $this->belongsTo(Transport::class, 'route_id');
    }

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function category()
    {
        return $this->belongsTo(StudentCategory::class, 'category_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->middle_name} {$this->last_name}";
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
    public function siblings()
    {
        return $this->belongsToMany(Student::class, 'student_siblings', 'student_id', 'sibling_id');
    }
    public function assignments()
    {
        return $this->hasMany(StudentAssignment::class);
    }
    public function family()
    {
        return $this->belongsTo(Family::class);
    }
    public function transport()
    {
        return $this->hasOne(Transport::class);
    }
    public function dropOffPoint()
    {
        return $this->belongsTo(DropOffPoint::class, 'drop_off_point_id');
    }
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    // New relationships for enhanced features
    public function medicalRecords()
    {
        return $this->hasMany(StudentMedicalRecord::class);
    }

    public function disciplinaryRecords()
    {
        return $this->hasMany(StudentDisciplinaryRecord::class);
    }

    public function extracurricularActivities()
    {
        return $this->hasMany(StudentExtracurricularActivity::class);
    }

    public function academicHistory()
    {
        return $this->hasMany(StudentAcademicHistory::class);
    }

    public function currentAcademicHistory()
    {
        return $this->hasOne(StudentAcademicHistory::class)->where('is_current', true);
    }

    public function statusChangedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'status_changed_by');
    }

    public function previousStudent()
    {
        return $this->belongsTo(Student::class, 'previous_student_id');
    }

    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('archive', 0);
        });
    }
    public static function withArchived()
    {
        return (new static)->newQueryWithoutScope('active');
    }
    public function getNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getPhotoUrlAttribute(): string
    {
        // prefer stored photo (photo_path or photo)
        $path = $this->photo_path ?? $this->photo ?? null;

        if ($path) {
            // Use the public disk; make sure "php artisan storage:link" is done
            return asset('storage/' . ltrim($path, '/'));
        }

        // Nice initials fallback (no “av” weirdness)
        $name = trim($this->first_name . ' ' . $this->last_name);
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=6c63ff&color=fff&size=128&rounded=true';
    }

}
