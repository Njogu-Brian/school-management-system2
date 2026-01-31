import { UserRole } from '@constants/roles';

/**
 * Normalize API role string to app UserRole value.
 * Backend may return "Teacher", "Senior Teacher", "Super Admin", etc.
 */
export function normalizeRole(role: unknown): string {
    if (role == null || typeof role !== 'string') {
        return UserRole.TEACHER; // safe default for role-based nav
    }
    const lower = role.trim().toLowerCase();
    const normalized: Record<string, string> = {
        'teacher': UserRole.TEACHER,
        'senior teacher': UserRole.SENIOR_TEACHER,
        'senior_teacher': UserRole.SENIOR_TEACHER,
        'supervisor': UserRole.SUPERVISOR,
        'super admin': UserRole.SUPER_ADMIN,
        'super_admin': UserRole.SUPER_ADMIN,
        'admin': UserRole.ADMIN,
        'secretary': UserRole.SECRETARY,
        'accountant': UserRole.ACCOUNTANT,
        'finance': UserRole.FINANCE,
        'parent': UserRole.PARENT,
        'guardian': UserRole.GUARDIAN,
        'student': UserRole.STUDENT,
        'driver': UserRole.DRIVER,
        'transport': UserRole.TRANSPORT,
    };
    return normalized[lower] ?? lower;
}

export function isTeacherRole(role: string): boolean {
    const r = normalizeRole(role);
    return r === UserRole.TEACHER || r === UserRole.SENIOR_TEACHER || r === UserRole.SUPERVISOR;
}

export function isSeniorTeacherRole(role: string): boolean {
    const r = normalizeRole(role);
    return r === UserRole.SENIOR_TEACHER || r === UserRole.SUPERVISOR;
}
