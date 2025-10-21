<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Staff;

class CommunicationHelperService
{
    /**
     * Build a map of recipients => entity used for personalization.
     * $target: students|parents|staff|class|student|custom
     * $data: ['target', 'classroom_id', 'student_id', 'custom_emails', 'custom_numbers']
     * $type: 'email' or 'sms'
     */
    public static function collectRecipients(array $data, string $type): array
    {
        $out = [];
        $target = $data['target'];
        $custom = $data['custom_emails'] ?? $data['custom_numbers'] ?? null;

        // Custom manual entries
        if ($custom) {
            foreach (array_map('trim', explode(',', $custom)) as $item) {
                if ($item !== '') $out[$item] = null;
            }
        }

        // Single student
        if ($target === 'student' && !empty($data['student_id'])) {
            $student = Student::with('parent', 'classroom')->find($data['student_id']);
            if ($student && $student->parent) {
                $contacts = $type === 'email'
                    ? [$student->parent->father_email, $student->parent->mother_email, $student->parent->guardian_email]
                    : [$student->parent->father_phone, $student->parent->mother_phone, $student->parent->guardian_phone];
                foreach ($contacts as $c) if ($c) $out[$c] = $student;
            }
        }

        // Entire class
        if ($target === 'class' && !empty($data['classroom_id'])) {
            Student::with('parent')->where('classroom_id', $data['classroom_id'])->get()
                ->each(function ($s) use (&$out, $type) {
                    if ($s->parent) {
                        $contacts = $type === 'email'
                            ? [$s->parent->father_email, $s->parent->mother_email, $s->parent->guardian_email]
                            : [$s->parent->father_phone, $s->parent->mother_phone, $s->parent->guardian_phone];
                        foreach ($contacts as $c) if ($c) $out[$c] = $s;
                    }
                });
        }

        // All parents
        if ($target === 'parents') {
            Student::with('parent')->get()->each(function ($s) use (&$out, $type) {
                if ($s->parent) {
                    $contacts = $type === 'email'
                        ? [$s->parent->father_email, $s->parent->mother_email, $s->parent->guardian_email]
                        : [$s->parent->father_phone, $s->parent->mother_phone, $s->parent->guardian_phone];
                    foreach ($contacts as $c) if ($c) $out[$c] = $s;
                }
            });
        }

        // All students
        if ($target === 'students') {
            Student::all()->each(function ($s) use (&$out, $type) {
                $contact = $type === 'email' ? $s->email : $s->phone_number;
                if ($contact) $out[$contact] = $s;
            });
        }

        // All staff
        if ($target === 'staff') {
            Staff::all()->each(function ($st) use (&$out, $type) {
                $contact = $type === 'email' ? $st->email : $st->phone_number;
                if ($contact) $out[$contact] = $st;
            });
        }

        return $out;
    }
}
