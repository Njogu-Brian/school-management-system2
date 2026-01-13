<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Staff;

class CommunicationHelperService
{
    /**
     * Build a map of recipients => entity used for personalization.
     * $target: students|parents|staff|class|student|specific_students|custom
     * $data: ['target', 'classroom_id', 'student_id', 'selected_student_ids', 'custom_emails', 'custom_numbers']
     * $type: 'email', 'sms', or 'whatsapp'
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

        // Specific multiple students (exclude alumni and archived)
        if ($target === 'specific_students' && !empty($data['selected_student_ids'])) {
            $studentIds = is_array($data['selected_student_ids']) 
                ? $data['selected_student_ids'] 
                : array_filter(explode(',', $data['selected_student_ids']));
            
            if (!empty($studentIds)) {
                Student::with('parent', 'classroom')
                    ->whereIn('id', $studentIds)
                    ->where('archive', 0)
                    ->where('is_alumni', false)
                    ->get()
                    ->each(function ($s) use (&$out, $type) {
                        if ($s->parent) {
                            $contacts = match ($type) {
                                'email' => [$s->parent->father_email, $s->parent->mother_email, $s->parent->guardian_email],
                                'whatsapp' => [
                                    $s->parent->father_whatsapp ?? $s->parent->father_phone,
                                    $s->parent->mother_whatsapp ?? $s->parent->mother_phone,
                                    $s->parent->guardian_whatsapp ?? $s->parent->guardian_phone,
                                ],
                                default => [$s->parent->father_phone, $s->parent->mother_phone, $s->parent->guardian_phone],
                            };
                            foreach ($contacts as $c) if ($c) $out[$c] = $s;
                        }
                    });
            }
        }

        // Single student (exclude alumni and archived)
        if ($target === 'student' && !empty($data['student_id'])) {
            $student = Student::with('parent', 'classroom')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->find($data['student_id']);
            if ($student && $student->parent) {
                $contacts = match ($type) {
                    'email' => [$student->parent->father_email, $student->parent->mother_email, $student->parent->guardian_email],
                    'whatsapp' => [
                        $student->parent->father_whatsapp ?: $student->parent->father_phone,
                        $student->parent->mother_whatsapp ?: $student->parent->mother_phone,
                        $student->parent->guardian_whatsapp ?: $student->parent->guardian_phone,
                    ],
                    default => [$student->parent->father_phone, $student->parent->mother_phone, $student->parent->guardian_phone],
                };
                foreach ($contacts as $c) if ($c) $out[$c] = $student;
            }
        }

        // Entire class (exclude alumni and archived)
        if ($target === 'class' && !empty($data['classroom_id'])) {
            Student::with('parent')
                ->where('classroom_id', $data['classroom_id'])
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->get()
                ->each(function ($s) use (&$out, $type) {
                    if ($s->parent) {
                        $contacts = match ($type) {
                            'email' => [$s->parent->father_email, $s->parent->mother_email, $s->parent->guardian_email],
                            'whatsapp' => [
                                $s->parent->father_whatsapp ?? $s->parent->father_phone,
                                $s->parent->mother_whatsapp ?? $s->parent->mother_phone,
                                $s->parent->guardian_whatsapp ?? $s->parent->guardian_phone,
                            ],
                            default => [$s->parent->father_phone, $s->parent->mother_phone, $s->parent->guardian_phone],
                        };
                        foreach ($contacts as $c) if ($c) $out[$c] = $s;
                    }
                });
        }

        // All parents (exclude alumni and archived)
        if ($target === 'parents') {
            Student::with('parent')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->get()
                ->each(function ($s) use (&$out, $type) {
                    if ($s->parent) {
                        $contacts = match ($type) {
                            'email' => [$s->parent->father_email, $s->parent->mother_email, $s->parent->guardian_email],
                            'whatsapp' => [
                                $s->parent->father_whatsapp ?? $s->parent->father_phone,
                                $s->parent->mother_whatsapp ?? $s->parent->mother_phone,
                                $s->parent->guardian_whatsapp ?? $s->parent->guardian_phone,
                            ],
                            default => [$s->parent->father_phone, $s->parent->mother_phone, $s->parent->guardian_phone],
                        };
                        foreach ($contacts as $c) if ($c) $out[$c] = $s;
                    }
                });
        }

        // All students
        if ($target === 'students') {
            Student::all()->each(function ($s) use (&$out, $type) {
                $contact = match ($type) {
                    'email' => $s->email,
                    'whatsapp' => $s->phone_number, // fallback to primary phone for WhatsApp
                    default => $s->phone_number,
                };
                if ($contact) $out[$contact] = $s;
            });
        }

        // All staff
        if ($target === 'staff') {
            Staff::all()->each(function ($st) use (&$out, $type) {
                $contact = match ($type) {
                    'email' => $st->email,
                    'whatsapp' => $st->phone_number,
                    default => $st->phone_number,
                };
                if ($contact) $out[$contact] = $st;
            });
        }

        return $out;
    }
}
