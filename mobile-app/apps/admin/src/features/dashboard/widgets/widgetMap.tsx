import React from 'react';
import type { DashboardWidgetId } from '../types/widget';
import { CollectionsKpiWidget } from './CollectionsKpiWidget';
import { OutstandingFeesKpiWidget } from './OutstandingFeesKpiWidget';
import { PopulationAttendanceKpiWidget } from './PopulationAttendanceKpiWidget';

export const WIDGET_COMPONENTS: Record<
  Exclude<DashboardWidgetId, 'pending_approvals_kpi'>,
  React.FC
> = {
  population_attendance_kpi: PopulationAttendanceKpiWidget,
  collections_kpi: CollectionsKpiWidget,
  outstanding_fees_kpi: OutstandingFeesKpiWidget,
};
