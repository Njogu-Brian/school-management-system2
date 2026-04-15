<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    use \App\Models\Concerns\NormalizesNameAttributes;

    protected static array $sentenceCaseNameAttributes = [
        'guardian_name',
        'father_name',
        'mother_name',
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'guardian_name',
        'father_name',
        'mother_name',
        'phone',
        'father_phone',
        'mother_phone',
        'email',
        'father_email',
        'mother_email',
    ];

    /**
     * Get all students belonging to this family
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function updateLink()
    {
        return $this->hasOne(FamilyUpdateLink::class);
    }

    public function receiptLink()
    {
        return $this->hasOne(FamilyReceiptLink::class);
    }
}
