import type {
  StudentDetail,
  StudentListFilters,
  StudentListQueryParams,
  StudentRecord,
  StudentSummary,
} from '../types/student';
import { mapEmergencyContact, mapGuardians, mapParentBlock } from './family';

export function toStudentSummary(
  raw: StudentRecord,
  gradeLevel?: number | string | null,
): StudentSummary {
  const classroomId = raw.classroom_id ?? raw.class_id ?? null;
  return {
    id: raw.id,
    admissionNumber: raw.admission_number ?? '',
    fullName: raw.full_name?.trim() || `${raw.first_name} ${raw.last_name}`.trim(),
    className: raw.class_name ?? null,
    streamName: raw.stream_name ?? null,
    classroomId,
    streamId: raw.stream_id ?? null,
    gender: (raw.gender ?? 'other').toLowerCase(),
    enrollmentStatus: (raw.status ?? 'active') as StudentSummary['enrollmentStatus'],
    feeStatus:
      raw.fee_status === 'pending' || raw.fee_status === 'cleared'
        ? raw.fee_status
        : null,
    avatarUrl: raw.avatar ?? null,
    gradeLevel: gradeLevel ?? null,
  };
}

export function toStudentDetail(raw: StudentRecord, gradeLevel?: number | string | null): StudentDetail {
  const summary = toStudentSummary(raw, gradeLevel);
  return {
    ...summary,
    dateOfBirth: raw.date_of_birth ?? null,
    phone: raw.phone ?? null,
    email: raw.email ?? null,
    admissionDate: raw.admission_date ?? null,
    enrollmentYear: raw.enrollment_year ?? null,
    address: raw.address ?? raw.residential_area ?? null,
    category: raw.category ?? null,
    nemisNumber: raw.nemis_number ?? null,
    outstandingBalance: raw.outstanding_balance ?? null,
    parent: mapParentBlock(raw.parent),
    guardians: mapGuardians(raw.guardians),
    emergencyContact: mapEmergencyContact(raw),
  };
}

export function buildStudentQueryParams(
  filters: StudentListFilters,
  page: number,
): StudentListQueryParams {
  const params: StudentListQueryParams = {
    page,
    per_page: filters.perPage ?? 25,
  };
  if (filters.search?.trim()) {
    params.search = filters.search.trim();
  }
  if (filters.classroomId != null) {
    params.class_id = filters.classroomId;
  }
  if (filters.streamId != null) {
    params.stream_id = filters.streamId;
  }
  return params;
}

export function applyStudentClientFilters(
  items: StudentSummary[],
  filters: StudentListFilters,
): StudentSummary[] {
  let result = items;

  if (filters.gender && filters.gender !== 'all') {
    result = result.filter((s) => s.gender === filters.gender);
  }

  if (filters.status && filters.status !== 'all') {
    if (filters.status === 'active') {
      result = result.filter((s) => s.enrollmentStatus === 'active');
    } else if (filters.status === 'fee_pending') {
      result = result.filter((s) => s.feeStatus === 'pending');
    } else if (filters.status === 'fee_cleared') {
      result = result.filter((s) => s.feeStatus === 'cleared');
    }
  }

  if (filters.gradeLevel != null && filters.gradeLevel !== '' && filters.classroomId == null) {
    result = result.filter(
      (s) => s.gradeLevel != null && String(s.gradeLevel) === String(filters.gradeLevel),
    );
  }

  return result;
}
