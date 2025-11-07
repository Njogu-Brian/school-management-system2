<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffProfileChange extends Model
{
    protected $fillable = [
        'staff_id','submitted_by','changes','status','reviewed_by','reviewed_at','review_notes'
    ];

    protected $casts = [
        'changes' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function staff() { return $this->belongsTo(Staff::class); }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
