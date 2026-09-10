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

export const parentAbsenceApi = {
  async listReasonCodes(): Promise<ApiResponse<AttendanceReasonCodeDto[]>> {
    const { data } = await apiClient.get<ApiResponse<AttendanceReasonCodeDto[]>>('/attendance/reason-codes');
    return data as ApiResponse<AttendanceReasonCodeDto[]>;
  },

  async history(studentId: number): Promise<ApiResponse<ParentAbsenceRecordDto[]>> {
    const { data } = await apiClient.get<ApiResponse<ParentAbsenceRecordDto[]>>(
      `/students/${studentId}/attendance-absence`,
    );
    return data as ApiResponse<ParentAbsenceRecordDto[]>;
  },

  async report(
    studentId: number,
    payload: {
      start_date: string;
      end_date: string;
      reason: string;
      reason_code_id?: number | null;
    },
  ): Promise<ApiResponse<ParentAbsenceSubmitResult>> {
    const { data } = await apiClient.post<ApiResponse<ParentAbsenceSubmitResult>>(
      `/students/${studentId}/attendance-absence`,
      payload,
    );
    return data as ApiResponse<ParentAbsenceSubmitResult>;
  },
};
