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

export function adaptPopulationAttendanceKpi(stats: AdminDashboardStats): KpiAdapterResult {
  const { label, icon } = meta('population_attendance_kpi');
  const total = stats.total_students ?? 0;
  const present = stats.present_today ?? 0;
  const absent = stats.absent_today ?? 0;
  const unmarked =
    stats.unmarked_today ?? Math.max(0, total - present - absent);
  const pct = total > 0 ? (present / total) * 100 : null;

  const admissionsToday = stats.admissions_today ?? 0;
  const lastAdmission = stats.last_admission;
  let admissionCaption: string | undefined;
  if (lastAdmission?.date) {
    const d = new Date(lastAdmission.date);
    const formatted = d.toLocaleDateString('en-KE', {
      day: 'numeric',
      month: 'numeric',
      year: 'numeric',
    });
    const count = lastAdmission.count ?? 1;
    const studentWord = count === 1 ? 'student' : 'students';
    admissionCaption = `Last admission ${formatted} · ${count} ${studentWord}`;
  } else if (admissionsToday > 0) {
    admissionCaption = `${formatInteger(admissionsToday)} joined today`;
  }

  const attendanceCaption =
    pct != null ? `${formatPercent(pct)} present` : 'Attendance today';

  return {
    isEmpty: total === 0,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: formatInteger(total),
      delta: admissionCaption ?? attendanceCaption,
      deltaPositive: pct != null ? pct >= 85 : true,
      stats: [
        { label: 'Present today', value: formatInteger(present) },
        { label: 'Absent today', value: formatInteger(absent) },
        { label: 'Unmarked today', value: formatInteger(unmarked) },
      ],
    },
  };
}

export function adaptCollectionsKpi(stats: AdminDashboardStats): KpiAdapterResult {
  const { label, icon } = meta('collections_kpi');
  const termCollected = stats.fees_collected ?? stats.collected_this_term ?? 0;
  const month = stats.collected_this_month;
  const week = stats.collected_this_week;
  const termName = stats.filters?.available_terms?.find(
    (t) => t.id === stats.filters?.term_id,
  )?.name;

  const parts: string[] = [];
  if (termName) parts.push(termName);
  if (week != null) parts.push(`Week ${formatKes(week)}`);
  if (month != null) parts.push(`Month ${formatKes(month)}`);

  return {
    isEmpty: termCollected === 0,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: formatKes(termCollected),
      delta: parts.length ? parts.join(' · ') : 'Current term (allocation-based)',
      deltaPositive: true,
    },
  };
}

export function adaptOutstandingFeesKpi(stats: AdminDashboardStats): KpiAdapterResult {
  const { label, icon } = meta('outstanding_fees_kpi');
  const balance = stats.outstanding_balance ?? 0;
  const invoiced = stats.total_invoiced ?? 0;
  const trend = chartPeriodTrend(stats.charts?.invoices, { invertPositive: true });
  const termName = stats.filters?.available_terms?.find(
    (t) => t.id === stats.filters?.term_id,
  )?.name;

  return {
    isEmpty: balance === 0,
    kpi: {
      label,
      icon: icon as KpiCardProps['icon'],
      value: formatKes(balance),
      delta:
        trend?.delta ??
        (termName
          ? `${termName} · Invoiced ${formatKes(invoiced)}`
          : 'Due invoices (current term)'),
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
  population_attendance_kpi: adaptPopulationAttendanceKpi,
  collections_kpi: adaptCollectionsKpi,
  outstanding_fees_kpi: adaptOutstandingFeesKpi,
};

export function adaptKpiFromStats(
  widgetId: Exclude<DashboardWidgetId, 'pending_approvals_kpi'>,
  stats: AdminDashboardStats,
): KpiAdapterResult {
  return STATS_ADAPTERS[widgetId](stats);
}
