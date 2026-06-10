import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
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

export function useWeeklyReportDetail(
  type: string,
  id: number,
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.reports.weeklyDetail(type, id),
    queryFn: async () => {
      const res = await reportsApi.getWeeklyReportDetail(type, id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load report.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0 && type.length > 0,
    staleTime: 60_000,
  });
}

export function useInfiniteExpenses(options?: {
  enabled?: boolean;
  status?: string;
  search?: string;
  perPage?: number;
}) {
  const perPage = options?.perPage ?? 25;
  return useInfiniteQuery({
    queryKey: queryKeys.reports.expensesList({
      status: options?.status,
      search: options?.search,
    }),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await reportsApi.listExpenses({
        per_page: perPage,
        page: pageParam as number,
        status: options?.status,
        search: options?.search,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load expenses.');
      }
      const page = res.data;
      return {
        items: page.data,
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last) => (last.hasMore ? last.currentPage + 1 : undefined),
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useExpense(id: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.reports.expenseDetail(id),
    queryFn: async () => {
      const res = await reportsApi.getExpense(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load expense.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0,
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
