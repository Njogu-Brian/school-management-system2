<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceBroughtForwardImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'term',
        'academic_year_id',
        'term_id',
        'balances_updated_count',
        'balances_deleted_count',
        'total_amount',
        'snapshot_before',
        'imported_by',
        'imported_at',
        'reversed_by',
        'reversed_at',
        'is_reversed',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'balances_updated_count' => 'integer',
        'balances_deleted_count' => 'integer',
        'snapshot_before' => 'array',
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

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
