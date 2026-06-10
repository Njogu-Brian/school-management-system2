import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { communicationApi } from '../../api/communication.api';
import { queryKeys } from '../queryKeys';

export function useAnnouncements(options?: { enabled?: boolean; perPage?: number; page?: number }) {
  return useQuery({
    queryKey: queryKeys.communication.announcements(options?.page),
    queryFn: async () => {
      const res = await communicationApi.listAnnouncements({
        per_page: options?.perPage ?? 30,
        page: options?.page ?? 1,
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

export function useInfiniteAnnouncements(options?: { enabled?: boolean; perPage?: number }) {
  const perPage = options?.perPage ?? 25;
  return useInfiniteQuery({
    queryKey: queryKeys.communication.announcements('infinite'),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await communicationApi.listAnnouncements({
        per_page: perPage,
        page: pageParam as number,
        status: 'published',
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load announcements.');
      }
      const page = res.data;
      return {
        items: page.data,
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last) => (last.hasMore ? last.currentPage + 1 : undefined),
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useAnnouncement(id: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.communication.announcement(id),
    queryFn: async () => {
      const res = await communicationApi.getAnnouncement(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load announcement.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0,
    staleTime: 30_000,
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

export function useCommunicationTemplate(id: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.communication.template(id),
    queryFn: async () => {
      const res = await communicationApi.getTemplate(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load template.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0,
    staleTime: 120_000,
  });
}

export function useCommunicationLog(id: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.communication.log(id),
    queryFn: async () => {
      const res = await communicationApi.getLog(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load message log.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0,
    staleTime: 60_000,
  });
}

export function useSmsRecipients(options?: { enabled?: boolean; classroomId?: number }) {
  return useQuery({
    queryKey: queryKeys.communication.recipients(options?.classroomId),
    queryFn: async () => {
      const res = await communicationApi.listRecipients(
        options?.classroomId ? { classroom_id: options.classroomId } : undefined,
      );
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load recipients.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useCommunicationLogs(options?: {
  enabled?: boolean;
  channel?: string;
  status?: string;
  perPage?: number;
}) {
  return useQuery({
    queryKey: queryKeys.communication.logs({ channel: options?.channel, status: options?.status }),
    queryFn: async () => {
      const res = await communicationApi.listLogs({
        per_page: options?.perPage ?? 30,
        channel: options?.channel,
        status: options?.status,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load communication logs.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useInfiniteCommunicationLogs(options?: {
  enabled?: boolean;
  channel?: string;
  status?: string;
  perPage?: number;
}) {
  const perPage = options?.perPage ?? 25;
  return useInfiniteQuery({
    queryKey: queryKeys.communication.logs({
      channel: options?.channel,
      status: options?.status,
      infinite: true,
    }),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await communicationApi.listLogs({
        per_page: perPage,
        page: pageParam as number,
        channel: options?.channel,
        status: options?.status,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load communication logs.');
      }
      const page = res.data;
      return {
        items: page.data,
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last) => (last.hasMore ? last.currentPage + 1 : undefined),
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useSendSms() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: communicationApi.sendSms,
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.communication.logs() });
    },
  });
}

export function useSendWhatsApp() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: communicationApi.sendWhatsApp,
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.communication.logs() });
    },
  });
}

export function useCreateTemplate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: communicationApi.createTemplate,
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.communication.all });
    },
  });
}

export function useUpdateTemplate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({
      id,
      ...payload
    }: {
      id: number;
      title: string;
      type: string;
      code?: string | null;
      subject?: string | null;
      content: string;
    }) => communicationApi.updateTemplate(id, payload),
    onSuccess: (_data, vars) => {
      void qc.invalidateQueries({ queryKey: queryKeys.communication.all });
      void qc.invalidateQueries({ queryKey: queryKeys.communication.template(vars.id) });
    },
  });
}

export function useDeleteTemplate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => communicationApi.deleteTemplate(id),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.communication.all });
    },
  });
}

export function useCreateAnnouncement() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: communicationApi.createAnnouncement,
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.communication.all });
    },
  });
}

export function useUpdateAnnouncement() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, ...payload }: { id: number; title: string; content: string; active: boolean; expires_at?: string | null }) =>
      communicationApi.updateAnnouncement(id, payload),
    onSuccess: (_data, vars) => {
      void qc.invalidateQueries({ queryKey: queryKeys.communication.all });
      void qc.invalidateQueries({ queryKey: queryKeys.communication.announcement(vars.id) });
    },
  });
}

export function useDeleteAnnouncement() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => communicationApi.deleteAnnouncement(id),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.communication.all });
    },
  });
}
