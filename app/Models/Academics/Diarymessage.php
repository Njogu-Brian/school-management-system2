<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;  // ✅ Correct import
use App\Models\Academics\Diary;
use App\Models\Academics\DiaryReadReceipt;

class DiaryMessage extends Model
{
    protected $fillable = ['diary_id','user_id','message_type','body','attachment_path'];

    public function diary(){ return $this->belongsTo(Diary::class); }
    public function sender(){ return $this->belongsTo(User::class,'user_id'); } // ✅ Fix
    public function receipts(){ return $this->hasMany(DiaryReadReceipt::class,'message_id'); }
}
