import type { DashboardWidgetId } from '../types/widget';

/** Static labels/icons only — values come from API adapters (Batch 2). */
export const KPI_METADATA: Record<
  DashboardWidgetId,
  { label: string; icon: string }
> = {
  enrollment_kpi: { label: 'Enrollment', icon: 'school-outline' },
  attendance_kpi: { label: 'Attendance today', icon: 'checkmark-circle-outline' },
  collections_kpi: { label: 'Collections', icon: 'cash-outline' },
  outstanding_fees_kpi: { label: 'Outstanding fees', icon: 'wallet-outline' },
  pending_approvals_kpi: { label: 'Pending approvals', icon: 'document-text-outline' },
};
