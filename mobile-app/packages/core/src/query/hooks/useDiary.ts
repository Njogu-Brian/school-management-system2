import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { diaryApi, type DiaryChannel } from '../../api/diary.api';

export function useDiaryThreads(options?: {
  enabled?: boolean;
  search?: string;
  channel?: DiaryChannel;
}) {
  const channel = options?.channel ?? 'teacher_parent';
  return useQuery({
    queryKey: ['diaries', 'list', channel, options?.search ?? ''] as const,
    queryFn: async () => {
      const res = await diaryApi.list({
        search: options?.search,
        channel,
        per_page: 50,
      });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load diaries.');
      return res.data.data ?? [];
    },
    enabled: options?.enabled !== false,
    staleTime: 15_000,
  });
}

export function useDiaryThread(
  studentId: number,
  options?: { enabled?: boolean; channel?: DiaryChannel },
) {
  const channel = options?.channel ?? 'teacher_parent';
  return useQuery({
    queryKey: ['diaries', 'student', studentId, channel] as const,
    queryFn: async () => {
      const res = await diaryApi.getForStudent(studentId, channel);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load diary.');
      return res.data;
    },
    enabled: (options?.enabled !== false) && studentId > 0,
    staleTime: 5_000,
  });
}

export function useSendDiaryMessage(studentId: number, channel: DiaryChannel = 'teacher_parent') {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (input: {
      content: string;
      attachments?: { uri: string; name: string; type: string }[];
    }) => {
      const res = await diaryApi.sendMessage(
        studentId,
        { content: input.content, channel },
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
