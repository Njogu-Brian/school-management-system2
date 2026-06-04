import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import {
  classroomLevelMap,
  fetchClassrooms,
  fetchStudentListPage,
} from '../../students/fetchStudents';
import type { StudentListFilters } from '../../types/student';
import { queryKeys } from '../queryKeys';

export function useClassrooms(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.students.classrooms(),
    queryFn: fetchClassrooms,
    enabled: options?.enabled !== false,
    staleTime: 5 * 60_000,
  });
}

export function useClassroomStreams(classId: number | null, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.students.streams(classId ?? 0),
    queryFn: async () => {
      if (classId == null) return [];
      const { studentsApi } = await import('../../api/students.api');
      const res = await studentsApi.listStreams(classId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load streams.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && classId != null,
    staleTime: 5 * 60_000,
  });
}

export function useInfiniteStudentList(
  filters: StudentListFilters,
  options?: { enabled?: boolean },
) {
  const classroomsQuery = useClassrooms({ enabled: options?.enabled !== false });

  return useInfiniteQuery({
    queryKey: queryKeys.students.list(filters),
    queryFn: async ({ pageParam }) => {
      const classrooms = classroomsQuery.data ?? [];
      const levelMap = classroomLevelMap(classrooms);
      return fetchStudentListPage(filters, pageParam as number, levelMap);
    },
    initialPageParam: 1,
    getNextPageParam: (lastPage, _pages, lastPageParam) =>
      lastPage.hasMore ? (lastPageParam as number) + 1 : undefined,
    enabled: options?.enabled !== false && classroomsQuery.isSuccess,
    staleTime: 45_000,
  });
}
