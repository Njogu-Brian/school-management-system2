import { useQuery } from '@tanstack/react-query';
import { fetchPendingApprovalsSummary } from '../fetchers';
import { queryKeys } from '../queryKeys';

export function usePendingApprovals(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.dashboard.pendingApprovals(),
    queryFn: fetchPendingApprovalsSummary,
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}
