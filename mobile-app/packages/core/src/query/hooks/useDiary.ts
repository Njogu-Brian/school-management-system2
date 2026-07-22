import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { diaryApi } from '../../api/diary.api';

export function useDiaryThreads(options?: { enabled?: boolean; search?: string }) {
  return useQuery({
    queryKey: ['diaries', 'list', options?.search ?? ''] as const,
    queryFn: async () => {
      const res = await diaryApi.list({ search: options?.search, per_page: 50 });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load diaries.');
      return res.data.data ?? [];
    },
    enabled: options?.enabled !== false,
    staleTime: 15_000,
  });
}

export function useDiaryThread(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['diaries', 'student', studentId] as const,
    queryFn: async () => {
      const res = await diaryApi.getForStudent(studentId);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load diary.');
      return res.data;
    },
    enabled: (options?.enabled !== false) && studentId > 0,
    staleTime: 5_000,
  });
}

export function useSendDiaryMessage(studentId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (input: {
      content: string;
      attachments?: { uri: string; name: string; type: string }[];
    }) => {
      const res = await diaryApi.sendMessage(
        studentId,
        { content: input.content },
        input.attachments,
      );
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to send message.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['diaries'] });
    },
  });
}
