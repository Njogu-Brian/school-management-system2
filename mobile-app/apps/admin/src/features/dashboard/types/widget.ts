import type { WidgetDisplayState } from '@erp/ui';
import type { PermissionInput } from '@erp/core';

/** Stable widget identifiers for registry and future API mapping. */
export type DashboardWidgetId =
  | 'population_attendance_kpi'
  | 'collections_kpi'
  | 'outstanding_fees_kpi'
  | 'pending_approvals_kpi';

export interface DashboardWidgetDefinition {
  id: DashboardWidgetId;
  /** User must hold ≥1 permission to see the widget. */
  permissions: PermissionInput;
  /** Placeholder/demo display state (no API in Batch 1). */
  defaultState?: WidgetDisplayState;
}

export interface KpiPlaceholderData {
  label: string;
  value: string;
  delta?: string;
  deltaPositive?: boolean;
  icon?: string;
}
