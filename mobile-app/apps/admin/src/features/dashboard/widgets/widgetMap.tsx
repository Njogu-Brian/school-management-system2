import React from 'react';
import type { DashboardWidgetId } from '../types/widget';
import { CollectionsKpiWidget } from './CollectionsKpiWidget';
import { OutstandingFeesKpiWidget } from './OutstandingFeesKpiWidget';
import { PendingApprovalsKpiWidget } from './PendingApprovalsKpiWidget';
import { PopulationAttendanceKpiWidget } from './PopulationAttendanceKpiWidget';

export const WIDGET_COMPONENTS: Record<DashboardWidgetId, React.FC> = {
  population_attendance_kpi: PopulationAttendanceKpiWidget,
  collections_kpi: CollectionsKpiWidget,
  outstanding_fees_kpi: OutstandingFeesKpiWidget,
  pending_approvals_kpi: PendingApprovalsKpiWidget,
};
