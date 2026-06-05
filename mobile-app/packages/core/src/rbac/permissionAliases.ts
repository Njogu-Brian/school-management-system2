/**
 * Maps Laravel Spatie permission names to Admin App canonical keys.
 * Applied when resolving server-granted permissions (see permissionModel.ts).
 */
export const PERMISSION_ALIASES: Readonly<Record<string, readonly string[]>> = {
  'admin.dashboard': ['dashboard.view'],
  'staff.view': ['people.view'],
  'manage staff': ['people.view'],
  'manage students': ['students.view'],
  'students.manage': ['students.view'],
  'manage finance': ['finance.view'],
  'finance.manage': ['finance.view'],
  'manage transport': ['transport.view'],
  'transport.manage': ['transport.view'],
  'inventory.view': ['operations.view'],
  'inventory.manage': ['operations.view'],
  'academics.manage': ['academics.view'],
  'manage settings': ['settings.view'],
  'settings.manage': ['settings.view'],
  'admissions.online_admission': ['admissions.view'],
  'dashboard.approvals.view': ['approvals.view'],
  'lesson_plans.view': ['academics.view'],
  'report_cards.view': ['academics.view'],
  'exams.view': ['academics.view'],
};

/** Expand a normalized permission set with mobile canonical aliases. */
export function expandPermissionAliases(set: Set<string>): Set<string> {
  const expanded = new Set(set);
  for (const perm of set) {
    const aliases = PERMISSION_ALIASES[perm];
    if (aliases) {
      for (const alias of aliases) {
        expanded.add(alias);
      }
    }
  }
  return expanded;
}
