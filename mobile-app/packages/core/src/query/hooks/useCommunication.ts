import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { communicationApi } from '../../api/communication.api';
import { queryKeys } from '../queryKeys';

export function useAnnouncements(options?: { enabled?: boolean; perPage?: number }) {
  return useQuery({
    queryKey: queryKeys.communication.announcements(),
    queryFn: async () => {
      const res = await communicationApi.listAnnouncements({
        per_page: options?.perPage ?? 30,
        status: 'published',
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load announcements.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useCommunicationTemplates(options?: { enabled?: boolean; type?: string }) {
  return useQuery({
    queryKey: queryKeys.communication.templates(options?.type),
    queryFn: async () => {
      const res = await communicationApi.listTemplates({ type: options?.type ?? 'sms' });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load templates.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useCommunicationLogs(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.communication.logs(),
    queryFn: async () => {
      const res = await communicationApi.listLogs({ per_page: 30 });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load communication logs.');
      }
      return res.data.data ?? [];
    },
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useSendSms() {
  return useMutation({
    mutationFn: communicationApi.sendSms,
  });
}

export function useCreateAnnouncement() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: communicationApi.createAnnouncement,
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.communication.announcements() });
    },
  });
}
