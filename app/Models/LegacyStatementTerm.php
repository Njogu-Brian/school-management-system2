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
}

