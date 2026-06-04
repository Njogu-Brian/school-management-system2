import { studentsApi } from '../api/students.api';
import type { ClassroomRecord } from '../types/student';
import type { StudentListFilters, StudentSummary } from '../types/student';
import {
  applyStudentClientFilters,
  buildStudentQueryParams,
  toStudentSummary,
} from './normalize';

export async function fetchClassrooms(): Promise<ClassroomRecord[]> {
  const res = await studentsApi.listClassrooms();
  if (!res.success || !res.data) {
    throw new Error(res.message || 'Failed to load classes.');
  }
  return res.data;
}

export function classroomLevelMap(
  classrooms: ClassroomRecord[],
): Map<number, number | string | null> {
  return new Map(classrooms.map((c) => [c.id, c.level ?? null]));
}

export async function fetchStudentListPage(
  filters: StudentListFilters,
  page: number,
  levelByClassroomId: Map<number, number | string | null>,
): Promise<{ items: StudentSummary[]; hasMore: boolean; total: number }> {
  const res = await studentsApi.list(buildStudentQueryParams(filters, page));
  if (!res.success || !res.data) {
    throw new Error(res.message || 'Failed to load students.');
  }

  const { data: rows, current_page, last_page, total } = res.data;
  let items = rows.map((raw) => {
    const classId = raw.classroom_id ?? raw.class_id;
    const level = classId != null ? levelByClassroomId.get(classId) ?? null : null;
    return toStudentSummary(raw, level);
  });

  items = applyStudentClientFilters(items, filters);

  return {
    items,
    hasMore: current_page < last_page,
    total,
  };
}
