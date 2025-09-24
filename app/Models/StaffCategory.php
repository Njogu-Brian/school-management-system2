<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffCategory extends Model
{
    use HasFactory;

    protected $table = 'staff_categories';

    protected $fillable = ['name'];

    public function staff()
    {
        return $this->hasMany(Staff::class, 'staff_category_id');
    }
}
