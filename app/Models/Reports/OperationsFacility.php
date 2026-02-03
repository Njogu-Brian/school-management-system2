<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OperationsFacility extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_ending',
        'campus',
        'area',
        'status',
        'issue_noted',
        'action_needed',
        'responsible_person',
        'resolved',
        'notes',
    ];

    protected $casts = [
        'week_ending' => 'date',
        'resolved' => 'boolean',
    ];
}
