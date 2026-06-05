import { useQuery } from '@tanstack/react-query';
import { fetchApprovalItems } from '../../approvals/fetchApprovals';
import type { ApprovalListFilters } from '../../types/approval';
import { queryKeys } from '../queryKeys';

export interface UseApprovalListOptions {
  filters?: ApprovalListFilters;
  enabled?: boolean;
  includeLeave?: boolean;
  includeLessonPlans?: boolean;
  includeAdmissions?: boolean;
}

export function useApprovalList(options?: UseApprovalListOptions) {
  const filters = options?.filters ?? { status: 'pending' };
  return useQuery({
    queryKey: queryKeys.approvals.list(filters),
    queryFn: () =>
      fetchApprovalItems(filters, {
        includeLeave: options?.includeLeave,
        includeLessonPlans: options?.includeLessonPlans,
        includeAdmissions: options?.includeAdmissions,
      }),
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}
