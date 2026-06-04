import { useQuery } from '@tanstack/react-query';
import { staffApi } from '../../api/staff.api';
import { toStaffDetail } from '../../staff/normalize';
import type { StaffDetail } from '../../types/staff';
import { queryKeys } from '../queryKeys';

export function useStaffDetail(staffId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.staff.detail(staffId),
    queryFn: async (): Promise<StaffDetail> => {
      const res = await staffApi.getById(staffId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load staff profile.');
      }
      return toStaffDetail(res.data);
    },
    enabled: options?.enabled !== false && staffId > 0,
    staleTime: 60_000,
  });
}
