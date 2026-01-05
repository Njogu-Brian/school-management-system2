<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditDebitNoteImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'term',
        'votehead_id',
        'academic_year_id',
        'term_id',
        'notes_imported_count',
        'total_credit_amount',
        'total_debit_amount',
        'imported_by',
        'imported_at',
        'reversed_by',
        'reversed_at',
        'is_reversed',
        'notes',
    ];

    protected $casts = [
        'total_credit_amount' => 'decimal:2',
        'total_debit_amount' => 'decimal:2',
        'notes_imported_count' => 'integer',
        'is_reversed' => 'boolean',
        'imported_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function votehead()
    {
        return $this->belongsTo(Votehead::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}

