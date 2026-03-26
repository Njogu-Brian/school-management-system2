import { UserRole } from '@constants/roles';
import type { User } from '@types/auth.types';

/** Matches web `staff.*` middleware: Super Admin | Admin | Secretary */
export function canManageStaff(user: User | null): boolean {
    if (!user) return false;
    return [UserRole.SUPER_ADMIN, UserRole.ADMIN, UserRole.SECRETARY].includes(user.role);
}

/** Payroll listing: aligned with API (includes finance + senior teacher). */
export function canViewPayrollRecords(user: User | null): boolean {
    if (!user) return false;
    return [
        UserRole.SUPER_ADMIN,
        UserRole.ADMIN,
        UserRole.SECRETARY,
        UserRole.SENIOR_TEACHER,
        UserRole.FINANCE,
        UserRole.ACCOUNTANT,
    ].includes(user.role);
}

/** Leave list + apply: staff-linked users, or roles that manage HR / supervise. */
export function canAccessLeaveManagement(user: User | null): boolean {
    if (!user) return false;
    if (
        [
            UserRole.SUPER_ADMIN,
            UserRole.ADMIN,
            UserRole.SECRETARY,
            UserRole.SENIOR_TEACHER,
            UserRole.SUPERVISOR,
        ].includes(user.role)
    ) {
        return true;
    }
    return user.staff_id != null && user.staff_id > 0;
}

/** Matches API: Admin/Secretary/Super Admin, senior teacher, or supervisor role. */
export function canApproveLeaveRequests(user: User | null): boolean {
    if (!user) return false;
    return [
        UserRole.SUPER_ADMIN,
        UserRole.ADMIN,
        UserRole.SECRETARY,
        UserRole.SENIOR_TEACHER,
        UserRole.SUPERVISOR,
    ].includes(user.role);
}
