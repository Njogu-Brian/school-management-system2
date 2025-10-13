<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;  // ✅ Correct import
use App\Models\Academics\DiaryMessage;

class DiaryReadReceipt extends Model
{
    public $timestamps = false;

    protected $fillable = ['message_id','user_id','read_at'];
    protected $casts = ['read_at'=>'datetime'];

    public function message(){ return $this->belongsTo(DiaryMessage::class); }
    public function user(){ return $this->belongsTo(User::class); } // ✅ Fix
}
