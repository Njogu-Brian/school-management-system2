import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { parentFamilyFeesApi } from '../../api/parentFamilyFees.api';

const familyFeesKey = ['parent-family-fees'] as const;

export function useParentFamilyFees(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: familyFeesKey,
    queryFn: async () => {
      const res = await parentFamilyFeesApi.getSummary();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load family fees.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
  });
}

export function useParentFamilyFeesPrompt() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: { phone_number: string; amount: number; idempotency_key?: string }) => {
      const res = await parentFamilyFeesApi.prompt(payload);
      if (!res.success) {
        throw new Error(res.message || 'Failed to send M-Pesa prompt.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: familyFeesKey });
    },
  });
}
