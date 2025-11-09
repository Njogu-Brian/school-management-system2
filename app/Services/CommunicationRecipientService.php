<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

class CommunicationRecipientService
{
    public function resolveDetailed(array $criteria, string $channel): LazyCollection
    {
        $channel = strtolower($channel) === 'sms' ? 'sms' : 'email';
        $target  = $criteria['target'] ?? '';
        $custom  = $criteria['custom_emails'] ?? $criteria['custom_numbers'] ?? null;

        return LazyCollection::make(function () use ($criteria, $channel, $target, $custom) {
            $seen = [];

            $emit = function (array $payload) use (&$seen) {
                $contact = strtolower(trim((string) ($payload['contact'] ?? '')));
                if ($contact === '' || isset($seen[$contact])) {
                    return null;
                }

                $seen[$contact] = true;

                return $payload;
            };

            if ($custom) {
                foreach (array_filter(array_map('trim', explode(',', $custom))) as $value) {
                    if ($payload = $emit([
                        'contact'        => $value,
                        'entity'         => null,
                        'entity_id'      => null,
                        'recipient_type' => 'custom',
                        'extra'          => [],
                    ])) {
                        yield $payload;
                    }
                }
            }

            if ($target === 'student' && !empty($criteria['student_id'])) {
                $student = Student::with(['parent', 'classroom', 'stream'])->find($criteria['student_id']);
                if ($student) {
                    yield from $this->emitStudentContacts($student, $channel, $emit, includeParents: true, includeStudent: false);
                }
                return;
            }

            if ($target === 'class' && !empty($criteria['classroom_id'])) {
                $query = Student::with(['parent', 'classroom', 'stream'])
                    ->where('classroom_id', $criteria['classroom_id']);

                if (!empty($criteria['stream_id'])) {
                    $query->where('stream_id', $criteria['stream_id']);
                }

                yield from $this->emitStudentQuery($query, $channel, $emit, includeParents: true, includeStudent: false);
                return;
            }

            if ($target === 'parents') {
                $query = Student::with(['parent', 'classroom', 'stream']);
                yield from $this->emitStudentQuery($query, $channel, $emit, includeParents: true, includeStudent: false);
                return;
            }

            if ($target === 'students') {
                $query = Student::with(['parent', 'classroom', 'stream']);
                yield from $this->emitStudentQuery($query, $channel, $emit, includeParents: false, includeStudent: true);
                return;
            }

            if ($target === 'staff' || $target === 'teachers') {
                $staffQuery = Staff::with('user')
                    ->when($target === 'teachers', fn ($q) => $q->whereHas('user.roles', fn ($r) => $r->where('name', 'Teacher')));

                yield from $this->emitStaffQuery($staffQuery, $channel, $emit);
                return;
            }

            if ($target === 'custom') {
                // already handled via $custom above, nothing else to do
                return;
            }

            // default fallback: treat as parents
            $query = Student::with(['parent', 'classroom', 'stream']);
            yield from $this->emitStudentQuery($query, $channel, $emit);
        });
    }

    public function resolveMap(array $criteria, string $channel): array
    {
        return $this->resolveDetailed($criteria, $channel)
            ->mapWithKeys(fn ($entry) => [$entry['contact'] => $entry['entity']])
            ->toArray();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\Student> $query
     */
    protected function emitStudentQuery(
        Builder $query,
        string $channel,
        callable $emit,
        bool $includeParents = true,
        bool $includeStudent = true
    ): \Generator
    {
        foreach ($query->lazy() as $student) {
            yield from $this->emitStudentContacts($student, $channel, $emit, $includeParents, $includeStudent);
        }
    }

    protected function emitStudentContacts(
        Student $student,
        string $channel,
        callable $emit,
        bool $includeParents = true,
        bool $includeStudent = true
    ): \Generator
    {
        if ($includeStudent && $channel === 'email' && filled($student->email ?? null)) {
            if ($payload = $emit([
                'contact'        => $student->email,
                'entity'         => $student,
                'entity_id'      => $student->id,
                'recipient_type' => 'student',
                'extra'          => [],
            ])) {
                yield $payload;
            }
        }

        if ($includeStudent && $channel === 'sms' && filled($student->phone_number ?? null)) {
            if ($payload = $emit([
                'contact'        => $student->phone_number,
                'entity'         => $student,
                'entity_id'      => $student->id,
                'recipient_type' => 'student',
                'extra'          => [],
            ])) {
                yield $payload;
            }
        }

        if (! $includeParents || ! $student->parent) {
            return;
        }

        $parent = $student->parent;

        $parentContacts = $channel === 'email'
            ? [
                ['value' => $parent->father_email,   'label' => $parent->father_name],
                ['value' => $parent->mother_email,   'label' => $parent->mother_name],
                ['value' => $parent->guardian_email, 'label' => $parent->guardian_name],
            ]
            : [
                ['value' => $parent->father_phone,   'label' => $parent->father_name],
                ['value' => $parent->mother_phone,   'label' => $parent->mother_name],
                ['value' => $parent->guardian_phone, 'label' => $parent->guardian_name],
            ];

        foreach ($parentContacts as $entry) {
            if (! filled($entry['value'])) {
                continue;
            }

            if ($payload = $emit([
                'contact'        => $entry['value'],
                'entity'         => $student,
                'entity_id'      => $parent->id,
                'recipient_type' => 'parent',
                'extra'          => [
                    '{parent_name}' => $entry['label'] ?? '',
                ],
            ])) {
                yield $payload;
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<\App\Models\Staff> $query
     */
    protected function emitStaffQuery(Builder $query, string $channel, callable $emit): \Generator
    {
        foreach ($query->lazy() as $staff) {
            $contact = $channel === 'email'
                ? ($staff->work_email ?? $staff->personal_email ?? $staff->email ?? optional($staff->user)->email)
                : $staff->phone_number;

            if (! filled($contact)) {
                continue;
            }

            if ($payload = $emit([
                'contact'        => $contact,
                'entity'         => $staff,
                'entity_id'      => $staff->id,
                'recipient_type' => 'staff',
                'extra'          => [],
            ])) {
                yield $payload;
            }
        }
    }
}
