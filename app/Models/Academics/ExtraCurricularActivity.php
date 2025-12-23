<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ExtraCurricularActivity extends Model
{
    protected $fillable = [
        'name',
        'type',
        'day',
        'start_time',
        'end_time',
        'period',
        'academic_year_id',
        'term_id',
        'classroom_ids',
        'staff_ids',
        'description',
        'is_active',
        'repeat_weekly',
        'fee_amount',
        'votehead_id',
        'auto_invoice',
        'student_ids'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'classroom_ids' => 'array',
        'staff_ids' => 'array',
        'student_ids' => 'array',
        'is_active' => 'boolean',
        'repeat_weekly' => 'boolean',
        'auto_invoice' => 'boolean',
        'period' => 'integer',
        'fee_amount' => 'decimal:2',
    ];

    public function academicYear()
    {
        return $this->belongsTo(\App\Models\AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(\App\Models\Term::class);
    }

    public function classrooms()
    {
        if (!$this->classroom_ids) {
            return collect();
        }
        return Classroom::whereIn('id', $this->classroom_ids)->get();
    }

    public function staff()
    {
        if (!$this->staff_ids) {
            return collect();
        }
        return \App\Models\Staff::whereIn('id', $this->staff_ids)->get();
    }

    public function votehead()
    {
        return $this->belongsTo(\App\Models\Votehead::class);
    }

    public function students()
    {
        if (!$this->student_ids) {
            return collect();
        }
        return \App\Models\Student::whereIn('id', $this->student_ids)->get();
    }

    /**
     * Create votehead and assign to all classes
     */
    public function syncFinanceIntegration()
    {
        if (!$this->fee_amount || $this->fee_amount <= 0) {
            return;
        }

        // Create or get votehead
        $votehead = \App\Models\Votehead::firstOrCreate(
            ['name' => $this->name . ' - Activity Fee'],
            [
                'description' => "Optional fee for {$this->name} activity",
                'is_mandatory' => false,
                'charge_type' => 'per_student',
            ]
        );

        $this->update(['votehead_id' => $votehead->id]);

        // Add to all class fee structures
        $classrooms = $this->classrooms();
        foreach ($classrooms as $classroom) {
            $feeStructure = \App\Models\FeeStructure::firstOrCreate(
                [
                    'classroom_id' => $classroom->id,
                    'year' => $this->academicYear->year ?? date('Y'),
                ]
            );

            // Add votehead to fee structure if not exists
            \App\Models\FeeCharge::firstOrCreate(
                [
                    'fee_structure_id' => $feeStructure->id,
                    'votehead_id' => $votehead->id,
                ],
                [
                    'amount' => $this->fee_amount,
                    'term' => $this->term->id ?? 1,
                ]
            );
        }

        // Auto-invoice assigned students
        if ($this->auto_invoice && $this->student_ids) {
            $this->invoiceStudents();
        }
    }

    /**
     * Invoice students for this activity
     */
    public function invoiceStudents()
    {
        if (!$this->votehead_id || !$this->student_ids) {
            return;
        }

        $term = $this->term;
        $year = $this->academicYear;

        foreach ($this->student_ids as $studentId) {
            \App\Models\OptionalFee::firstOrCreate(
                [
                    'student_id' => $studentId,
                    'votehead_id' => $this->votehead_id,
                    'term' => $term->id ?? 1,
                    'year' => $year->year ?? date('Y'),
                ],
                [
                    'amount' => $this->fee_amount,
                    'status' => 'billed',
                ]
            );
        }
    }
}
