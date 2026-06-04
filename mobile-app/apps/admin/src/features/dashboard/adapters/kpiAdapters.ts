import type {
  AdminDashboardStats,
  PendingApprovalsSummary,
} from '@erp/core';
import type { KpiCardProps } from '@erp/ui';
import { KPI_METADATA } from '../config/kpiMetadata';
import type { DashboardWidgetId } from '../types/widget';
import { formatInteger, formatKes, formatPercent } from './formatters';

export interface KpiAdapterResult {
  kpi: KpiCardProps;
  isEmpty: boolean;
}

function meta(widgetId: DashboardWidgetId) {
  return KPI_METADATA[widgetId];
}

function termScopeCaption(stats: AdminDashboardStats): string | undefined {
  const filters = stats.filters;
  if (!filters?.term_id && !filters?.academic_year_id) {
    return 'All time';
  }
  const term = filters.available_terms?.find((t) => t.id === filters.term_id);
  if (term?.name) {
    return term.name;
  }
  const year = filters.available_years?.find((y) => y.id === filters.academic_year_id);
  if (year?.year != null) {
    return `Year ${year.year}`;
  }
  return undefined;
}

export function adaptEnrollmentKpi(stats: AdminDashboardStats): KpiAdapterResult {
  const { label, icon } = meta('enrollment_kpi');
  const total = stats.total_students ?? 0;
  return {
    isEmpty: total === 0,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: formatInteger(total),
      delta: 'Active students',
      deltaPositive: true,
    },
  };
}

export function adaptAttendanceKpi(stats: AdminDashboardStats): KpiAdapterResult {
  const { label, icon } = meta('attendance_kpi');
  const total = stats.total_students ?? 0;
  const present = stats.present_today ?? 0;
  const pct = total > 0 ? (present / total) * 100 : null;

  return {
    isEmpty: total === 0,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: pct != null ? formatPercent(pct) : '—',
      delta: `${formatInteger(present)} present today`,
      deltaPositive: pct != null && pct >= 85,
    },
  };
}

export function adaptCollectionsKpi(stats: AdminDashboardStats): KpiAdapterResult {
  const { label, icon } = meta('collections_kpi');
  const collected = stats.fees_collected ?? 0;
  const invoiced = stats.total_invoiced ?? 0;
  const scope = termScopeCaption(stats);
  const collectionRate =
    invoiced > 0 ? Math.min(100, Math.round((collected / invoiced) * 100)) : null;

  return {
    isEmpty: false,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: formatKes(collected),
      delta:
        collectionRate != null
          ? `${collectionRate}% of invoiced${scope ? ` · ${scope}` : ''}`
          : scope ?? 'Scoped collections',
      deltaPositive: collectionRate == null || collectionRate >= 50,
    },
  };
}

export function adaptOutstandingFeesKpi(stats: AdminDashboardStats): KpiAdapterResult {
  const { label, icon } = meta('outstanding_fees_kpi');
  const balance = stats.outstanding_balance ?? 0;
  const invoiced = stats.total_invoiced ?? 0;
  const scope = termScopeCaption(stats);

  return {
    isEmpty: balance === 0 && invoiced === 0,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: formatKes(balance),
      delta: scope ? `Balance · ${scope}` : 'Invoice balance',
      deltaPositive: false,
    },
  };
}

export function adaptPendingApprovalsKpi(
  summary: PendingApprovalsSummary,
): KpiAdapterResult {
  const { label, icon } = meta('pending_approvals_kpi');
  const { total, pending_leave_requests, pending_lesson_plans } = summary;
  const parts: string[] = [];
  if (pending_leave_requests > 0) {
    parts.push(`${pending_leave_requests} leave`);
  }
  if (pending_lesson_plans > 0) {
    parts.push(`${pending_lesson_plans} lesson plans`);
  }

  return {
    isEmpty: total === 0,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: formatInteger(total),
      delta: parts.length ? parts.join(' · ') : 'Nothing awaiting approval',
      deltaPositive: false,
    },
  };
}

const STATS_ADAPTERS: Record<
  Exclude<DashboardWidgetId, 'pending_approvals_kpi'>,
  (stats: AdminDashboardStats) => KpiAdapterResult
> = {
  enrollment_kpi: adaptEnrollmentKpi,
  attendance_kpi: adaptAttendanceKpi,
  collections_kpi: adaptCollectionsKpi,
  outstanding_fees_kpi: adaptOutstandingFeesKpi,
};

export function adaptKpiFromStats(
  widgetId: Exclude<DashboardWidgetId, 'pending_approvals_kpi'>,
  stats: AdminDashboardStats,
): KpiAdapterResult {
  return STATS_ADAPTERS[widgetId](stats);
}
