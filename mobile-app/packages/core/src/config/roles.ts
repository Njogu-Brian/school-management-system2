/**
 * Canonical role identifiers (config only — no enforcement logic in this batch).
 *
 * The permission-first RBAC engine (build plan §7) resolves effective permissions from
 * the backend `/user` claims in a later batch. This file exists so the shell, the
 * app-mismatch guard, and future `computeMenu` share one source of role strings.
 */
export enum UserRole {
  SUPER_ADMIN = 'super_admin',
  ADMIN = 'admin',
  SECRETARY = 'secretary',
  ACADEMIC_ADMIN = 'academic_admin',
  TEACHER = 'teacher',
  SENIOR_TEACHER = 'senior_teacher',
  SUPERVISOR = 'supervisor',
  ACCOUNTANT = 'accountant',
  FINANCE = 'finance',
  PARENT = 'parent',
  GUARDIAN = 'guardian',
  STUDENT = 'student',
  DRIVER = 'driver',
  TRANSPORT = 'transport',
}

/**
 * Roles whose home is the Admin App. Used by the app-mismatch guard
 * (a users-only role logging in here is denied and pointed to the Users App — build plan §5.1).
 */
export const ADMIN_APP_ROLES: readonly UserRole[] = [
  UserRole.SUPER_ADMIN,
  UserRole.ADMIN,
  UserRole.SECRETARY,
  UserRole.ACADEMIC_ADMIN,
  UserRole.ACCOUNTANT,
  UserRole.FINANCE,
];

/** Roles whose home is the Users App (the symmetric guard, enforced by that binary). */
export const USERS_APP_ROLES: readonly UserRole[] = [
  UserRole.TEACHER,
  UserRole.SENIOR_TEACHER,
  UserRole.SUPERVISOR,
  UserRole.PARENT,
  UserRole.GUARDIAN,
  UserRole.STUDENT,
  UserRole.DRIVER,
  UserRole.TRANSPORT,
];

/** @deprecated Use USERS_APP_ROLES — kept for temporary import compatibility. */
export const STAFF_APP_ROLES = USERS_APP_ROLES;

export function isAdminAppRole(role: UserRole | null | undefined): boolean {
  return role != null && ADMIN_APP_ROLES.includes(role);
}

export function isUsersAppRole(role: UserRole | null | undefined): boolean {
  return role != null && USERS_APP_ROLES.includes(role);
}

/** @deprecated Use isUsersAppRole */
export function isStaffAppRole(role: UserRole | null | undefined): boolean {
  return isUsersAppRole(role);
}
