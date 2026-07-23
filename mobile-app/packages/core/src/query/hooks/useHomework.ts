import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { homeworkApi, type CreateHomeworkPayload } from '../../api/homework.api';

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
    mutationFn: async (payload: CreateHomeworkPayload) => {
      const res = await homeworkApi.create(payload);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to create homework.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['homework'] });
    },
  });
}

/** Per-student diary status for an assignment (creates a pending row if missing). */
export function useHomeworkDiaryStatus(
  assignmentId: number,
  studentId: number,
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: ['homework', 'diary-status', assignmentId, studentId] as const,
    enabled: (options?.enabled !== false) && assignmentId > 0 && studentId > 0,
    queryFn: async () => {
      const res = await homeworkApi.getStatus(assignmentId, studentId);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load status.');
      return res.data;
    },
    staleTime: 15_000,
  });
}

/** Teacher-facing completion roster for an assignment. */
export function useHomeworkDiaryRoster(assignmentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['homework', 'diary-roster', assignmentId] as const,
    enabled: (options?.enabled !== false) && assignmentId > 0,
    queryFn: async () => {
      const res = await homeworkApi.listDiary(assignmentId);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load roster.');
      return res.data;
    },
    staleTime: 15_000,
  });
}

/** Parent/guardian mark-as-done + undo actions for one assignment. */
export function useHomeworkCompletion(assignmentId: number) {
  const qc = useQueryClient();
  const invalidate = (studentId: number) => {
    void qc.invalidateQueries({ queryKey: ['homework', 'diary-status', assignmentId, studentId] });
    void qc.invalidateQueries({ queryKey: ['homework', 'diary-roster', assignmentId] });
  };

  const complete = useMutation({
    mutationFn: async (payload: { student_id: number; notes?: string }) => {
      const res = await homeworkApi.complete(assignmentId, payload);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to mark as done.');
      return res.data;
    },
    onSuccess: (_data, variables) => invalidate(variables.student_id),
  });

  const uncomplete = useMutation({
    mutationFn: async (payload: { student_id: number }) => {
      const res = await homeworkApi.uncomplete(assignmentId, payload);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to undo.');
      return res.data;
    },
    onSuccess: (_data, variables) => invalidate(variables.student_id),
  });

  return { complete, uncomplete };
}
