import {
  mapQueryToWidgetState,
  useAuth,
  useDashboardStats,
  usePendingApprovals,
} from '@erp/core';
import type { WidgetDisplayState } from '@erp/ui';
import type { KpiCardProps } from '@erp/ui';
import { useMemo } from 'react';
import {
  adaptKpiFromStats,
  adaptPendingApprovalsKpi,
} from '../adapters/kpiAdapters';
import { KPI_METADATA } from '../config/kpiMetadata';
import type { DashboardWidgetId } from '../types/widget';
import { useIsWidgetVisible } from './useDashboardWidgets';

export interface KpiWidgetData {
  state: WidgetDisplayState;
  kpi: KpiCardProps | null;
  onRetry?: () => void;
}

const STATS_WIDGET_IDS = new Set<DashboardWidgetId>([
  'population_attendance_kpi',
  'collections_kpi',
  'outstanding_fees_kpi',
]);

/**
 * Binds a dashboard KPI widget to TanStack Query + adapters.
 * Query → WidgetShell: loading | error | empty | success; refetch → retry.
 */
export function useKpiWidgetData(widgetId: DashboardWidgetId): KpiWidgetData {
  const visible = useIsWidgetVisible(widgetId);
  const { status: authStatus } = useAuth();
  const enabled = visible && authStatus === 'authenticated';

  const statsQuery = useDashboardStats({
    enabled: enabled && STATS_WIDGET_IDS.has(widgetId),
  });
  const pendingQuery = usePendingApprovals({
    enabled: enabled && widgetId === 'pending_approvals_kpi',
  });

  return useMemo((): KpiWidgetData => {
    const label = KPI_METADATA[widgetId].label;

    if (!enabled) {
      return { state: 'loading', kpi: { label, value: '—' } };
    }

    if (widgetId === 'pending_approvals_kpi') {
      const state = mapQueryToWidgetState({
        isPending: pendingQuery.isPending,
        isLoading: pendingQuery.isLoading,
        isError: pendingQuery.isError,
        isSuccess: pendingQuery.isSuccess,
        isEmpty: pendingQuery.isSuccess && (pendingQuery.data?.total ?? 0) === 0,
      });

      if (state === 'success' && pendingQuery.data) {
        const { kpi } = adaptPendingApprovalsKpi(pendingQuery.data);
        return { state, kpi, onRetry: () => void pendingQuery.refetch() };
      }

      return {
        state,
        kpi: state === 'success' ? null : { label, value: '—' },
        onRetry: pendingQuery.isError ? () => void pendingQuery.refetch() : undefined,
      };
    }

    const state = mapQueryToWidgetState({
      isPending: statsQuery.isPending,
      isLoading: statsQuery.isLoading,
      isError: statsQuery.isError,
      isSuccess: statsQuery.isSuccess,
      isEmpty:
        statsQuery.isSuccess &&
        statsQuery.data != null &&
        adaptKpiFromStats(
          widgetId as Exclude<DashboardWidgetId, 'pending_approvals_kpi'>,
          statsQuery.data,
        ).isEmpty,
    });

    if (state === 'success' && statsQuery.data) {
      const { kpi } = adaptKpiFromStats(
        widgetId as Exclude<DashboardWidgetId, 'pending_approvals_kpi'>,
        statsQuery.data,
      );
      return { state, kpi, onRetry: () => void statsQuery.refetch() };
    }

    return {
      state,
      kpi: state === 'success' ? null : { label, value: '—' },
      onRetry: statsQuery.isError ? () => void statsQuery.refetch() : undefined,
    };
  }, [
    enabled,
    widgetId,
    statsQuery.isPending,
    statsQuery.isLoading,
    statsQuery.isError,
    statsQuery.isSuccess,
    statsQuery.data,
    statsQuery.refetch,
    pendingQuery.isPending,
    pendingQuery.isLoading,
    pendingQuery.isError,
    pendingQuery.isSuccess,
    pendingQuery.data,
    pendingQuery.refetch,
  ]);
}
