<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMedicalRecord extends Model
{
    protected $fillable = [
        'student_id',
        'record_type',
        'record_date',
        'title',
        'description',
        'doctor_name',
        'clinic_hospital',
        'medication_name',
        'medication_dosage',
        'medication_start_date',
        'medication_end_date',
        'vaccination_name',
        'vaccination_date',
        'next_due_date',
        'certificate_type',
        'certificate_file_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'record_date' => 'date',
        'medication_start_date' => 'date',
        'medication_end_date' => 'date',
        'vaccination_date' => 'date',
        'next_due_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
