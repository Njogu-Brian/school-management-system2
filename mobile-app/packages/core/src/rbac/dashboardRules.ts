import { DASHBOARD_TAB_PERMISSIONS, type DashboardTabKey } from './permissions';
import { can } from './permissionModel';

/**
 * Dashboard visibility rules (Batch 3 — no widgets).
 * Determines which dashboard tabs a user may see; widget gating lands in Sprint 2.
 */
export function getVisibleDashboardTabs(permissionSet: Set<string>): DashboardTabKey[] {
  const tabs: DashboardTabKey[] = [];
  for (const key of Object.keys(DASHBOARD_TAB_PERMISSIONS) as DashboardTabKey[]) {
    const perms = DASHBOARD_TAB_PERMISSIONS[key];
    if (can(permissionSet, perms)) {
      tabs.push(key);
    }
  }
  return tabs;
}

export function canViewDashboardTab(
  permissionSet: Set<string>,
  tab: DashboardTabKey,
): boolean {
  return can(permissionSet, DASHBOARD_TAB_PERMISSIONS[tab]);
}
