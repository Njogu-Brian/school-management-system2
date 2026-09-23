export interface AlertPlaceholder {
  id: string;
  title: string;
  message: string;
  severity: 'info' | 'success' | 'warning' | 'error';
  timestamp: string;
}

export interface OperationalStatusPlaceholder {
  id: string;
  label: string;
  status: 'ok' | 'warning' | 'error';
  detail: string;
}

export interface QuickActionPlaceholder {
  id: string;
  label: string;
  icon: string;
  permissions: readonly string[];
}

/** Alerts / quick actions remain static until Batch 3+ widget APIs. */
export const ALERT_PLACEHOLDERS: AlertPlaceholder[] = [
  {
    id: 'a1',
    title: 'Defaulters spike',
    message: 'Year 8 arrears increased 18% week-on-week.',
    severity: 'warning',
    timestamp: '2h ago',
  },
  {
    id: 'a2',
    title: 'Unreconciled M-Pesa',
    message: '14 transactions awaiting reconciliation.',
    severity: 'error',
    timestamp: '4h ago',
  },
  {
    id: 'a3',
    title: 'Report cards',
    message: 'Form 2 publish deadline in 3 days.',
    severity: 'info',
    timestamp: 'Yesterday',
  },
];

export const OPERATIONAL_STATUS_PLACEHOLDERS: OperationalStatusPlaceholder[] = [
  { id: 's1', label: 'SMS gateway', status: 'ok', detail: 'Operational' },
  { id: 's2', label: 'M-Pesa channel', status: 'warning', detail: 'Delayed callbacks' },
  { id: 's3', label: 'Backup', status: 'ok', detail: 'Last run 6h ago' },
];

export const QUICK_ACTION_PLACEHOLDERS: QuickActionPlaceholder[] = [
  {
    id: 'qa_students',
    label: 'Student registry',
    icon: 'student-registry',
    permissions: ['students.view'],
  },
  {
    id: 'qa_admissions',
    label: 'Applications',
    icon: 'applications',
    permissions: ['admissions.view'],
  },
  {
    id: 'qa_attendance',
    label: 'Attendance',
    icon: 'attendance',
    permissions: ['students.view', 'academics.view', 'dashboard.view'],
  },
  {
    id: 'qa_clock',
    label: 'Staff attendance',
    icon: 'staff-attendance',
    permissions: ['people.view', 'staff.view'],
  },
  {
    id: 'qa_staff_calendar',
    label: 'Staff calendar',
    icon: 'calendar',
    permissions: ['people.view', 'staff.view'],
  },
  {
    id: 'qa_concerns',
    label: 'Report concern',
    icon: 'report-concern',
    permissions: ['operations.view', 'dashboard.view'],
  },
];
