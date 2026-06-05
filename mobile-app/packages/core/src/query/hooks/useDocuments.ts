import { useQuery } from '@tanstack/react-query';
import { documentsApi } from '../../api/documents.api';
import { queryKeys } from '../queryKeys';

export function useStudentDocuments(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.documents.student(studentId),
    queryFn: async () => {
      const res = await documentsApi.listStudentDocuments(studentId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load student documents.');
      }
      return res.data.data ?? [];
    },
    enabled: (options?.enabled !== false) && studentId > 0,
    staleTime: 60_000,
  });
}

export function useStaffDocuments(staffId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.documents.staff(staffId),
    queryFn: async () => {
      const res = await documentsApi.listStaffDocuments(staffId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load staff documents.');
      }
      return res.data.data ?? [];
    },
    enabled: (options?.enabled !== false) && staffId > 0,
    staleTime: 60_000,
  });
}
