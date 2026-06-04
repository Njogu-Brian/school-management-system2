import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { fetchStaffFilterOptions, fetchStaffListPage } from '../../staff/fetchStaff';
import type { StaffListFilters } from '../../types/staff';
import { queryKeys } from '../queryKeys';

export function useStaffFilterOptions(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.staff.filterOptions(),
    queryFn: fetchStaffFilterOptions,
    enabled: options?.enabled !== false,
    staleTime: 10 * 60_000,
  });
}

export function useInfiniteStaffList(
  filters: StaffListFilters,
  options?: { enabled?: boolean },
) {
  return useInfiniteQuery({
    queryKey: queryKeys.staff.list(filters),
    queryFn: async ({ pageParam }) => fetchStaffListPage(filters, pageParam as number),
    initialPageParam: 1,
    getNextPageParam: (lastPage, _pages, lastPageParam) =>
      lastPage.hasMore ? (lastPageParam as number) + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}
