<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Transport;
use App\Models\Attendance;
use App\Models\ParentInfo;
use App\Models\Academics\StudentDiary;
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
    use HasFactory;
    protected static function boot()
    {
        parent::boot();
        
        // Ensure stream belongs to classroom when saving
        static::saving(function ($student) {
            // Ensure every student has a category (default to "General")
            if (!$student->category_id) {
                $defaultCategory = StudentCategory::firstOrCreate(
                    ['name' => 'General'],
                    ['description' => 'Default category for students']
                );
                $student->category_id = $defaultCategory->id;
            }

            if ($student->stream_id && $student->classroom_id) {
                $stream = Stream::find($student->stream_id);
                if ($stream) {
                    $isValidStream = $stream->classroom_id == $student->classroom_id || 
                                    $stream->classrooms->contains('id', $student->classroom_id);
                    if (!$isValidStream) {
                        // Clear stream if it doesn't belong to classroom
                        $student->stream_id = null;
                    }
                }
            }
        });
    }
    
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
        'is_alumni',
        'alumni_date',
        'route_id',
        'trip_id',
        'drop_off_point_id',
        'drop_off_point_other',
        'drop_off_point',
        // Extended demographics (trimmed)
        'religion',
        'allergies',
        'chronic_conditions',
        'emergency_contact_name',
        'emergency_contact_phone',
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
        'archived_reason',
        'archived_notes',
        'archived_by',
        'has_allergies',
        'allergies_notes',
        'is_fully_immunized',
        'residential_area',
        'preferred_hospital',
    ];

    protected $casts = [
        'dob'               => 'date',
        'admission_date'    => 'date',
        'graduation_date'   => 'date',
        'transfer_date'     => 'date',
        'status_changed_at' => 'datetime',
        'alumni_date'       => 'date',
        'archive'           => 'boolean',
        'is_readmission'    => 'boolean',
        'archived_at'       => 'datetime',
        'archived_by'       => 'integer',
        'has_allergies'     => 'boolean',
        'is_fully_immunized'=> 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    public function diary()
    {
        return $this->hasOne(StudentDiary::class);
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
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function category()
    {
        return $this->belongsTo(StudentCategory::class, 'category_id');
    }

    /**
     * Check if student is newly admitted (admitted in the current or recent academic year)
     * This is used to determine if once-only fees should be charged
     */
    public function isNewlyAdmitted(?int $academicYear = null): bool
    {
        if (!$this->admission_date) {
            // No admission date - assume existing student
            return false;
        }
        
        // If no academic year provided, use current year
        if ($academicYear === null) {
            $academicYear = (int) date('Y');
        }
        
        // Handle both Carbon instance and string
        if ($this->admission_date instanceof \Carbon\Carbon) {
            $admissionYear = (int) $this->admission_date->format('Y');
        } else {
            $admissionYear = (int) date('Y', strtotime($this->admission_date));
        }
        
        // Consider student as "new" if admitted in current academic year or later
        return $admissionYear >= $academicYear;
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

        static::created(function (self $student) {
            if (!$student->diary()->exists()) {
                $student->diary()->create();
            }
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
