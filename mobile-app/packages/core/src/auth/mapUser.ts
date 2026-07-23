import type { ApiUser, Branch, User } from '../types';
import { normalizeRole } from './roleUtils';

/** Map the backend snake_case user payload into the canonical `User` domain model. */
export function mapApiUser(raw: ApiUser): User {
  const branches: Branch[] | undefined = raw.branches?.map((b) => ({
    id: b.id,
    name: b.name,
    code: b.code ?? null,
    schoolId: b.school_id ?? null,
    isActive: b.is_active ?? true,
  }));

  return {
    id: raw.id,
    name: raw.name,
    email: raw.email ?? null,
    phone: raw.phone ?? null,
    avatarUrl: raw.avatar ?? null,
    role: normalizeRole(raw.role),
    roleName: raw.role ?? null,
    permissions: raw.permissions ?? [],
    schoolId: raw.school_id ?? null,
    branchId: raw.branch_id ?? (branches?.[0]?.id ?? null),
    branches,
    staffId: raw.staff_id ?? null,
    teacherId: raw.teacher_id ?? null,
    parentId: raw.parent_id ?? null,
    studentId: raw.student_id ?? null,
    classTeacherClassroomIds: raw.class_teacher_classroom_ids ?? [],
    assignedClassroomIds: raw.assigned_classroom_ids ?? [],
    assignedSubjectIds: raw.assigned_subject_ids ?? [],
  };
}
