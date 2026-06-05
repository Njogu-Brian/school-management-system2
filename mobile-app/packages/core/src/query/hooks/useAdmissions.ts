import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { admissionsApi } from '../../api/admissions.api';
import { normalizeApplicationDetail, normalizeApplicationSummary } from '../../admissions/normalize';
import type { ApplicationListFilters } from '../../types/admissions';
import { queryKeys } from '../queryKeys';

export function useAdmissionsStats(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.admissions.stats(),
    queryFn: async () => {
      const res = await admissionsApi.getStats();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load admissions stats.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useInfiniteApplicationList(
  filters: ApplicationListFilters,
  options?: { enabled?: boolean },
) {
  return useInfiniteQuery({
    queryKey: queryKeys.admissions.list(filters),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await admissionsApi.list({
        ...filters,
        page: pageParam as number,
        per_page: filters.per_page ?? 25,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load applications.');
      }
      const page = res.data;
      return {
        items: page.data.map(normalizeApplicationSummary),
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last, _pages, lastPageParam) =>
      last.hasMore ? (lastPageParam as number) + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useApplicationDetail(applicationId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.admissions.detail(applicationId),
    queryFn: async () => {
      const res = await admissionsApi.getById(applicationId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load application.');
      }
      return normalizeApplicationDetail(res.data);
    },
    enabled: options?.enabled !== false && applicationId > 0,
    staleTime: 60_000,
  });
}
