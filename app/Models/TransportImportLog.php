<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'imported_by',
        'total_rows',
        'success_count',
        'updated_count',
        'skipped_count',
        'error_count',
        'conflict_count',
        'errors',
        'conflicts',
        'status'
    ];

    protected $casts = [
        'errors' => 'array',
        'conflicts' => 'array',
    ];

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}

