import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { concernsApi, type ConcernCategory } from '../../api/concerns.api';

export function useConcernsList(options?: {
  status?: string;
  category?: string;
  search?: string;
  enabled?: boolean;
}) {
  return useQuery({
    queryKey: ['concerns', options?.status, options?.category, options?.search] as const,
    enabled: options?.enabled !== false,
    queryFn: async () => {
      const res = await concernsApi.list({
        status: options?.status,
        category: options?.category,
        search: options?.search,
      });
      if (!res.success) throw new Error(res.message || 'Failed to load concerns.');
      return res.data?.data ?? [];
    },
  });
}

export function useConcernDetail(id: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['concerns', id] as const,
    enabled: options?.enabled !== false && id > 0,
    queryFn: async () => {
      const res = await concernsApi.get(id);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load concern.');
      return res.data;
    },
  });
}

export function useCreateConcern() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: {
      student_id: number;
      category: ConcernCategory | string;
      description: string;
      staff_ids?: number[];
    }) => {
      const res = await concernsApi.create(payload);
      if (!res.success) throw new Error(res.message || 'Failed to create concern.');
      return res.data;
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['concerns'] }),
  });
}

export function useUpdateConcern() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (args: {
      id: number;
      status?: string;
      description?: string;
      staff_ids?: number[];
    }) => {
      const { id, ...payload } = args;
      const res = await concernsApi.update(id, payload);
      if (!res.success) throw new Error(res.message || 'Failed to update concern.');
      return res.data;
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['concerns'] }),
  });
}
