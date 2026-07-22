import type { DashboardWidgetId } from '../types/widget';

/** Static labels/icons only — values come from API adapters. */
export const KPI_METADATA: Record<
  DashboardWidgetId,
  { label: string; icon: string }
> = {
  population_attendance_kpi: { label: 'School population & attendance', icon: 'school-outline' },
  collections_kpi: { label: 'Collections', icon: 'cash-outline' },
  outstanding_fees_kpi: { label: 'Outstanding fees', icon: 'wallet-outline' },
  pending_approvals_kpi: { label: 'Pending approvals', icon: 'document-text-outline' },
};
