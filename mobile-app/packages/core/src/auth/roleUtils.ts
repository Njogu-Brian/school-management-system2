import {
  ADMIN_APP_ROLES,
  STAFF_APP_ROLES,
  UserRole,
} from '../config/roles';
import type { AppTarget, User } from '../types';

/**
 * Normalize a raw backend role string to a canonical `UserRole`.
 *
 * Explicit-deny policy (build plan §5.1): an unrecognized role returns `null`
 * rather than defaulting to a real role. A `null` role is treated as "no access"
 * by the guards, so a misconfigured account can never silently inherit privileges.
 */
export function normalizeRole(role: unknown): UserRole | null {
  if (typeof role !== 'string') {
    return null;
  }
  const key = role.trim().toLowerCase();
  const map: Record<string, UserRole> = {
    teacher: UserRole.TEACHER,
    'senior teacher': UserRole.SENIOR_TEACHER,
    senior_teacher: UserRole.SENIOR_TEACHER,
    supervisor: UserRole.SUPERVISOR,
    'super admin': UserRole.SUPER_ADMIN,
    super_admin: UserRole.SUPER_ADMIN,
    superadmin: UserRole.SUPER_ADMIN,
    admin: UserRole.ADMIN,
    administrator: UserRole.ADMIN,
    'academic administrator': UserRole.ACADEMIC_ADMIN,
    'academic admin': UserRole.ACADEMIC_ADMIN,
    academic_admin: UserRole.ACADEMIC_ADMIN,
    secretary: UserRole.SECRETARY,
    accountant: UserRole.ACCOUNTANT,
    finance: UserRole.FINANCE,
    'finance officer': UserRole.FINANCE,
    parent: UserRole.PARENT,
    guardian: UserRole.GUARDIAN,
    student: UserRole.STUDENT,
    driver: UserRole.DRIVER,
    transport: UserRole.TRANSPORT,
  };
  return map[key] ?? null;
}

/** Roles allowed into a given app binary. */
export function rolesForApp(target: AppTarget): readonly UserRole[] {
  return target === 'admin' ? ADMIN_APP_ROLES : STAFF_APP_ROLES;
}

/**
 * Whether a user may enter the given app. Returns false for unauthenticated users
 * and for recognized-but-wrong-app roles (→ Access Denied). The Admin App passes
 * `'admin'`; the Staff App will pass `'staff'` using the same helper.
 */
export function canAccessApp(user: User | null, target: AppTarget): boolean {
  if (!user || user.role == null) {
    return false;
  }
  return rolesForApp(target).includes(user.role);
}
