import type { User } from '../types';
import { UserRole } from '../config/roles';
import { expandPermissionAliases, PERMISSION_ALIASES } from './permissionAliases';
import { ALL_ADMIN_PERMISSIONS, AdminPermission } from './permissions';
import { ROLE_PRESET_PERMISSIONS, RolePreset } from './rolePresets';
import { presetMatchesRole, resolveRolePreset } from './roleModel';

export type PermissionInput = string | readonly string[];

export interface CanOptions {
  /** When true, user must hold every permission in a list (default: any). */
  requireAll?: boolean;
}

function normalizePermissionSet(permissions: Iterable<string>): Set<string> {
  const set = new Set<string>();
  for (const p of permissions) {
    const key = p.trim().toLowerCase();
    if (key) {
      set.add(key);
    }
  }
  return set;
}

function expandWildcard(set: Set<string>): Set<string> {
  if (set.has(AdminPermission.ALL)) {
    return expandPermissionAliases(
      normalizePermissionSet([...ALL_ADMIN_PERMISSIONS, AdminPermission.ALL]),
    );
  }
  return expandPermissionAliases(set);
}

/**
 * Resolve effective permissions: server claims when present, else preset fallback (§7.6).
 */
export function resolveEffectivePermissions(user: User | null): Set<string> {
  if (!user) {
    return new Set();
  }

  const server = user.permissions ?? [];
  if (server.length > 0) {
    return expandWildcard(normalizePermissionSet(server));
  }

  const preset = resolveRolePreset(user.role, user.roleName);
  if (!preset) {
    return new Set();
  }

  const fallback = ROLE_PRESET_PERMISSIONS[preset] ?? [];
  return expandWildcard(normalizePermissionSet(fallback));
}

export function hasFullAccess(permissionSet: Set<string>): boolean {
  return permissionSet.has(AdminPermission.ALL);
}

/** Imperative permission check (supports one or many permissions). */
export function can(
  permissionSet: Set<string>,
  permission: PermissionInput,
  options?: CanOptions,
): boolean {
  if (hasFullAccess(permissionSet)) {
    return true;
  }

  const required = Array.isArray(permission) ? permission : [permission];
  if (required.length === 0) {
    return true;
  }

  const normalized = required.map((p) => p.trim().toLowerCase());
  if (options?.requireAll) {
    return normalized.every((p) => permissionSet.has(p) || hasAliasGrant(permissionSet, p));
  }
  return normalized.some((p) => permissionSet.has(p) || hasAliasGrant(permissionSet, p));
}

/** True when a required mobile key is granted via a Laravel source permission. */
function hasAliasGrant(set: Set<string>, required: string): boolean {
  for (const [source, aliases] of Object.entries(PERMISSION_ALIASES)) {
    if (set.has(source) && aliases.includes(required)) {
      return true;
    }
  }
  return false;
}

export function hasRole(
  user: User | null,
  role: RolePreset | UserRole | string,
): boolean {
  if (!user) {
    return false;
  }
  const preset = resolveRolePreset(user.role, user.roleName);
  if (preset && presetMatchesRole(preset, role)) {
    return true;
  }
  if (user.role && String(user.role).toLowerCase() === String(role).toLowerCase()) {
    return true;
  }
  if (user.roleName && user.roleName.toLowerCase() === String(role).toLowerCase()) {
    return true;
  }
  return false;
}

export function hasAnyRole(
  user: User | null,
  ...roles: Array<RolePreset | UserRole | string>
): boolean {
  return roles.some((r) => hasRole(user, r));
}

export function hasAllRoles(
  user: User | null,
  ...roles: Array<RolePreset | UserRole | string>
): boolean {
  return roles.length > 0 && roles.every((r) => hasRole(user, r));
}

/** Imperative check for a user without React (e.g. handlers, tests). */
export function canForUser(
  user: User | null,
  permission: PermissionInput,
  options?: CanOptions,
): boolean {
  return can(resolveEffectivePermissions(user), permission, options);
}
