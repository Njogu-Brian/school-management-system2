import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { auditApi, type AuditTrailFilters } from '../../api/audit.api';
import { queryKeys } from '../queryKeys';

export function useInfiniteAuditTrail(filters?: AuditTrailFilters) {
  return useInfiniteQuery({
    queryKey: queryKeys.audit.list(filters),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await auditApi.list({ ...filters, page: pageParam as number, per_page: 30 });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load audit trail.');
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
    staleTime: 60_000,
  });
}

export function useAuditTrailDetail(id: string) {
  return useQuery({
    queryKey: queryKeys.audit.detail(id),
    queryFn: async () => {
      const res = await auditApi.show(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Audit record not found.');
      }
      return res.data;
    },
    enabled: id.length > 0,
  });
}
