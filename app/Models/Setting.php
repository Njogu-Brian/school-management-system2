<?php



// app/Models/Setting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    // EITHER allow everything:
    protected $guarded = [];      // simplest

    // OR be explicit:
    // protected $fillable = ['key', 'value']; // add 'category' only if the column exists

    public $timestamps = true;    // your table has created_at/updated_at
}

