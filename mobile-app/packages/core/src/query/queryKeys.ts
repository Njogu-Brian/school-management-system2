import type { DashboardStatsFilters } from '../types/dashboard';

/** Centralized TanStack Query keys for cache identity and invalidation. */
export const queryKeys = {
  dashboard: {
    all: ['dashboard'] as const,
    stats: (filters?: DashboardStatsFilters) =>
      [...queryKeys.dashboard.all, 'stats', filters ?? {}] as const,
    pendingApprovals: () => [...queryKeys.dashboard.all, 'pending-approvals'] as const,
  },
};
