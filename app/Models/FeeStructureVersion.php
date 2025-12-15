<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\{FeeStructure, User};

class FeeStructureVersion extends Model
{
    protected $fillable = [
        'fee_structure_id',
        'version_number',
        'structure_snapshot',
        'created_by',
        'change_notes',
    ];

    protected $casts = [
        'structure_snapshot' => 'array',
        'version_number' => 'integer',
    ];

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

