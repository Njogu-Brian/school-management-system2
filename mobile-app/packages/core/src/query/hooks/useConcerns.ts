import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { concernsApi, type ConcernCategory, type CreateConcernPayload } from '../../api/concerns.api';

export function useConcernsList(options?: {
  status?: string;
  category?: string;
  search?: string;
  staffId?: number;
  enabled?: boolean;
}) {
  return useQuery({
    queryKey: [
      'concerns',
      options?.status,
      options?.category,
      options?.search,
      options?.staffId,
    ] as const,
    enabled: options?.enabled !== false,
    queryFn: async () => {
      const res = await concernsApi.list({
        status: options?.status,
        category: options?.category,
        search: options?.search,
        staff_id: options?.staffId,
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
    mutationFn: async (payload: CreateConcernPayload) => {
      const res = await concernsApi.create(payload);
      if (!res.success) throw new Error(res.message || 'Failed to create concern.');
      return res.data;
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['concerns'] }),
  });
}

/** Searchable staff picker for raising concerns (works for all roles). */
export function useConcernStaffOptions(search: string, options?: { enabled?: boolean }) {
  const q = search.trim();
  return useQuery({
    queryKey: ['concerns', 'staff-options', q] as const,
    enabled: (options?.enabled !== false) && q.length >= 2,
    queryFn: async () => {
      const res = await concernsApi.staffOptions(q);
      if (!res.success) throw new Error(res.message || 'Failed to search staff.');
      return (res.data ?? []).map((row) => ({
        id: row.id,
        fullName: row.full_name,
        employeeNumber: row.employee_number ?? null,
        jobTitle: row.job_title ?? null,
      }));
    },
    staleTime: 30_000,
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

export type { ConcernCategory };
