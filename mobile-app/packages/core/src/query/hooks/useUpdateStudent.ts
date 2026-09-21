import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { studentsApi } from '../../api/students.api';
import { queryKeys } from '../queryKeys';
import { errorMessage } from '../../utils/errors';

export function useStudentCategories(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: [...queryKeys.students.all, 'categories'] as const,
    queryFn: async () => {
      const res = await studentsApi.listCategories();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load student categories.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 300_000,
  });
}

export function useUpdateStudent(studentId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) => {
      try {
        const res = await studentsApi.update(studentId, payload);
        if (!res.success || !res.data) {
          throw new Error(res.message || 'Failed to update student.');
        }
        return res.data;
      } catch (err) {
        throw new Error(errorMessage(err, 'Failed to update student.'));
      }
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.students.detail(studentId) });
      void qc.invalidateQueries({ queryKey: queryKeys.students.all });
    },
  });
}
