import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { homeworkApi } from '../../api/homework.api';

export function useHomeworkList(filters?: {
  classroom_id?: number;
  status?: string;
  search?: string;
  enabled?: boolean;
}) {
  return useQuery({
    queryKey: ['homework', 'list', filters?.classroom_id ?? 0, filters?.status ?? '', filters?.search ?? ''] as const,
    enabled: filters?.enabled !== false,
    queryFn: async () => {
      const res = await homeworkApi.list({
        classroom_id: filters?.classroom_id,
        status: filters?.status,
        search: filters?.search,
        per_page: 50,
      });
      if (!res.success) throw new Error(res.message || 'Failed to load homework.');
      return res.data?.data ?? [];
    },
    staleTime: 30_000,
  });
}

export function useHomeworkDetail(id: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['homework', 'detail', id] as const,
    enabled: (options?.enabled !== false) && id > 0,
    queryFn: async () => {
      const res = await homeworkApi.get(id);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load homework.');
      return res.data;
    },
  });
}

export function useCreateHomework() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Parameters<typeof homeworkApi.create>[0]) => {
      const res = await homeworkApi.create(payload);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to create homework.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['homework'] });
    },
  });
}
