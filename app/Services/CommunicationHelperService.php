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
            if ($student) {
                self::appendStudentContacts($student, $type, $out);
            }
        }

        // Entire class
        if ($target === 'class' && !empty($data['classroom_id'])) {
            Student::with('parent', 'classroom', 'stream')
                ->where('classroom_id', $data['classroom_id'])
                ->get()
                ->each(fn($s) => self::appendStudentContacts($s, $type, $out));
        }

        // All parents
        if ($target === 'parents') {
            Student::with('parent', 'classroom', 'stream')
                ->get()
                ->each(fn($s) => self::appendStudentContacts($s, $type, $out));
        }

        // All students
        if ($target === 'students') {
            Student::with('parent', 'classroom', 'stream')
                ->get()
                ->each(fn($s) => self::appendStudentContacts($s, $type, $out));
        }

        // All staff
        if ($target === 'staff') {
            Staff::with('user')->get()->each(function ($st) use (&$out, $type) {
                $contact = $type === 'email'
                    ? ($st->work_email ?? $st->personal_email ?? $st->email ?? optional($st->user)->email)
                    : $st->phone_number;

                if ($contact) {
                    $out[$contact] = $st;
                }
            });
        }

        return $out;
    }

    protected static function appendStudentContacts(Student $student, string $channel, array &$out): void
    {
        if ($channel === 'email') {
            if (filled($student->email ?? null)) {
                $out[$student->email] = $student;
            }

            if ($student->parent) {
                foreach (['father_email', 'mother_email', 'guardian_email'] as $field) {
                    $value = $student->parent->{$field} ?? null;
                    if (filled($value)) {
                        $out[$value] = $student;
                    }
                }
            }
        } else {
            if (filled($student->phone_number ?? null)) {
                $out[$student->phone_number] = $student;
            }

            if ($student->parent) {
                foreach (['father_phone', 'mother_phone', 'guardian_phone'] as $field) {
                    $value = $student->parent->{$field} ?? null;
                    if (filled($value)) {
                        $out[$value] = $student;
                    }
                }
            }
        }
    }
}
