<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NavAccess
{
    public static function user(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public static function isElevated(?User $user = null): bool
    {
        $user ??= self::user();

        return $user
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Super Admin', 'Director', 'System Admin']);
    }

    public static function can(string $section): bool
    {
        $user = self::user();
        if (! $user) {
            return false;
        }

        if (self::isElevated($user)) {
            return true;
        }

        $allowed = config("nav_access.sections.{$section}", []);

        return ! empty($allowed) && $user->hasAnyRole($allowed);
    }

    public static function canDashboard(string $routeName): bool
    {
        $user = self::user();
        if (! $user) {
            return false;
        }

        if (self::isElevated($user)) {
            return true;
        }

        $allowed = config("nav_access.dashboards.{$routeName}", []);

        return ! empty($allowed) && $user->hasAnyRole($allowed);
    }

    /**
     * Which sidebar partial to render for the current user.
     */
    public static function resolvePartial(): string
    {
        $user = self::user();
        if (! $user) {
            return 'layouts.partials.nav-admin';
        }

        $onTeacherRoute = request()->routeIs('teacher.*') || request()->is('teacher/*');
        $onSeniorTeacherRoute = request()->routeIs('senior_teacher.*') || request()->is('senior-teacher/*');

        $isTeacher = $user->hasAnyRole(['Teacher', 'teacher'])
            || $user->roles->pluck('name')->map(fn ($n) => strtolower($n))->contains('teacher');

        $isSeniorTeacher = $user->hasAnyRole(['Senior Teacher', 'Deputy Senior Teacher'])
            || $user->roles->pluck('name')->map(fn ($n) => strtolower($n))->contains('senior teacher')
            || $user->roles->pluck('name')->map(fn ($n) => strtolower($n))->contains('deputy senior teacher');

        if (($onSeniorTeacherRoute || $isSeniorTeacher) && $isSeniorTeacher) {
            return 'layouts.partials.nav-senior-teacher';
        }

        if (($onTeacherRoute || $isTeacher) && $isTeacher && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            return 'layouts.partials.nav-teacher';
        }

        if ($user->hasAnyRole(['Supervisor']) && ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Teacher', 'teacher'])) {
            if (is_supervisor()) {
                return 'layouts.partials.nav-teacher';
            }
        }

        return 'layouts.partials.nav-admin';
    }

    /**
     * @return array<int, array{route: string, label: string, icon: string, path: string}>
     */
    public static function dashboardLinks(): array
    {
        $links = [
            ['route' => 'admin.dashboard', 'label' => 'Admin Dashboard', 'icon' => 'bi-speedometer2', 'path' => 'admin/home'],
            ['route' => 'teacher.dashboard', 'label' => 'Teacher Dashboard', 'icon' => 'bi-easel2', 'path' => 'teacher/home'],
            ['route' => 'senior_teacher.dashboard', 'label' => 'Senior Teacher Dashboard', 'icon' => 'bi-mortarboard', 'path' => 'senior-teacher/home'],
            ['route' => 'student.dashboard', 'label' => 'Student Dashboard', 'icon' => 'bi-person-badge', 'path' => 'student/home'],
            ['route' => 'parent.dashboard', 'label' => 'Parent Dashboard', 'icon' => 'bi-people', 'path' => 'parent/home'],
            ['route' => 'finance.dashboard', 'label' => 'Finance Dashboard', 'icon' => 'bi-cash-stack', 'path' => 'finance/home'],
            ['route' => 'transport.dashboard', 'label' => 'Transport Dashboard', 'icon' => 'bi-truck', 'path' => 'transport/home'],
            ['route' => 'supervisor.dashboard', 'label' => 'Supervisor Dashboard', 'icon' => 'bi-eye', 'path' => 'supervisor/home'],
        ];

        return array_values(array_filter($links, function (array $link) {
            return \Illuminate\Support\Facades\Route::has($link['route'])
                && self::canDashboard($link['route']);
        }));
    }

    public static function moduleLabel(string $module): string
    {
        return config("nav_access.module_labels.{$module}") ?? ucwords(str_replace(['_', '.'], ' ', $module));
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Spatie\Permission\Models\Role>
     */
    public static function orderedRoles($roles)
    {
        $order = config('nav_access.role_order', []);
        $rank = array_flip($order);

        return collect($roles)->sortBy(function ($role) use ($rank) {
            return $rank[$role->name] ?? 999;
        })->values();
    }
}
