import React from 'react';
import type { DashboardWidgetId } from '../types/widget';
import { AttendanceKpiWidget } from './AttendanceKpiWidget';
import { CollectionsKpiWidget } from './CollectionsKpiWidget';
import { EnrollmentKpiWidget } from './EnrollmentKpiWidget';
import { OutstandingFeesKpiWidget } from './OutstandingFeesKpiWidget';
import { PendingApprovalsKpiWidget } from './PendingApprovalsKpiWidget';

export const WIDGET_COMPONENTS: Record<DashboardWidgetId, React.FC> = {
  enrollment_kpi: EnrollmentKpiWidget,
  attendance_kpi: AttendanceKpiWidget,
  collections_kpi: CollectionsKpiWidget,
  outstanding_fees_kpi: OutstandingFeesKpiWidget,
  pending_approvals_kpi: PendingApprovalsKpiWidget,
};
