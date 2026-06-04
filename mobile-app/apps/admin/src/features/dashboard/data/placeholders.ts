import type { WidgetDisplayState } from '@erp/ui';
import type { DashboardWidgetId, KpiPlaceholderData } from '../types/widget';

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

/** Static KPI values — replaced by API hooks in Sprint 2 Batch 2+. */
export const KPI_PLACEHOLDERS: Record<DashboardWidgetId, KpiPlaceholderData> = {
  enrollment_kpi: {
    label: 'Enrollment',
    value: '1,248',
    delta: '+12 this term',
    deltaPositive: true,
    icon: 'school-outline',
  },
  attendance_kpi: {
    label: 'Attendance today',
    value: '94.2%',
    delta: '-0.8% vs yesterday',
    deltaPositive: false,
    icon: 'checkmark-circle-outline',
  },
  collections_kpi: {
    label: 'Collections (MTD)',
    value: 'KES 4.2M',
    delta: '78% of target',
    deltaPositive: true,
    icon: 'cash-outline',
  },
  outstanding_fees_kpi: {
    label: 'Outstanding fees',
    value: 'KES 1.1M',
    delta: '142 accounts',
    deltaPositive: false,
    icon: 'wallet-outline',
  },
  pending_approvals_kpi: {
    label: 'Pending approvals',
    value: '7',
    delta: '3 urgent',
    deltaPositive: false,
    icon: 'document-text-outline',
  },
};

/** Demo states for framework QA (cycle via widget id suffix in dev if needed). */
export const WIDGET_DEMO_STATES: Partial<Record<DashboardWidgetId, WidgetDisplayState>> = {};

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
