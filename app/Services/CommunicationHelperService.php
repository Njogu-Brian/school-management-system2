<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Staff;
use App\Models\Invoice;

class CommunicationHelperService
{
    /**
     * Build a map of recipients => entity used for personalization.
     * $target: students|parents|staff|class|student|specific_students|custom
     * $data: ['target', 'classroom_id', 'student_id', 'selected_student_ids', 'custom_emails', 'custom_numbers', 'fee_balance_only', 'exclude_student_ids']
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
                            // Never include guardian when selecting parents/students; guardians are reached via manual number entry only
                            $contacts = match ($type) {
                                'email' => [$s->parent->father_email, $s->parent->mother_email],
                                'whatsapp' => [
                                    !empty($s->parent->father_whatsapp) ? $s->parent->father_whatsapp : $s->parent->father_phone,
                                    !empty($s->parent->mother_whatsapp) ? $s->parent->mother_whatsapp : $s->parent->mother_phone,
                                ],
                                default => [$s->parent->father_phone, $s->parent->mother_phone],
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
                // Never include guardian when selecting parents/students; guardians are reached via manual number entry only
                $contacts = match ($type) {
                    'email' => [$student->parent->father_email, $student->parent->mother_email],
                    'whatsapp' => [
                        !empty($student->parent->father_whatsapp) ? $student->parent->father_whatsapp : $student->parent->father_phone,
                        !empty($student->parent->mother_whatsapp) ? $student->parent->mother_whatsapp : $student->parent->mother_phone,
                    ],
                    default => [$student->parent->father_phone, $student->parent->mother_phone],
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
                        // Never include guardian when selecting parents/students; guardians are reached via manual number entry only
                        $contacts = match ($type) {
                            'email' => [$s->parent->father_email, $s->parent->mother_email],
                            'whatsapp' => [
                                !empty($s->parent->father_whatsapp) ? $s->parent->father_whatsapp : $s->parent->father_phone,
                                !empty($s->parent->mother_whatsapp) ? $s->parent->mother_whatsapp : $s->parent->mother_phone,
                            ],
                            default => [$s->parent->father_phone, $s->parent->mother_phone],
                        };
                        foreach ($contacts as $c) if ($c) $out[$c] = $s;
                    }
                });
        }

        // All students (via parent contacts) – every non-archived, non-alumni student; send to parent when available
        if ($target === 'parents') {
            Student::with('parent')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->get()
                ->each(function ($s) use (&$out, $type) {
                    if ($s->parent) {
                        // Never include guardian when selecting parents/students; guardians are reached via manual number entry only
                        $contacts = match ($type) {
                            'email' => [$s->parent->father_email, $s->parent->mother_email],
                            'whatsapp' => [
                                !empty($s->parent->father_whatsapp) ? $s->parent->father_whatsapp : $s->parent->father_phone,
                                !empty($s->parent->mother_whatsapp) ? $s->parent->mother_whatsapp : $s->parent->mother_phone,
                            ],
                            default => [$s->parent->father_phone, $s->parent->mother_phone],
                        };
                        foreach ($contacts as $c) if ($c) $out[$c] = $s;
                    }
                });
        }

        // All students (via student contact – email/phone)
        if ($target === 'students') {
            Student::where('archive', 0)
                ->where('is_alumni', false)
                ->get()
                ->each(function ($s) use (&$out, $type) {
                    $contact = match ($type) {
                        'email' => $s->email,
                        'whatsapp' => $s->phone_number,
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

        // Exclude specific students (applies to any target that yields student/parent recipients)
        $excludeIds = [];
        if (!empty($data['exclude_student_ids'])) {
            $excludeIds = is_array($data['exclude_student_ids'])
                ? array_map('intval', $data['exclude_student_ids'])
                : array_filter(array_map('intval', explode(',', (string) $data['exclude_student_ids'])));
        }
        if (!empty($excludeIds)) {
            $out = array_filter($out, function ($entity) use ($excludeIds) {
                if (!$entity instanceof Student) {
                    return true;
                }
                return !in_array((int) $entity->id, $excludeIds, true);
            });
        }

        // Only recipients with fee balance (students/parents who have at least one invoice with balance > 0)
        if (!empty($data['fee_balance_only'])) {
            $studentIdsWithBalance = Invoice::where('balance', '>', 0)
                ->distinct()
                ->pluck('student_id')
                ->flip()
                ->all();
            $out = array_filter($out, function ($entity) use ($studentIdsWithBalance) {
                if (!$entity instanceof Student) {
                    return true; // keep non-student entries (e.g. custom emails)
                }
                return isset($studentIdsWithBalance[$entity->id]);
            });
        }

        // Exclude students in staff category (children whose student category name is "Staff")
        if (!empty($data['exclude_staff'])) {
            $out = array_filter($out, function ($entity) {
                if (!$entity instanceof Student) {
                    return true;
                }
                $entity->loadMissing('category');
                return !$entity->category || strtolower($entity->category->name) !== 'staff';
            });
        }

        return $out;
    }
}
