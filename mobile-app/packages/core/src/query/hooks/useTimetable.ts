import { useQuery } from '@tanstack/react-query';
import { academicsApi } from '../../api/academics.api';
import type { TimetableRecord } from '../../types/academics';
import { queryKeys } from '../queryKeys';

async function fetchTeacherTimetable(staffId: number, termId?: number): Promise<TimetableRecord> {
  const res = await academicsApi.getTeacherTimetable(staffId, termId != null ? { term_id: termId } : undefined);
  if (!res.success || !res.data) {
    throw new Error(res.message || 'Failed to load timetable.');
  }
  return res.data;
}

async function fetchStudentTimetable(studentId: number, termId?: number): Promise<TimetableRecord> {
  const res = await academicsApi.getStudentTimetable(studentId, termId != null ? { term_id: termId } : undefined);
  if (!res.success || !res.data) {
    throw new Error(res.message || 'Failed to load timetable.');
  }
  return res.data;
}

export function useTeacherTimetable(staffId: number, options?: { termId?: number; enabled?: boolean }) {
  const enabled = (options?.enabled ?? true) && staffId > 0;
  return useQuery({
    queryKey: queryKeys.academics.teacherTimetable(staffId, options?.termId),
    queryFn: () => fetchTeacherTimetable(staffId, options?.termId),
    enabled,
    staleTime: 5 * 60_000,
  });
}

export function useMyTimetable(options?: { termId?: number; enabled?: boolean }) {
  const enabled = options?.enabled ?? true;
  return useQuery({
    queryKey: queryKeys.academics.myTimetable(options?.termId),
    queryFn: async () => {
      const res = await academicsApi.getMyTimetable(options?.termId != null ? { term_id: options.termId } : undefined);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load timetable.');
      }
      return res.data;
    },
    enabled,
    staleTime: 5 * 60_000,
  });
}

export function useClassTimetable(classroomId: number, options?: { termId?: number; enabled?: boolean }) {
  const enabled = (options?.enabled ?? true) && classroomId > 0;
  return useQuery({
    queryKey: queryKeys.academics.classTimetable(classroomId, options?.termId),
    queryFn: async () => {
      const res = await academicsApi.getClassTimetable(
        classroomId,
        options?.termId != null ? { term_id: options.termId } : undefined,
      );
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load timetable.');
      }
      return res.data;
    },
    enabled,
    staleTime: 5 * 60_000,
  });
}
export function useStudentTimetable(studentId: number, options?: { termId?: number; enabled?: boolean }) {
  const enabled = (options?.enabled ?? true) && studentId > 0;
  return useQuery({
    queryKey: queryKeys.academics.studentTimetable(studentId, options?.termId),
    queryFn: () => fetchStudentTimetable(studentId, options?.termId),
    enabled,
    staleTime: 5 * 60_000,
  });
}
