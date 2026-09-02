import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { usersApi } from '../../api/users.api';
import { queryKeys } from '../queryKeys';

export function usePasswordChangeTargets(
  filters?: { group?: 'staff' | 'parents' | 'all'; q?: string; page?: number },
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.users.passwordTargets(filters),
    queryFn: async () => {
      const res = await usersApi.passwordChangeTargets(filters);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load users.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 20_000,
  });
}

export function useRequirePasswordChange() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Parameters<typeof usersApi.requirePasswordChange>[0]) => {
      const res = await usersApi.requirePasswordChange(payload);
      if (!res.success) {
        throw new Error(res.message || 'Could not require password change.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.users.all });
    },
  });
}
