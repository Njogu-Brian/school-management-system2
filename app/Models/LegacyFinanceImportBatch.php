<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyFinanceImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uploaded_by',
        'file_name',
        'class_label',
        'status',
        'total_students',
        'imported_students',
        'draft_students',
    ];

    public function terms(): HasMany
    {
        return $this->hasMany(LegacyStatementTerm::class, 'batch_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(LegacyStatementLine::class, 'batch_id');
    }
}

