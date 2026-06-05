import { useQuery } from '@tanstack/react-query';
import { analyticsApi, type AnalyticsPeriod } from '../../api/analytics.api';
import { queryKeys } from '../queryKeys';

export function useExecutiveAnalytics(period: AnalyticsPeriod = 'month') {
  return useQuery({
    queryKey: queryKeys.analytics.executive(period),
    queryFn: async () => {
      const res = await analyticsApi.executive(period);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load analytics.');
      }
      return res.data;
    },
    staleTime: 120_000,
  });
}
