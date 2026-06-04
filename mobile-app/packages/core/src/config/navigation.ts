/**
 * Admin App navigation tree (data, not components).
 *
 * Mirrors the 10 top-level areas from the Admin Information Architecture (IA §1).
 * In a later batch this same data drives permission-first menu rendering
 * (`computeMenu`, build plan §7.3); for the shell it drives the drawer + tab bar.
 *
 * `icon` values are Ionicons glyph names (resolved by @erp/ui / @expo/vector-icons).
 */

export type AdminAreaKey =
  | 'dashboard'
  | 'admissions'
  | 'students'
  | 'academics'
  | 'finance'
  | 'people'
  | 'operations'
  | 'communication'
  | 'reports'
  | 'settings';

export interface AdminNavArea {
  key: AdminAreaKey;
  label: string;
  /** Ionicons glyph name. */
  icon: string;
  /** Short description shown on the module placeholder. */
  description: string;
  /** Planned sub-areas (IA §1) — descriptive copy for the placeholder, not data. */
  sections: string[];
  /** Whether this area is surfaced as a primary bottom tab (vs drawer-only). */
  inTabs: boolean;
}

export const ADMIN_NAV_AREAS: readonly AdminNavArea[] = [
  {
    key: 'dashboard',
    label: 'Dashboard',
    icon: 'grid-outline',
    description: 'Role-aware command center: overview, approvals, and alerts.',
    sections: ['Overview', 'Approvals', 'Alerts'],
    inTabs: true,
  },
  {
    key: 'admissions',
    label: 'Admissions',
    icon: 'school-outline',
    description: 'Applications pipeline, enrollment, and transfers.',
    sections: ['Applications', 'Enrollment', 'Transfers'],
    inTabs: false,
  },
  {
    key: 'students',
    label: 'Students',
    icon: 'people-outline',
    description: 'Student 360, categories, promotion, and alumni.',
    sections: ['Student 360', 'Categories', 'Promotion', 'Alumni'],
    inTabs: true,
  },
  {
    key: 'academics',
    label: 'Academics',
    icon: 'book-outline',
    description: 'Structure, timetable, CBC, assessments, and report cards.',
    sections: ['Structure', 'Timetable', 'CBC', 'Assessments', 'Report Cards'],
    inTabs: false,
  },
  {
    key: 'finance',
    label: 'Finance',
    icon: 'cash-outline',
    description: 'Billing, collections, reconciliation, accounting, and payroll.',
    sections: ['Dashboard', 'Billing', 'Collections', 'Reconciliation', 'Accounting', 'Payroll'],
    inTabs: true,
  },
  {
    key: 'people',
    label: 'People',
    icon: 'briefcase-outline',
    description: 'Staff directory, leave, attendance, performance, and roles.',
    sections: ['Staff', 'Leave', 'Attendance', 'Performance', 'Roles & Permissions'],
    inTabs: true,
  },
  {
    key: 'operations',
    label: 'Operations',
    icon: 'bus-outline',
    description: 'Transport, inventory, procurement, library, clinic, visitors, security.',
    sections: [
      'Transport',
      'Inventory',
      'Procurement',
      'Library',
      'Clinic',
      'Visitors',
      'Security',
    ],
    inTabs: false,
  },
  {
    key: 'communication',
    label: 'Communication',
    icon: 'chatbubbles-outline',
    description: 'Messages, announcements, circulars, and templates.',
    sections: ['Messages', 'Announcements', 'Templates'],
    inTabs: false,
  },
  {
    key: 'reports',
    label: 'Reports',
    icon: 'bar-chart-outline',
    description: 'Academic, finance, operations, and executive board pack.',
    sections: ['Academic', 'Finance', 'Operations', 'Executive'],
    inTabs: false,
  },
  {
    key: 'settings',
    label: 'Settings',
    icon: 'settings-outline',
    description: 'School identity, academic, finance, communication, integrations.',
    sections: ['School', 'Academic', 'Finance', 'Communication', 'Integrations'],
    inTabs: false,
  },
];

export const ADMIN_TAB_AREAS: readonly AdminNavArea[] = ADMIN_NAV_AREAS.filter(
  (area) => area.inTabs,
);

export function getNavArea(key: AdminAreaKey): AdminNavArea {
  const area = ADMIN_NAV_AREAS.find((a) => a.key === key);
  if (!area) {
    throw new Error(`Unknown admin nav area: ${key}`);
  }
  return area;
}
