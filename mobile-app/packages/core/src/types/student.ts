/** Raw student shape from `GET /students` and `GET /students/{id}`. */
export interface StudentRecord {
  id: number;
  admission_number: string;
  first_name: string;
  last_name: string;
  middle_name?: string | null;
  full_name: string;
  date_of_birth?: string;
  gender?: string;
  class_id?: number;
  classroom_id?: number;
  stream_id?: number | null;
  class_name?: string | null;
  stream_name?: string | null;
  status?: string;
  category?: string | null;
  avatar?: string | null;
  phone?: string | null;
  email?: string | null;
  fee_status?: 'cleared' | 'pending' | string;
  outstanding_balance?: number | null;
  admission_date?: string | null;
  enrollment_year?: string | number | null;
  address?: string | null;
  residential_area?: string | null;
  religion?: string | null;
  nemis_number?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface ClassroomRecord {
  id: number;
  name: string;
  level?: number | string | null;
  code?: string | null;
}

export interface StreamRecord {
  id: number;
  name: string;
  class_id: number;
}

/** List row model (registry). */
export interface StudentSummary {
  id: number;
  admissionNumber: string;
  fullName: string;
  className: string | null;
  streamName: string | null;
  classroomId: number | null;
  streamId: number | null;
  gender: string;
  enrollmentStatus: 'active' | 'archived' | string;
  feeStatus: 'cleared' | 'pending' | null;
  avatarUrl: string | null;
  gradeLevel: number | string | null;
}

/** Detail header model (no 360 tabs in Batch 1). */
export interface StudentDetail extends StudentSummary {
  dateOfBirth: string | null;
  phone: string | null;
  email: string | null;
  admissionDate: string | null;
  enrollmentYear: string | number | null;
  address: string | null;
  category: string | null;
  nemisNumber: string | null;
  outstandingBalance: number | null;
}

export type StudentEnrollmentStatusFilter = 'all' | 'active' | 'fee_pending' | 'fee_cleared';

export type StudentGenderFilter = 'all' | 'male' | 'female' | 'other';

export interface StudentListFilters {
  search?: string;
  classroomId?: number | null;
  streamId?: number | null;
  /** Matches `ClassroomRecord.level` — narrows class picker; client-filter when no class selected. */
  gradeLevel?: number | string | null;
  status?: StudentEnrollmentStatusFilter;
  gender?: StudentGenderFilter;
  perPage?: number;
}

export interface StudentListQueryParams {
  search?: string;
  class_id?: number;
  stream_id?: number;
  page?: number;
  per_page?: number;
}
