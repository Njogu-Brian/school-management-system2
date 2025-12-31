<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyStatementTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'student_id',
        'admission_number',
        'student_name',
        'academic_year',
        'term_name',
        'term_number',
        'class_label',
        'source_label',
        'starting_balance',
        'ending_balance',
        'status',
        'confidence',
        'notes',
    ];

    protected $casts = [
        'starting_balance' => 'decimal:2',
        'ending_balance' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegacyFinanceImportBatch::class, 'batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(LegacyStatementLine::class, 'term_id');
    }

    /**
     * Get balance brought forward from legacy data for a student.
     * This returns the ending_balance from the last term before 2026.
     * This balance should be used when creating 2026 invoices.
     *
     * @param int|Student $student Student ID or Student model instance
     * @return float|null Balance brought forward, or null if no legacy data exists
     */
    public static function getBalanceBroughtForward($student): ?float
    {
        $studentId = $student instanceof Student ? $student->id : $student;
        
        // Get the last term before 2026 for this student, ordered by academic year and term number
        $lastTerm = self::where('student_id', $studentId)
            ->where('academic_year', '<', 2026)
            ->whereNotNull('ending_balance')
            ->orderBy('academic_year', 'desc')
            ->orderBy('term_number', 'desc')
            ->first();
        
        return $lastTerm ? (float) $lastTerm->ending_balance : null;
    }

    /**
     * Get balance brought forward by admission number (useful when student_id is not yet linked).
     *
     * @param string $admissionNumber
     * @return float|null Balance brought forward, or null if no legacy data exists
     */
    public static function getBalanceBroughtForwardByAdmission(string $admissionNumber): ?float
    {
        // Get the last term before 2026 for this admission number, ordered by academic year and term number
        $lastTerm = self::where('admission_number', $admissionNumber)
            ->where('academic_year', '<', 2026)
            ->whereNotNull('ending_balance')
            ->orderBy('academic_year', 'desc')
            ->orderBy('term_number', 'desc')
            ->first();
        
        return $lastTerm ? (float) $lastTerm->ending_balance : null;
    }
}

