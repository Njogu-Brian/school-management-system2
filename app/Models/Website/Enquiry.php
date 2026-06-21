<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_ENROLLED = 'enrolled';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'parent_name',
        'phone',
        'email',
        'child_age',
        'grade_interest',
        'message',
        'status',
        'source',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_CONTACTED,
            self::STATUS_ENROLLED,
            self::STATUS_CLOSED,
        ];
    }
}
