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

/** Subjects for a class — server scopes to "subjects you teach" for teacher-like roles. */
export function useClassroomSubjects(classId: number | null, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.students.classroomSubjects(classId ?? 0),
    queryFn: async () => {
      if (classId == null) return [];
      const { studentsApi } = await import('../../api/students.api');
      const res = await studentsApi.listClassroomSubjects(classId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load subjects.');
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

export function useInfiniteParentsContact(
  filters: { search?: string; classroomId?: number | null; streamId?: number | null },
  options?: { enabled?: boolean },
) {
  return useInfiniteQuery({
    queryKey: queryKeys.students.parentsContact(filters),
    queryFn: async ({ pageParam }) => {
      const { studentsApi } = await import('../../api/students.api');
      const res = await studentsApi.listParentsContact({
        search: filters.search,
        class_id: filters.classroomId ?? undefined,
        stream_id: filters.streamId ?? undefined,
        page: pageParam as number,
        per_page: 20,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load parent contacts.');
      }
      return res.data;
    },
    initialPageParam: 1,
    getNextPageParam: (lastPage) =>
      lastPage.current_page < lastPage.last_page ? lastPage.current_page + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useInfiniteArchivedStudents(
  filters: {
    search?: string;
    classroomId?: number | null;
    streamId?: number | null;
    termId?: number | null;
  },
  options?: { enabled?: boolean },
) {
  return useInfiniteQuery({
    queryKey: queryKeys.students.archived(filters),
    queryFn: async ({ pageParam }) => {
      const { studentsApi } = await import('../../api/students.api');
      const res = await studentsApi.listArchived({
        search: filters.search,
        class_id: filters.classroomId ?? undefined,
        stream_id: filters.streamId ?? undefined,
        term_id: filters.termId ?? undefined,
        page: pageParam as number,
        per_page: 20,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load archived students.');
      }
      return res.data;
    },
    initialPageParam: 1,
    getNextPageParam: (lastPage) =>
      lastPage.current_page < lastPage.last_page ? lastPage.current_page + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}
