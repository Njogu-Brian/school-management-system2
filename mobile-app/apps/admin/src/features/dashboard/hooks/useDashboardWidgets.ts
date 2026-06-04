import { useCan, useRbac } from '@erp/core';
import type { PermissionInput } from '@erp/core';
import { useMemo } from 'react';
import { DASHBOARD_WIDGET_REGISTRY } from '../config/widgetRegistry';
import type { DashboardWidgetDefinition, DashboardWidgetId } from '../types/widget';

function canSeeWidget(
  check: (permission: PermissionInput, options?: { requireAll?: boolean }) => boolean,
  permissions: PermissionInput,
): boolean {
  const list = Array.isArray(permissions) ? permissions : [permissions];
  return check(list);
}

/** Widget definitions visible to the current user (permission-gated). */
export function useVisibleDashboardWidgets(): DashboardWidgetDefinition[] {
  const { can } = useRbac();

  return useMemo(
    () =>
      DASHBOARD_WIDGET_REGISTRY.filter((w) => canSeeWidget(can, w.permissions)),
    [can],
  );
}

export function useIsWidgetVisible(id: DashboardWidgetId): boolean {
  const visible = useVisibleDashboardWidgets();
  return visible.some((w) => w.id === id);
}

/** Section-level gate helpers. */
export function useCanViewDashboardOverview(): boolean {
  return useCan('dashboard.view');
}

export function useCanViewFinanceWidgets(): boolean {
  return useCan('finance.view');
}

export function useCanViewStudentWidgets(): boolean {
  return useCan('students.view');
}
