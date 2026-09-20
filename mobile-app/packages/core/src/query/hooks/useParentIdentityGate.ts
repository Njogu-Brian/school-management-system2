import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  parentIdentityGateApi,
  type ParentIdentityGate,
  type ParentIdentityUpdatePayload,
} from '../../api/parentIdentityGate.api';

const IDENTITY_GATE_KEY = ['parent', 'identity-gate'] as const;

export function useParentIdentityGate(options?: { enabled?: boolean }) {
  return useQuery<ParentIdentityGate>({
    queryKey: IDENTITY_GATE_KEY,
    enabled: options?.enabled !== false,
    queryFn: async () => {
      const res = await parentIdentityGateApi.get();
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load your details.');
      return res.data;
    },
    staleTime: 15_000,
  });
}

export function useUpdateParentIdentityGate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: ParentIdentityUpdatePayload) => {
      const res = await parentIdentityGateApi.update(payload);
      if (!res.success || !res.data) throw new Error(res.message || 'Could not save your details.');
      return res.data;
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: IDENTITY_GATE_KEY }),
  });
}
