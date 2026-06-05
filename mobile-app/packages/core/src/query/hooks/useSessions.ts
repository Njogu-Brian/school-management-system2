import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { sessionsApi } from '../../api/sessions.api';
import { queryKeys } from '../queryKeys';

export function useActiveSessions() {
  return useQuery({
    queryKey: queryKeys.sessions.list(),
    queryFn: async () => {
      const res = await sessionsApi.list();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load sessions.');
      }
      return res.data;
    },
    staleTime: 30_000,
  });
}

export function useRevokeSession() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (tokenId: number) => sessionsApi.revoke(tokenId),
    onSuccess: () => void qc.invalidateQueries({ queryKey: queryKeys.sessions.all }),
  });
}

export function useRevokeOtherSessions() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => sessionsApi.revokeOthers(),
    onSuccess: () => void qc.invalidateQueries({ queryKey: queryKeys.sessions.all }),
  });
}

export function useRefreshToken() {
  return useMutation({
    mutationFn: () => sessionsApi.refresh(),
  });
}
