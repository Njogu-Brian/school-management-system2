import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { approvalsApi } from '../../api/approvals.api';
import { queryKeys } from '../queryKeys';

export function useLeaveTypes(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['leave-types'] as const,
    queryFn: async () => {
      const res = await approvalsApi.listLeaveTypes();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load leave types.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 300_000,
  });
}

export function useCreateLeaveRequest() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Parameters<typeof approvalsApi.createLeaveRequest>[0]) => {
      const res = await approvalsApi.createLeaveRequest(payload);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to submit leave request.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.approvals.all });
      void qc.invalidateQueries({ queryKey: queryKeys.staff.all });
      void qc.invalidateQueries({ queryKey: queryKeys.dashboard.pendingApprovals() });
    },
  });
}
