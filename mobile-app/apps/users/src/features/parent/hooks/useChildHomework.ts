import {
  apiClient,
  useStudentDetail,
  type ApiResponse,
  type HomeworkAttachment,
  type PaginatedResponse,
} from '@erp/core';
import { useQuery } from '@tanstack/react-query';

export type HomeworkAssignment = {
  id: number;
  title: string;
  description?: string;
  instructions?: string | null;
  subject_name?: string | null;
  class_name?: string | null;
  teacher_name?: string | null;
  due_date?: string | null;
  status?: string;
  total_marks?: number;
  max_score?: number | null;
  attachments?: HomeworkAttachment[] | null;
};

export function useChildHomework(studentId: number, options?: { enabled?: boolean }) {
  const detail = useStudentDetail(studentId, {
    enabled: (options?.enabled !== false) && studentId > 0,
  });
  const classroomId = detail.data?.classroomId ?? null;

  const homeworkQuery = useQuery({
    queryKey: ['assignments', 'homework', studentId, classroomId] as const,
    queryFn: async (): Promise<HomeworkAssignment[]> => {
      const params: Record<string, string | number> = { per_page: 50 };
      if (classroomId != null) params.classroom_id = classroomId;
      const res = await apiClient.get<PaginatedResponse<HomeworkAssignment>>('/assignments', params);
      const typed = res as ApiResponse<PaginatedResponse<HomeworkAssignment>>;
      if (!typed.success || !typed.data) {
        throw new Error(typed.message || 'Failed to load homework.');
      }
      return typed.data.data ?? [];
    },
    enabled: (options?.enabled !== false) && studentId > 0 && !detail.isLoading,
    staleTime: 45_000,
  });

  return {
    ...homeworkQuery,
    studentName: detail.data?.fullName ?? null,
    classroomId,
    detailLoading: detail.isLoading,
    detailError: detail.isError,
  };
}
