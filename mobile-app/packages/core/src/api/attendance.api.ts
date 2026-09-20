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

  getReport(params: AttendanceReportParams): Promise<ApiResponse<AttendanceReportPage>> {
    const query: Record<string, string | number> = { date: params.date };
    if (params.status) query.status = params.status;
    if (params.classroom_id != null) query.classroom_id = params.classroom_id;
    if (params.stream_id != null) query.stream_id = params.stream_id;
    if (params.search) query.search = params.search;
    if (params.page != null) query.page = params.page;
    if (params.per_page != null) query.per_page = params.per_page;
    return apiClient.get<AttendanceReportPage>('/attendance/report', query);
  },

  getConsecutive(params: ConsecutiveAbsenceParams): Promise<ApiResponse<ConsecutiveAbsencePage>> {
    const query: Record<string, string | number> = {};
    if (params.date) query.date = params.date;
    if (params.threshold != null) query.threshold = params.threshold;
    if (params.classroom_id != null) query.classroom_id = params.classroom_id;
    if (params.stream_id != null) query.stream_id = params.stream_id;
    if (params.search) query.search = params.search;
    if (params.page != null) query.page = params.page;
    if (params.per_page != null) query.per_page = params.per_page;
    return apiClient.get<ConsecutiveAbsencePage>('/attendance/consecutive', query);
  },

  markStudents(payload: MarkStudentsPayload): Promise<ApiResponse<{ message: string; count: number }>> {
    return apiClient.post<{ message: string; count: number }>('/attendance/mark-students', payload);
  },
};

export type AttendanceReportStatus = 'all' | 'present' | 'absent' | 'late' | 'unmarked';

export interface AttendanceReportParams {
  date: string;
  status?: AttendanceReportStatus;
  classroom_id?: number | null;
  stream_id?: number | null;
  search?: string;
  page?: number;
  per_page?: number;
}

export interface AttendanceReportSummary {
  total: number;
  present: number;
  absent: number;
  late: number;
  unmarked: number;
}

export interface AttendanceReportRow extends AttendanceReasonFields {
  student_id: number;
  full_name: string;
  admission_number: string;
  classroom_id?: number | null;
  classroom_name?: string | null;
  stream_id?: number | null;
  stream_name?: string | null;
  status: AttendanceMarkStatus;
  is_excused?: boolean;
  consecutive_absences?: number;
}

export interface AttendanceReportPage {
  date: string;
  is_school_day: boolean;
  is_future: boolean;
  summary: AttendanceReportSummary;
  data: AttendanceReportRow[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number | null;
  to?: number | null;
}

export interface ConsecutiveAbsenceParams {
  date?: string;
  threshold?: number;
  classroom_id?: number | null;
  stream_id?: number | null;
  search?: string;
  page?: number;
  per_page?: number;
}

export interface ConsecutiveAbsencePage {
  date: string;
  threshold: number;
  data: AttendanceReportRow[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number | null;
  to?: number | null;
}

export interface MarkStudentsPayload {
  date: string;
  records: Array<{ student_id: number; status: AttendanceMarkStatus } & AttendanceReasonFields>;
}
