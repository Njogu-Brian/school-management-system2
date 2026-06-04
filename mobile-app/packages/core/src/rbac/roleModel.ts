import { UserRole } from '../config/roles';
import { RolePreset } from './rolePresets';

/**
 * Maps backend / normalized role strings to an organizational preset for fallback RBAC.
 */
export function resolveRolePreset(
  role: UserRole | string | null | undefined,
  roleName?: string | null,
): RolePreset | null {
  const candidates = [role, roleName].filter(Boolean).map((r) => String(r).trim().toLowerCase());

  const map: Record<string, RolePreset> = {
    super_admin: RolePreset.SUPER_ADMIN,
    superadmin: RolePreset.SUPER_ADMIN,
    'super admin': RolePreset.SUPER_ADMIN,
    admin: RolePreset.ADMIN,
    administrator: RolePreset.ADMIN,
    director: RolePreset.DIRECTOR,
    'school director': RolePreset.DIRECTOR,
    principal: RolePreset.PRINCIPAL,
    'deputy principal': RolePreset.DEPUTY_PRINCIPAL,
    deputy_principal: RolePreset.DEPUTY_PRINCIPAL,
    senior_teacher: RolePreset.SENIOR_TEACHER,
    'senior teacher': RolePreset.SENIOR_TEACHER,
    supervisor: RolePreset.SENIOR_TEACHER,
    teacher: RolePreset.TEACHER,
    accountant: RolePreset.ACCOUNTANT,
    finance: RolePreset.BURSAR,
    'finance officer': RolePreset.BURSAR,
    bursar: RolePreset.BURSAR,
    secretary: RolePreset.SECRETARY,
    receptionist: RolePreset.RECEPTIONIST,
    librarian: RolePreset.LIBRARIAN,
    nurse: RolePreset.NURSE,
    'store keeper': RolePreset.STORE_KEEPER,
    store_keeper: RolePreset.STORE_KEEPER,
    driver: RolePreset.DRIVER,
    transport: RolePreset.DRIVER,
    security: RolePreset.SECURITY_OFFICER,
    'security officer': RolePreset.SECURITY_OFFICER,
    academic_admin: RolePreset.DEPUTY_PRINCIPAL,
    'academic administrator': RolePreset.DEPUTY_PRINCIPAL,
    'academic admin': RolePreset.DEPUTY_PRINCIPAL,
    'head teacher': RolePreset.DEPUTY_PRINCIPAL,
  };

  for (const c of candidates) {
    if (map[c]) {
      return map[c];
    }
  }
  return null;
}

/** Whether a preset matches a UserRole slug or another preset id. */
export function presetMatchesRole(
  preset: RolePreset | null,
  target: RolePreset | UserRole | string,
): boolean {
  if (!preset) {
    return false;
  }
  const t = String(target).trim().toLowerCase();
  if (preset === t) {
    return true;
  }
  const resolved = resolveRolePreset(target as UserRole, target);
  return resolved === preset;
}
