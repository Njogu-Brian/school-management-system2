import type { PermissionInput } from './permissionModel';
import type { CanOptions } from './permissionModel';
import { useRbac } from './RbacContext';
import type { RolePreset } from './rolePresets';
import { UserRole } from '../config/roles';

/** Reactive permission check for conditional UI. */
export function useCan(permission: PermissionInput, options?: CanOptions): boolean {
  const { can: canFn } = useRbac();
  return canFn(permission, options);
}

/** Current organizational role preset (from role + display name). */
export function useRole(): RolePreset | null {
  return useRbac().rolePreset;
}

/** Effective permission set for the signed-in user. */
export function usePermissions(): ReadonlySet<string> {
  return useRbac().permissions;
}

/** Whether the user has a specific role preset or UserRole slug. */
export function useHasRole(role: RolePreset | UserRole | string): boolean {
  return useRbac().hasRole(role);
}
