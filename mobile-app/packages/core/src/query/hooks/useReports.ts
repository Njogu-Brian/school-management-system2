import { useQuery } from '@tanstack/react-query';
import { reportsApi } from '../../api/reports.api';
import { queryKeys } from '../queryKeys';

export function useWeeklyReports(options?: { enabled?: boolean; weekEnding?: string }) {
  return useQuery({
    queryKey: queryKeys.reports.weekly(options?.weekEnding),
    queryFn: async () => {
      const res = await reportsApi.listWeeklyReports({
        week_ending: options?.weekEnding,
        limit: 50,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load weekly reports.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useExpenseReportSummary(options?: {
  enabled?: boolean;
  fromDate?: string;
  toDate?: string;
}) {
  return useQuery({
    queryKey: queryKeys.reports.expenses({
      from: options?.fromDate,
      to: options?.toDate,
    }),
    queryFn: async () => {
      const res = await reportsApi.getExpenseSummary({
        from_date: options?.fromDate,
        to_date: options?.toDate,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load expense report.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useBoardPack(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.reports.boardPack(),
    queryFn: async () => {
      const res = await reportsApi.getBoardPack();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load board pack.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}
