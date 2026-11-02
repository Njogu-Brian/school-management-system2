<?php

namespace App\Models\Admissions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionDocument extends Model
{
    public const TYPE_BIRTH_CERTIFICATE = 'birth_certificate';

    public const TYPE_REPORT_FORM = 'report_form';

    public const TYPE_PASSPORT_PHOTO = 'passport_photo';

    public const TYPE_TRANSFER_LETTER = 'transfer_letter';

    public const TYPE_PARENT_ID = 'parent_id';

    public const TYPE_SIGNED_ADMISSION_FORM = 'signed_admission_form';

    protected $fillable = [
        'application_id',
        'online_admission_id',
        'document_type',
        'file_path',
        'verified',
    ];

    protected $casts = [
        'verified' => 'boolean',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_BIRTH_CERTIFICATE,
            self::TYPE_REPORT_FORM,
            self::TYPE_PASSPORT_PHOTO,
            self::TYPE_TRANSFER_LETTER,
            self::TYPE_PARENT_ID,
            self::TYPE_SIGNED_ADMISSION_FORM,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class, 'application_id');
    }

    public function url(): string
    {
        return asset('admissions/'.$this->file_path);
    }
}
