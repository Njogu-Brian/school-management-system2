import React, {
  createContext,
  useCallback,
  useContext,
  useMemo,
} from 'react';
import type { AdminAreaKey, AdminNavArea } from '../config/navigation';
import { ADMIN_NAV_AREAS } from '../config/navigation';
import { useAuth } from '../auth/AuthContext';
import type { User } from '../types';
import { UserRole } from '../config/roles';
import { computeMenu, computeTabAreas, canAccessArea } from './computeMenu';
import {
  can,
  hasAllRoles,
  hasAnyRole,
  hasRole,
  resolveEffectivePermissions,
  type CanOptions,
  type PermissionInput,
} from './permissionModel';
import { resolveRolePreset } from './roleModel';
import type { RolePreset } from './rolePresets';
import { getVisibleDashboardTabs, canViewDashboardTab } from './dashboardRules';
import type { DashboardTabKey } from './permissions';
export interface RbacContextValue {
  user: User | null;
  permissions: ReadonlySet<string>;
  rolePreset: RolePreset | null;
  drawerAreas: AdminNavArea[];
  tabAreas: AdminNavArea[];
  visibleDashboardTabs: DashboardTabKey[];
  can: (permission: PermissionInput, options?: CanOptions) => boolean;
  canAccessArea: (areaKey: AdminAreaKey) => boolean;
  canViewDashboardTab: (tab: DashboardTabKey) => boolean;
  hasRole: (role: RolePreset | UserRole | string) => boolean;
  hasAnyRole: (...roles: Array<RolePreset | UserRole | string>) => boolean;
  hasAllRoles: (...roles: Array<RolePreset | UserRole | string>) => boolean;
}

const RbacContext = createContext<RbacContextValue | undefined>(undefined);

export const RbacProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { user, status } = useAuth();

  const permissionSet = useMemo(() => {
    if (status !== 'authenticated' || !user) {
      return new Set<string>();
    }
    return resolveEffectivePermissions(user);
  }, [user, status]);

  const rolePreset = useMemo(
    () => (user ? resolveRolePreset(user.role, user.roleName) : null),
    [user],
  );

  const drawerAreas = useMemo(
    () => computeMenu(ADMIN_NAV_AREAS, permissionSet),
    [permissionSet],
  );

  const tabAreas = useMemo(
    () => computeTabAreas(ADMIN_NAV_AREAS, permissionSet),
    [permissionSet],
  );

  const visibleDashboardTabs = useMemo(
    () => getVisibleDashboardTabs(permissionSet),
    [permissionSet],
  );

  const canFn = useCallback(
    (permission: PermissionInput, options?: CanOptions) => can(permissionSet, permission, options),
    [permissionSet],
  );

  const canAccessAreaFn = useCallback(
    (areaKey: AdminAreaKey) => canAccessArea(permissionSet, areaKey),
    [permissionSet],
  );

  const canViewDashboardTabFn = useCallback(
    (tab: DashboardTabKey) => canViewDashboardTab(permissionSet, tab),
    [permissionSet],
  );

  const hasRoleFn = useCallback(
    (role: RolePreset | UserRole | string) => hasRole(user, role),
    [user],
  );

  const hasAnyRoleFn = useCallback(
    (...roles: Array<RolePreset | UserRole | string>) => hasAnyRole(user, ...roles),
    [user],
  );

  const hasAllRolesFn = useCallback(
    (...roles: Array<RolePreset | UserRole | string>) => hasAllRoles(user, ...roles),
    [user],
  );

  const value = useMemo<RbacContextValue>(
    () => ({
      user,
      permissions: permissionSet,
      rolePreset,
      drawerAreas,
      tabAreas,
      visibleDashboardTabs,
      can: canFn,
      canAccessArea: canAccessAreaFn,
      canViewDashboardTab: canViewDashboardTabFn,
      hasRole: hasRoleFn,
      hasAnyRole: hasAnyRoleFn,
      hasAllRoles: hasAllRolesFn,
    }),
    [
      user,
      permissionSet,
      rolePreset,
      drawerAreas,
      tabAreas,
      visibleDashboardTabs,
      canFn,
      canAccessAreaFn,
      canViewDashboardTabFn,
      hasRoleFn,
      hasAnyRoleFn,
      hasAllRolesFn,
    ],
  );

  return <RbacContext.Provider value={value}>{children}</RbacContext.Provider>;
};

export function useRbac(): RbacContextValue {
  const ctx = useContext(RbacContext);
  if (!ctx) {
    throw new Error('useRbac must be used within an RbacProvider');
  }
  return ctx;
}
