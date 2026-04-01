<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Student;

/**
 * Replaces {{placeholders}} in invoice header/footer HTML with student/invoice-specific values.
 */
class InvoiceFooterPlaceholderService
{
    /** @var array<string, mixed> */
    private static array $settingCache = [];

    private static function setting(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, self::$settingCache)) {
            self::$settingCache[$key] = Setting::get($key, $default);
        }

        return self::$settingCache[$key];
    }

    /**
     * @param  string  $content  Raw HTML/text from settings
     */
    public static function replace(string $content, Invoice $invoice, ?Student $student = null): string
    {
        if ($content === '') {
            return '';
        }

        $student = $student ?? $invoice->student;
        $invoice->loadMissing(['student.classroom', 'student.stream', 'student.family', 'term', 'academicYear']);

        $family = $student?->family;
        $parentPhone = trim((string) ($family->phone ?? $family->father_phone ?? $family->mother_phone ?? ''));
        $parentEmail = trim((string) ($family->email ?? $family->father_email ?? $family->mother_email ?? ''));

        $fullName = $student?->full_name ?? '';

        $map = [
            'name' => $fullName,
            'student_name' => $fullName,
            'student_full_name' => $fullName,
            'first_name' => $student?->first_name ?? '',
            'last_name' => $student?->last_name ?? '',
            'admission_number' => $student?->admission_number ?? '',
            'class' => optional($student?->classroom)->name ?? '',
            'class_name' => optional($student?->classroom)->name ?? '',
            'stream' => optional($student?->stream)->name ?? '',
            'stream_name' => optional($student?->stream)->name ?? '',
            'class_stream' => trim(
                (optional($student?->classroom)->name ?? '') .
                ((optional($student?->classroom)->name && optional($student?->stream)->name) ? ' / ' : '') .
                (optional($student?->stream)->name ?? '')
            ),
            'guardian_name' => $family->guardian_name ?? '',
            'parent_name' => $family->guardian_name ?? '',
            'parent_phone' => $parentPhone,
            'parent_contact' => $parentPhone,
            'parent_email' => $parentEmail,
            'invoice_number' => $invoice->invoice_number ?? '',
            'year' => (string) ($invoice->year ?? ''),
            'term' => (string) ($invoice->term ?? ''),
            'term_name' => optional($invoice->term)->name ?? '',
            'school_name' => self::setting('school_name', ''),
            'school_phone' => self::setting('school_phone', ''),
            'school_email' => self::setting('school_email', ''),
            'school_address' => self::setting('school_address', ''),
        ];

        $out = $content;
        foreach ($map as $key => $value) {
            $escaped = e($value);
            $pattern = '/\{\{\s*' . preg_quote($key, '/') . '\s*\}}/iu';
            $out = preg_replace($pattern, $escaped, $out);
        }

        return $out;
    }
}
