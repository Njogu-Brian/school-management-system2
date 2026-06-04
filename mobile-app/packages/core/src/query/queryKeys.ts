import type { ApprovalListFilters } from '../types/approval';
import type { DashboardStatsFilters } from '../types/dashboard';

/** Centralized TanStack Query keys for cache identity and invalidation. */
export const queryKeys = {
  dashboard: {
    all: ['dashboard'] as const,
    stats: (filters?: DashboardStatsFilters) =>
      [...queryKeys.dashboard.all, 'stats', filters ?? {}] as const,
    pendingApprovals: () => [...queryKeys.dashboard.all, 'pending-approvals'] as const,
  },
  approvals: {
    all: ['approvals'] as const,
    list: (filters?: ApprovalListFilters) =>
      [...queryKeys.approvals.all, 'list', filters ?? {}] as const,
    detail: (id: string) => [...queryKeys.approvals.all, 'detail', id] as const,
  },
};
