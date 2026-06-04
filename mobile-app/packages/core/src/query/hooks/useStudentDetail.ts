import { useQuery } from '@tanstack/react-query';
import { studentsApi } from '../../api/students.api';
import { classroomLevelMap, fetchClassrooms } from '../../students/fetchStudents';
import { toStudentDetail } from '../../students/normalize';
import type { StudentDetail } from '../../types/student';
import { queryKeys } from '../queryKeys';

export function useStudentDetail(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.students.detail(studentId),
    queryFn: async (): Promise<StudentDetail> => {
      const [studentRes, classrooms] = await Promise.all([
        studentsApi.getById(studentId),
        fetchClassrooms().catch(() => []),
      ]);
      if (!studentRes.success || !studentRes.data) {
        throw new Error(studentRes.message || 'Failed to load student.');
      }
      const raw = studentRes.data;
      const classId = raw.classroom_id ?? raw.class_id;
      const levelMap = classroomLevelMap(classrooms);
      const level = classId != null ? levelMap.get(classId) ?? null : null;
      return toStudentDetail(raw, level);
    },
    enabled: options?.enabled !== false && studentId > 0,
    staleTime: 60_000,
  });
}
