import {
  ADMIN_APP_ROLES,
  USERS_APP_ROLES,
  UserRole,
  isAdminAppRole,
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
    'deputy senior teacher': UserRole.SENIOR_TEACHER,
    deputy_senior_teacher: UserRole.SENIOR_TEACHER,
    supervisor: UserRole.SUPERVISOR,
    'super admin': UserRole.SUPER_ADMIN,
    super_admin: UserRole.SUPER_ADMIN,
    superadmin: UserRole.SUPER_ADMIN,
    director: UserRole.DIRECTOR,
    'school director': UserRole.DIRECTOR,
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

/** Roles allowed into a given app binary. Combined (iOS) accepts every recognized role. */
export function rolesForApp(target: AppTarget): readonly UserRole[] {
  if (target === 'admin') return ADMIN_APP_ROLES;
  if (target === 'combined') return [...ADMIN_APP_ROLES, ...USERS_APP_ROLES];
  return USERS_APP_ROLES;
}

function hasLinkedParentProfile(user: User): boolean {
  return Boolean(user.parentId || user.canHomeMode);
}

/** Prefer normalized `role`; fall back to parsing `roleName` for older payloads. */
export function effectiveRole(user: User | null | undefined): UserRole | null {
  if (!user) {
    return null;
  }
  return user.role ?? normalizeRole(user.roleName);
}

/**
 * Whether a user may enter the given app. Returns false for unauthenticated users
 * and for recognized-but-wrong-app roles (→ Access Denied). The Admin App passes
 * `'admin'`; the Users App passes `'users'` using the same helper. The iOS combined
 * binary passes `'combined'` and accepts every recognized role.
 *
 * Directors may always enter the Users app (parent shell, or link-child screen).
 * Other admin roles may enter Users when linked to a parent profile.
 */
export function canAccessApp(user: User | null, target: AppTarget): boolean {
  const role = effectiveRole(user);
  if (!user || role == null) {
    return false;
  }
  if (target === 'combined') {
    return true;
  }
  if (rolesForApp(target).includes(role)) {
    return true;
  }
  if (target === 'users' && role === UserRole.DIRECTOR) {
    return true;
  }
  if (target === 'users' && isAdminAppRole(role) && hasLinkedParentProfile(user)) {
    return true;
  }
  return false;
}

/** Work shell is available for admin roles and any staff identity. */
export function userCanWork(user: User | null | undefined): boolean {
  if (!user) return false;
  if (user.canWorkMode) return true;
  if (user.staffId) return true;
  const role = effectiveRole(user);
  if (role == null) return false;
  if (isAdminAppRole(role)) return true;
  return (
    role === UserRole.TEACHER ||
    role === UserRole.SENIOR_TEACHER ||
    role === UserRole.SUPERVISOR ||
    role === UserRole.DRIVER ||
    role === UserRole.TRANSPORT
  );
}

/** Home (parent) shell is available when a parent profile is linked. */
export function userCanHome(user: User | null | undefined): boolean {
  if (!user) return false;
  if (user.canHomeMode) return true;
  if (user.parentId) return true;
  const role = effectiveRole(user);
  return role === UserRole.PARENT || role === UserRole.GUARDIAN;
}
