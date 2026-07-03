import { useMutation, useQueryClient } from '@tanstack/react-query';
import { staffApi } from '../../api/staff.api';
import { queryKeys } from '../queryKeys';

export function useUpdateStaff(staffId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) => {
      const res = await staffApi.update(staffId, payload);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to update staff profile.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.staff.detail(staffId) });
      void qc.invalidateQueries({ queryKey: queryKeys.staff.all });
    },
  });
}
