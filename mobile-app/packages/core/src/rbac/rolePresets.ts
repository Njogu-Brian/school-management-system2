import { AdminPermission, type PermissionKey } from './permissions';

/**
 * Organizational role presets (IA §2.3–2.4, Batch 3 brief).
 * Used for fallback permission resolution when `/user` returns an empty permission list.
 * Visibility is permission-first; presets are bundles, not menu switches.
 */
export enum RolePreset {
  DIRECTOR = 'director',
  PRINCIPAL = 'principal',
  DEPUTY_PRINCIPAL = 'deputy_principal',
  SENIOR_TEACHER = 'senior_teacher',
  TEACHER = 'teacher',
  ACCOUNTANT = 'accountant',
  BURSAR = 'bursar',
  SECRETARY = 'secretary',
  RECEPTIONIST = 'receptionist',
  LIBRARIAN = 'librarian',
  NURSE = 'nurse',
  STORE_KEEPER = 'store_keeper',
  DRIVER = 'driver',
  SECURITY_OFFICER = 'security_officer',
  /** Platform / school-wide administrator (maps from super_admin / admin). */
  SUPER_ADMIN = 'super_admin',
  ADMIN = 'admin',
}

/** Full Admin module access (leadership / platform admin). */
const LEADERSHIP_PERMISSIONS: PermissionKey[] = [
  AdminPermission.DASHBOARD_VIEW,
  AdminPermission.DASHBOARD_APPROVALS_VIEW,
  AdminPermission.DASHBOARD_ALERTS_VIEW,
  AdminPermission.ADMISSIONS_VIEW,
  AdminPermission.STUDENTS_VIEW,
  AdminPermission.ACADEMICS_VIEW,
  AdminPermission.FINANCE_VIEW,
  AdminPermission.PEOPLE_VIEW,
  AdminPermission.OPERATIONS_VIEW,
  AdminPermission.COMMUNICATION_VIEW,
  AdminPermission.REPORTS_VIEW,
  AdminPermission.SETTINGS_VIEW,
];

const ACADEMIC_LEADERSHIP: PermissionKey[] = [
  AdminPermission.DASHBOARD_VIEW,
  AdminPermission.DASHBOARD_APPROVALS_VIEW,
  AdminPermission.DASHBOARD_ALERTS_VIEW,
  AdminPermission.STUDENTS_VIEW,
  AdminPermission.ACADEMICS_VIEW,
  AdminPermission.PEOPLE_VIEW,
  AdminPermission.COMMUNICATION_VIEW,
  AdminPermission.REPORTS_VIEW,
  AdminPermission.ADMISSIONS_VIEW,
];

const FINANCE_SUITE: PermissionKey[] = [
  AdminPermission.DASHBOARD_VIEW,
  AdminPermission.DASHBOARD_ALERTS_VIEW,
  AdminPermission.FINANCE_VIEW,
  AdminPermission.REPORTS_VIEW,
  AdminPermission.STUDENTS_VIEW,
];

const SECRETARIAL: PermissionKey[] = [
  AdminPermission.DASHBOARD_VIEW,
  AdminPermission.DASHBOARD_APPROVALS_VIEW,
  AdminPermission.ADMISSIONS_VIEW,
  AdminPermission.STUDENTS_VIEW,
  AdminPermission.COMMUNICATION_VIEW,
  AdminPermission.PEOPLE_VIEW,
];

const RECEPTION: PermissionKey[] = [
  AdminPermission.DASHBOARD_VIEW,
  AdminPermission.ADMISSIONS_VIEW,
  AdminPermission.COMMUNICATION_VIEW,
  AdminPermission.OPERATIONS_VIEW,
];

const OPERATIONS_SUBSET = (modules: PermissionKey[]): PermissionKey[] => [
  AdminPermission.DASHBOARD_VIEW,
  AdminPermission.DASHBOARD_ALERTS_VIEW,
  AdminPermission.OPERATIONS_VIEW,
  ...modules,
];

/**
 * Fallback permission matrix: preset → granted permission keys.
 * Server claims always win when non-empty (see `resolveEffectivePermissions`).
 */
export const ROLE_PRESET_PERMISSIONS: Record<RolePreset, readonly PermissionKey[]> = {
  [RolePreset.SUPER_ADMIN]: [AdminPermission.ALL],
  [RolePreset.DIRECTOR]: LEADERSHIP_PERMISSIONS,
  [RolePreset.PRINCIPAL]: LEADERSHIP_PERMISSIONS,
  [RolePreset.ADMIN]: LEADERSHIP_PERMISSIONS,
  [RolePreset.DEPUTY_PRINCIPAL]: ACADEMIC_LEADERSHIP,
  [RolePreset.SENIOR_TEACHER]: [
    AdminPermission.DASHBOARD_VIEW,
    AdminPermission.STUDENTS_VIEW,
    AdminPermission.ACADEMICS_VIEW,
    AdminPermission.REPORTS_VIEW,
  ],
  [RolePreset.TEACHER]: [AdminPermission.DASHBOARD_VIEW, AdminPermission.STUDENTS_VIEW],
  [RolePreset.ACCOUNTANT]: FINANCE_SUITE,
  [RolePreset.BURSAR]: FINANCE_SUITE,
  [RolePreset.SECRETARY]: SECRETARIAL,
  [RolePreset.RECEPTIONIST]: RECEPTION,
  [RolePreset.LIBRARIAN]: OPERATIONS_SUBSET([]),
  [RolePreset.NURSE]: OPERATIONS_SUBSET([]),
  [RolePreset.STORE_KEEPER]: OPERATIONS_SUBSET([]),
  [RolePreset.DRIVER]: OPERATIONS_SUBSET([]),
  [RolePreset.SECURITY_OFFICER]: OPERATIONS_SUBSET([]),
};
