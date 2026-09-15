import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export type AttendanceMarkStatus = 'present' | 'absent' | 'late' | 'unmarked';

export interface AttendanceReasonCode {
  id: number;
  code: string;
  name: string;
  requires_excuse?: boolean;
  is_medical?: boolean;
}

export interface AttendanceReasonFields {
  reason_code_id?: number | null;
  reason?: string | null;
  excuse_notes?: string | null;
}

export interface ClassAttendanceRow extends AttendanceReasonFields {
  student_id: number;
  status: AttendanceMarkStatus;
}

export interface MarkAttendancePayload {
  date: string;
  class_id: number;
  stream_id?: number | null;
  records: Array<{ student_id: number; status: AttendanceMarkStatus } & AttendanceReasonFields>;
}

export const attendanceApi = {
  getClassAttendance(params: {
    date: string;
    class_id: number;
    stream_id?: number | null;
  }): Promise<ApiResponse<ClassAttendanceRow[]>> {
    const query: Record<string, string | number> = {
      date: params.date,
      class_id: params.class_id,
    };
    if (params.stream_id != null) query.stream_id = params.stream_id;
    return apiClient.get<ClassAttendanceRow[]>('/attendance/class', query);
  },

  getSchoolDay(date: string): Promise<
    ApiResponse<{ date: string; is_school_day: boolean; is_future: boolean }>
  > {
    return apiClient.get('/attendance/school-day', { date });
  },

  getReasonCodes(): Promise<ApiResponse<AttendanceReasonCode[]>> {
    return apiClient.get<AttendanceReasonCode[]>('/attendance/reason-codes');
  },

  mark(payload: MarkAttendancePayload): Promise<ApiResponse<{ message: string; count: number }>> {
    return apiClient.post<{ message: string; count: number }>('/attendance/mark', payload);
  },

  markAbsent(payload: {
    date: string;
    student_ids: number[];
  } & AttendanceReasonFields): Promise<ApiResponse<{ message: string; count: number }>> {
    return apiClient.post<{ message: string; count: number }>('/attendance/mark-absent', payload);
  },
};
