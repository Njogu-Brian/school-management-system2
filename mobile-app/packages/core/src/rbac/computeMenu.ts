import type { AdminAreaKey, AdminNavArea } from '../config/navigation';
import { AREA_VIEW_PERMISSIONS } from './permissions';
import { can, hasFullAccess, type PermissionInput } from './permissionModel';

/**
 * Returns true when the user may see a nav area (holds ≥1 area permission).
 */
export function canAccessArea(
  permissionSet: Set<string>,
  areaKey: AdminAreaKey,
): boolean {
  const required = AREA_VIEW_PERMISSIONS[areaKey];
  return can(permissionSet, required);
}

/**
 * Filter the Admin nav tree by effective permissions (build plan §7.3).
 */
export function computeMenu(
  areas: readonly AdminNavArea[],
  permissionSet: Set<string>,
): AdminNavArea[] {
  if (hasFullAccess(permissionSet)) {
    return [...areas];
  }
  return areas.filter((area) => canAccessArea(permissionSet, area.key));
}

/** Tab bar areas = permission-filtered subset with `inTabs: true`. */
export function computeTabAreas(
  areas: readonly AdminNavArea[],
  permissionSet: Set<string>,
): AdminNavArea[] {
  return computeMenu(areas, permissionSet).filter((a) => a.inTabs);
}

/** Check arbitrary permission(s) against a set (used by route guards). */
export function canWithSet(
  permissionSet: Set<string>,
  permission: PermissionInput,
  requireAll?: boolean,
): boolean {
  return can(permissionSet, permission, { requireAll });
}
