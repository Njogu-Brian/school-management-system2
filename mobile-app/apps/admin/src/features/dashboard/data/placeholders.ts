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
    icon: 'people-outline',
    permissions: ['students.view'],
  },
  {
    id: 'qa_finance',
    label: 'Collections',
    icon: 'cash-outline',
    permissions: ['finance.view'],
  },
  {
    id: 'qa_approvals',
    label: 'Approvals inbox',
    icon: 'checkbox-outline',
    permissions: ['dashboard.view', 'dashboard.approvals.view'],
  },
  {
    id: 'qa_admissions',
    label: 'Applications',
    icon: 'school-outline',
    permissions: ['admissions.view'],
  },
];
