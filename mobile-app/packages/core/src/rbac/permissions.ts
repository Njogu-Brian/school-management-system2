import type { AdminAreaKey } from '../config/navigation';
import type { PermissionName } from '../types';

/**
 * Canonical Admin App permission keys (`<resource>.<action>`).
 * Mirrors Backlog E4 taxonomy; used by the registry, menu filter, and gates.
 */
export const AdminPermission = {
  // Dashboard
  DASHBOARD_VIEW: 'dashboard.view',
  DASHBOARD_APPROVALS_VIEW: 'dashboard.approvals.view',
  DASHBOARD_ALERTS_VIEW: 'dashboard.alerts.view',
  APPROVALS_VIEW: 'approvals.view',

  // Top-level modules (minimum to see area in nav)
  ADMISSIONS_VIEW: 'admissions.view',
  STUDENTS_VIEW: 'students.view',
  ACADEMICS_VIEW: 'academics.view',
  FINANCE_VIEW: 'finance.view',
  PEOPLE_VIEW: 'people.view',
  OPERATIONS_VIEW: 'operations.view',
  COMMUNICATION_VIEW: 'communication.view',
  REPORTS_VIEW: 'reports.view',
  SETTINGS_VIEW: 'settings.view',

  /** Wildcard — Super Admin / full access (server or client grant). */
  ALL: '*',
} as const;

export type PermissionKey = (typeof AdminPermission)[keyof typeof AdminPermission];

/** Every permission the Admin shell knows about (excludes wildcard). */
export const ALL_ADMIN_PERMISSIONS: readonly PermissionName[] = Object.values(
  AdminPermission,
).filter((p) => p !== AdminPermission.ALL);

/**
 * Permissions required to show a nav area (user needs ≥1).
 * Additional granular keys can be added per area in later batches.
 */
export const AREA_VIEW_PERMISSIONS: Record<AdminAreaKey, readonly PermissionName[]> = {
  dashboard: [AdminPermission.DASHBOARD_VIEW],
  approvals: [
    AdminPermission.APPROVALS_VIEW,
    AdminPermission.DASHBOARD_APPROVALS_VIEW,
    AdminPermission.DASHBOARD_VIEW,
  ],
  admissions: [AdminPermission.ADMISSIONS_VIEW],
  students: [AdminPermission.STUDENTS_VIEW],
  academics: [AdminPermission.ACADEMICS_VIEW],
  finance: [AdminPermission.FINANCE_VIEW],
  people: [AdminPermission.PEOPLE_VIEW, 'staff.view'],
  operations: [AdminPermission.OPERATIONS_VIEW],
  communication: [AdminPermission.COMMUNICATION_VIEW],
  reports: [AdminPermission.REPORTS_VIEW],
  settings: [AdminPermission.SETTINGS_VIEW],
};

/** Dashboard tab visibility (Batch 3 — rules only, no widgets). */
export const DASHBOARD_TAB_PERMISSIONS = {
  overview: [AdminPermission.DASHBOARD_VIEW],
  approvals: [
    AdminPermission.APPROVALS_VIEW,
    AdminPermission.DASHBOARD_APPROVALS_VIEW,
    AdminPermission.DASHBOARD_VIEW,
  ],
  alerts: [AdminPermission.DASHBOARD_ALERTS_VIEW, AdminPermission.DASHBOARD_VIEW],
} as const;

export type DashboardTabKey = keyof typeof DASHBOARD_TAB_PERMISSIONS;
