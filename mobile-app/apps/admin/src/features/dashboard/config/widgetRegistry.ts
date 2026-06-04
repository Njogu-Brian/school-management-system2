import { AdminPermission } from '@erp/core';
import type { DashboardWidgetDefinition } from '../types/widget';

/**
 * Dashboard widget registry — permission metadata + stable ids for future
 * `GET /dashboard/stats` mapping (Sprint 2+).
 */
export const DASHBOARD_WIDGET_REGISTRY: readonly DashboardWidgetDefinition[] = [
  {
    id: 'enrollment_kpi',
    permissions: [AdminPermission.DASHBOARD_VIEW, AdminPermission.STUDENTS_VIEW],
    defaultState: 'success',
  },
  {
    id: 'attendance_kpi',
    permissions: [AdminPermission.STUDENTS_VIEW],
    defaultState: 'success',
  },
  {
    id: 'collections_kpi',
    permissions: [AdminPermission.FINANCE_VIEW],
    defaultState: 'success',
  },
  {
    id: 'outstanding_fees_kpi',
    permissions: [AdminPermission.FINANCE_VIEW],
    defaultState: 'success',
  },
  {
    id: 'pending_approvals_kpi',
    permissions: [AdminPermission.DASHBOARD_VIEW, AdminPermission.DASHBOARD_APPROVALS_VIEW],
    defaultState: 'success',
  },
];
