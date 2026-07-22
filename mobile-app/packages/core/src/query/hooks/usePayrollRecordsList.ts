import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { payrollApi } from '../../api/payroll.api';

export function usePayrollRecordsList(options?: {
  enabled?: boolean;
  staffId?: number;
  month?: string | null;
}) {
  const month = options?.month ?? null;
  return useInfiniteQuery({
    queryKey: ['payroll-records', 'list', options?.staffId ?? 'all', month ?? 'all'] as const,
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await payrollApi.list({
        staff_id: options?.staffId,
        month: month || undefined,
        page: pageParam as number,
        per_page: 25,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load payroll records.');
      }
      const page = res.data;
      return {
        items: page.data,
        currentPage: page.current_page,
        lastPage: page.last_page,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last, _pages, lastPageParam) =>
      last.hasMore ? (lastPageParam as number) + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function usePayrollRecordDetail(recordId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['payroll-records', 'detail', recordId] as const,
    queryFn: async () => {
      const res = await payrollApi.getById(recordId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load payroll detail.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && recordId > 0,
    staleTime: 60_000,
  });
}
