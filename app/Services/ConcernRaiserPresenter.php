<?php

namespace App\Services;

use App\Models\StudentConcern;
use App\Models\User;

/**
 * Resolve display label for who raised a concern (Mother / Father / staff name).
 */
class ConcernRaiserPresenter
{
    public function __construct(protected ParentCredentialsService $parents) {}

    /**
     * @return array{name: ?string, slot: ?string, label: string}
     */
    public function present(StudentConcern $concern): array
    {
        $user = $concern->relationLoaded('raisedBy')
            ? $concern->raisedBy
            : $concern->raisedBy()->first();

        if (! $user) {
            return [
                'name' => null,
                'slot' => null,
                'label' => 'Unknown',
            ];
        }

        $slot = $this->slotForUser($concern, $user);
        $name = trim((string) ($user->name ?? '')) ?: null;

        if ($slot === 'mother') {
            return ['name' => $name, 'slot' => 'mother', 'label' => $name ? "Mother · {$name}" : 'Mother'];
        }
        if ($slot === 'father') {
            return ['name' => $name, 'slot' => 'father', 'label' => $name ? "Father · {$name}" : 'Father'];
        }
        if ($slot === 'guardian') {
            return ['name' => $name, 'slot' => 'guardian', 'label' => $name ? "Guardian · {$name}" : 'Guardian'];
        }

        if ($user->staff || $user->hasElevatedStaffRole() || $user->hasTeacherLikeRole()) {
            return ['name' => $name, 'slot' => null, 'label' => $name ? "Staff · {$name}" : 'Staff'];
        }

        return [
            'name' => $name,
            'slot' => null,
            'label' => $name ?: 'Parent',
        ];
    }

    public function label(StudentConcern $concern): string
    {
        return $this->present($concern)['label'];
    }

    protected function slotForUser(StudentConcern $concern, User $user): ?string
    {
        $student = $concern->relationLoaded('student')
            ? $concern->student
            : $concern->student()->with('parent')->first();

        $parent = $student?->parent;
        if (! $parent && $student?->parent_id) {
            $parent = $student->parent()->first();
        }
        if (! $parent && $user->parent_id) {
            $parent = \App\Models\ParentInfo::find($user->parent_id);
        }
        if (! $parent) {
            return null;
        }

        $slot = $this->parents->slotForUser($parent, $user);

        return in_array($slot, ['mother', 'father', 'guardian'], true) ? $slot : null;
    }
}
