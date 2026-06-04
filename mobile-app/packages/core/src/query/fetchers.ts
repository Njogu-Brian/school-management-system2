import { dashboardApi } from '../api/dashboard.api';
import type {
  AdminDashboardStats,
  DashboardStatsFilters,
  PendingApprovalsSummary,
} from '../types/dashboard';

export async function fetchAdminDashboardStats(
  filters?: DashboardStatsFilters,
): Promise<AdminDashboardStats> {
  const res = await dashboardApi.getStats(filters);
  if (!res.success || !res.data) {
    throw new Error(res.message || 'Failed to load dashboard statistics.');
  }
  return res.data;
}

export async function fetchPendingApprovalsSummary(): Promise<PendingApprovalsSummary> {
  return dashboardApi.getPendingApprovalsSummary();
}
