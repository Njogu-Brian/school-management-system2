import { UserRole } from '@constants/roles';

/** Short label shown on dashboard hero for the signed-in user. */
export function getDashboardRoleLabel(role: string | undefined): string {
    if (!role) return 'Member';
    const r = role as UserRole;
    switch (r) {
        case UserRole.SUPER_ADMIN:
            return 'Super Admin';
        case UserRole.ADMIN:
            return 'Administrator';
        case UserRole.SECRETARY:
            return 'Secretary';
        case UserRole.TEACHER:
            return 'Teacher';
        case UserRole.SENIOR_TEACHER:
            return 'Senior Teacher';
        case UserRole.SUPERVISOR:
            return 'Supervisor';
        case UserRole.ACCOUNTANT:
        case UserRole.FINANCE:
            return 'Finance';
        case UserRole.PARENT:
        case UserRole.GUARDIAN:
            return 'Parent / Guardian';
        case UserRole.STUDENT:
            return 'Student';
        case UserRole.DRIVER:
            return 'Driver';
        case UserRole.TRANSPORT:
            return 'Transport';
        default:
            return 'Member';
    }
}
