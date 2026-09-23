import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export type AttendanceReasonCodeDto = {
  id: number;
  code: string;
  name: string;
  description?: string | null;
  requires_excuse?: boolean;
  is_medical?: boolean;
};

export type ParentAbsenceRecordDto = {
  id: number;
  student_id: number;
  date: string;
  status: string;
  is_excused: boolean;
  reason?: string | null;
  excuse_notes?: string | null;
  reason_code_id?: number | null;
  reason_code?: string | null;
  marked_by?: number | null;
  marked_at?: string | null;
};

export type ParentAbsenceSubmitResult = {
  school_days: number;
  skipped: string[];
  records: ParentAbsenceRecordDto[];
};

/**
 * `apiClient.get/post` already return the unwrapped JSON body (`ApiResponse<T>`).
 * Do not destructure `{ data }` again — that drops `success` and breaks hooks.
 */
export const parentAbsenceApi = {
  listReasonCodes(): Promise<ApiResponse<AttendanceReasonCodeDto[]>> {
    return apiClient.get<AttendanceReasonCodeDto[]>('/attendance/reason-codes');
  },

  history(studentId: number): Promise<ApiResponse<ParentAbsenceRecordDto[]>> {
    return apiClient.get<ParentAbsenceRecordDto[]>(`/students/${studentId}/attendance-absence`);
  },

  report(
    studentId: number,
    payload: {
      start_date: string;
      end_date: string;
      reason: string;
      reason_code_id?: number | null;
    },
  ): Promise<ApiResponse<ParentAbsenceSubmitResult>> {
    return apiClient.post<ParentAbsenceSubmitResult>(
      `/students/${studentId}/attendance-absence`,
      payload,
    );
  },
};
