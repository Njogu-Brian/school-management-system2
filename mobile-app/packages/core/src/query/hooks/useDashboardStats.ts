import { useQuery } from '@tanstack/react-query';
import type { DashboardStatsFilters } from '../../types/dashboard';
import { fetchAdminDashboardStats } from '../fetchers';
import { queryKeys } from '../queryKeys';

export interface UseDashboardStatsOptions {
  filters?: DashboardStatsFilters;
  enabled?: boolean;
}

export function useDashboardStats(options?: UseDashboardStatsOptions) {
  const filters = options?.filters;
  return useQuery({
    queryKey: queryKeys.dashboard.stats(filters),
    queryFn: () => fetchAdminDashboardStats(filters),
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}
