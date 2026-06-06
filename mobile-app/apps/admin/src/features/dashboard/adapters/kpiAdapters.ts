import type {
  AdminDashboardStats,
  DashboardChartSeries,
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

function chartPeriodTrend(
  series: DashboardChartSeries | undefined,
  options?: { invertPositive?: boolean },
): { delta: string; deltaPositive: boolean } | null {
  const values = series?.values;
  if (!values || values.length < 2) return null;

  const prev = values[values.length - 2];
  const curr = values[values.length - 1];
  if (prev === 0 && curr === 0) return null;

  if (prev === 0) {
    return {
      delta: `↑ ${formatInteger(curr)}`,
      deltaPositive: !options?.invertPositive,
    };
  }

  const pct = ((curr - prev) / Math.abs(prev)) * 100;
  const arrow = pct >= 0 ? '↑' : '↓';
  const positive = options?.invertPositive ? pct <= 0 : pct >= 0;
  return {
    delta: `${arrow} ${Math.abs(pct).toFixed(1)}%`,
    deltaPositive: positive,
  };
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
  const trend = chartPeriodTrend(stats.charts?.enrollment);

  return {
    isEmpty: total === 0,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: formatInteger(total),
      delta: trend?.delta ?? 'Active students',
      deltaPositive: trend?.deltaPositive ?? true,
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
  const trend = chartPeriodTrend(stats.charts?.payments);

  return {
    isEmpty: false,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: formatKes(collected),
      delta:
        trend?.delta ??
        (collectionRate != null
          ? `${collectionRate}% of invoiced${scope ? ` · ${scope}` : ''}`
          : scope ?? 'Scoped collections'),
      deltaPositive: trend?.deltaPositive ?? (collectionRate == null || collectionRate >= 50),
    },
  };
}

export function adaptOutstandingFeesKpi(stats: AdminDashboardStats): KpiAdapterResult {
  const { label, icon } = meta('outstanding_fees_kpi');
  const balance = stats.outstanding_balance ?? 0;
  const invoiced = stats.total_invoiced ?? 0;
  const scope = termScopeCaption(stats);
  const trend = chartPeriodTrend(stats.charts?.invoices, { invertPositive: true });

  return {
    isEmpty: balance === 0 && invoiced === 0,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: formatKes(balance),
      delta: trend?.delta ?? (scope ? `Balance · ${scope}` : 'Invoice balance'),
      deltaPositive: trend?.deltaPositive ?? false,
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
