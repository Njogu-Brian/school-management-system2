<?php

namespace App\Services\Hr;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StaffAttendanceAccess
{
    public static function canManageAttendance(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        return $user?->hasAnyRole(['Super Admin', 'Admin', 'Secretary']) ?? false;
    }

    public static function canViewTeamAttendance(?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return false;
        }

        if (self::canManageAttendance($user)) {
            return true;
        }

        if ($user->isSeniorTeacherUser() || $user->isDeputySeniorTeacherUser()) {
            return true;
        }

        return is_supervisor() && ! empty(get_subordinate_staff_ids());
    }

    /**
     * Staff IDs the user may view. Null = all active staff.
     *
     * @return list<int>|null
     */
    public static function allowedStaffIds(?User $user = null): ?array
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return [];
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            return null;
        }

        if ($user->isSeniorTeacherUser()) {
            return null;
        }

        if ($user->isDeputySeniorTeacherUser()) {
            $ids = $user->getSupervisedStaffIds();

            return empty($ids) ? [] : $ids;
        }

        if (is_supervisor()) {
            $ids = get_subordinate_staff_ids();

            return empty($ids) ? [] : $ids;
        }

        return [];
    }

    public static function applyStaffScope(Builder $query, string $column = 'staff_id'): Builder
    {
        $allowed = self::allowedStaffIds();
        if ($allowed === null) {
            return $query;
        }
        if ($allowed === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $allowed);
    }

    public static function staffDropdownQuery(): Builder
    {
        $query = Staff::query()->where('status', 'active')->orderBy('first_name');
        $allowed = self::allowedStaffIds();
        if ($allowed !== null) {
            $query->whereIn('id', $allowed);
        }

        return $query;
    }

    public static function abortUnlessCanViewTeam(): void
    {
        if (! self::canViewTeamAttendance()) {
            abort(403, 'You are not allowed to view staff attendance.');
        }
    }
}
