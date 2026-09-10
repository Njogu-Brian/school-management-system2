import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { speedTestsApi, type SpeedTestBatch } from '../../api/speedTests.api';

export function useSpeedTests(filters?: { classroom_id?: number; student_id?: number; enabled?: boolean }) {
  return useQuery({
    queryKey: ['speed-tests', filters?.classroom_id ?? 0, filters?.student_id ?? 0] as const,
    enabled: filters?.enabled !== false,
    queryFn: async () => {
      const res = await speedTestsApi.list({
        classroom_id: filters?.classroom_id,
        student_id: filters?.student_id,
      });
      if (!res.success) throw new Error(res.message || 'Failed to load speed tests.');
      return res.data ?? [];
    },
    staleTime: 30_000,
  });
}

export function useSpeedTest(batchKey: string | undefined, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['speed-tests', 'detail', batchKey ?? ''] as const,
    enabled: (options?.enabled !== false) && Boolean(batchKey),
    queryFn: async () => {
      const res = await speedTestsApi.get(batchKey as string);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load speed test.');
      return res.data;
    },
  });
}

export function useCreateSpeedTest() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: {
      classroom_id: number;
      subject_id: number;
      question_count: number;
      max_marks: number;
      title?: string;
      assessment_date?: string;
    }) => {
      const res = await speedTestsApi.create(payload);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to create speed test.');
      return res.data as SpeedTestBatch;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['speed-tests'] });
      void qc.invalidateQueries({ queryKey: ['students'] });
    },
  });
}

export function useSaveSpeedTestMarks(batchKey: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (entries: Array<{ student_id: number; score: number | null }>) => {
      const res = await speedTestsApi.saveMarks(batchKey, entries);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to save marks.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['speed-tests'] });
      void qc.invalidateQueries({ queryKey: ['students'] });
    },
  });
}
