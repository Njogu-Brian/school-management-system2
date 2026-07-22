import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { leaveTypesApi, staffAdvancesApi } from '../../api/staffAdvances.api';

export function useStaffAdvancesList(options?: {
  status?: string;
  enabled?: boolean;
}) {
  return useQuery({
    queryKey: ['staff-advances', options?.status ?? 'all'] as const,
    enabled: options?.enabled !== false,
    queryFn: async () => {
      const res = await staffAdvancesApi.list({
        status: options?.status,
        per_page: 50,
      });
      if (!res.success) throw new Error(res.message || 'Failed to load advances.');
      return res.data?.data ?? [];
    },
  });
}

export function useCreateStaffAdvance() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Parameters<typeof staffAdvancesApi.create>[0]) => {
      const res = await staffAdvancesApi.create(payload);
      if (!res.success) throw new Error(res.message || 'Failed to create advance.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['staff-advances'] });
    },
  });
}

export function useApproveStaffAdvance() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (args: {
      id: number;
      amount?: number;
      repayment_method?: string;
      installment_count?: number;
      monthly_deduction_amount?: number;
      notes?: string;
    }) => {
      const { id, ...payload } = args;
      const res = await staffAdvancesApi.approve(id, payload);
      if (!res.success) throw new Error(res.message || 'Failed to approve advance.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['staff-advances'] });
    },
  });
}

export function useRejectStaffAdvance() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (args: { id: number; reason: string }) => {
      const res = await staffAdvancesApi.reject(args.id, args.reason);
      if (!res.success) throw new Error(res.message || 'Failed to reject advance.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['staff-advances'] });
    },
  });
}

export function useLeaveTypesAdmin(options?: { includeInactive?: boolean; enabled?: boolean }) {
  return useQuery({
    queryKey: ['leave-types-admin', options?.includeInactive ? 1 : 0] as const,
    enabled: options?.enabled !== false,
    queryFn: async () => {
      const res = await leaveTypesApi.list(options?.includeInactive);
      if (!res.success) throw new Error(res.message || 'Failed to load leave types.');
      return res.data ?? [];
    },
  });
}

export function useCreateLeaveType() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Parameters<typeof leaveTypesApi.create>[0]) => {
      const res = await leaveTypesApi.create(payload);
      if (!res.success) throw new Error(res.message || 'Failed to create leave type.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['leave-types'] });
      void qc.invalidateQueries({ queryKey: ['leave-types-admin'] });
    },
  });
}

export function useAssignLeaveType() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Parameters<typeof leaveTypesApi.assign>[0]) => {
      const res = await leaveTypesApi.assign(payload);
      if (!res.success) throw new Error(res.message || 'Failed to assign leave type.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['staff'] });
    },
  });
}
