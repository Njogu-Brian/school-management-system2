import { useInfiniteQuery } from '@tanstack/react-query';
import { payrollApi } from '../../api/payroll.api';

export function usePayrollRecordsList(options?: { enabled?: boolean; staffId?: number }) {
  return useInfiniteQuery({
    queryKey: ['payroll-records', 'list', options?.staffId ?? 'all'] as const,
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await payrollApi.list({
        staff_id: options?.staffId,
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
