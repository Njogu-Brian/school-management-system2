import { dashboardApi } from '../api/dashboard.api';
import { financeApi } from '../api/finance.api';
import type { FinanceDashboardKpis } from '../types/finance';

interface FinanceRoleStats {
  role?: string;
  collections_today?: number;
  collections_month?: number;
  pending_invoices?: number;
  overdue_invoices?: number;
  outstanding_balance?: number;
}

function todayIso(): string {
  return new Date().toISOString().slice(0, 10);
}

function monthStartIso(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
}

async function sumPaymentsInRange(dateFrom: string, dateTo: string): Promise<number> {
  const res = await financeApi.listPayments({
    date_from: dateFrom,
    date_to: dateTo,
    per_page: 100,
    active_only: true,
  });
  if (!res.success || !res.data) {
    return 0;
  }
  return (res.data.data ?? []).reduce((sum, p) => sum + (p.amount ?? 0), 0);
}

async function sumOutstandingFromInvoices(): Promise<number> {
  const res = await financeApi.listInvoices({ per_page: 100 });
  if (!res.success || !res.data) return 0;
  return (res.data.data ?? []).reduce((sum, inv) => sum + (inv.balance ?? 0), 0);
}

async function countStudentsInArrears(): Promise<number> {
  const res = await financeApi.listInvoices({ per_page: 100 });
  if (!res.success || !res.data) {
    return 0;
  }
  const ids = new Set<number>();
  for (const inv of res.data.data ?? []) {
    if ((inv.balance ?? 0) > 0) {
      ids.add(inv.student_id);
    }
  }
  return ids.size;
}

/** Prefer `GET /finance/summary`; fall back to composed KPIs. */
export async function fetchFinanceDashboardKpis(): Promise<FinanceDashboardKpis> {
  try {
    const summaryRes = await financeApi.getSummary();
    if (summaryRes.success && summaryRes.data) {
      const s = summaryRes.data;
      return {
        collectedToday: s.collected_today,
        collectedThisMonth: s.collected_this_month,
        outstandingFees: s.outstanding_balance,
        studentsInArrears: s.students_in_arrears,
        pendingReconciliation: s.pending_reconciliation,
      };
    }
  } catch {
    // Fall through to legacy composition.
  }

  const [statsRes, unassignedRes] = await Promise.all([
    dashboardApi.getStats(),
    financeApi.listTransactions({ view: 'unassigned', per_page: 1 }),
  ]);

  const stats = (statsRes.data ?? {}) as FinanceRoleStats & {
    fees_collected?: number;
    outstanding_balance?: number;
  };

  let collectedToday = 0;
  let collectedThisMonth = 0;
  let outstandingFees = 0;
  let studentsInArrears = 0;

  if (stats.role === 'finance') {
    collectedToday = stats.collections_today ?? 0;
    collectedThisMonth = stats.collections_month ?? 0;
    studentsInArrears = stats.overdue_invoices ?? stats.pending_invoices ?? 0;
    outstandingFees = await sumOutstandingFromInvoices();
  } else {
    collectedToday = await sumPaymentsInRange(todayIso(), todayIso());
    collectedThisMonth = await sumPaymentsInRange(monthStartIso(), todayIso());
    outstandingFees = stats.outstanding_balance ?? 0;
    studentsInArrears = await countStudentsInArrears();
  }

  const pendingReconciliation = unassignedRes.success ? (unassignedRes.data?.total ?? 0) : 0;

  return {
    collectedToday: Math.round(collectedToday * 100) / 100,
    collectedThisMonth: Math.round(collectedThisMonth * 100) / 100,
    outstandingFees: Math.round(outstandingFees * 100) / 100,
    studentsInArrears,
    pendingReconciliation,
  };
}
