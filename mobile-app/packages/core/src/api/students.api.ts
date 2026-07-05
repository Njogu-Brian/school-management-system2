import type {
  AttendanceCalendarDay,
  StudentFinanceSearchResult,
  StudentStatementRecord,
  StudentStatsRecord,
} from '../types/student360';
import type {
  ClassroomRecord,
  StreamRecord,
  StudentListQueryParams,
  StudentRecord,
} from '../types/student';
import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

/**
 * Student registry API — reuses existing Laravel routes.
 *
 * - `GET /students` — paginated list (`search`, `class_id`/`classroom_id`, `stream_id`)
 * - `GET /students/{id}` — detail
 * - `GET /classes` — classroom filter options
 * - `GET /classes/{id}/streams` — stream filter options
 */
export const studentsApi = {
  list(params?: StudentListQueryParams): Promise<ApiResponse<PaginatedResponse<StudentRecord>>> {
    const query: Record<string, string | number> = {};
    if (params?.search) query.search = params.search;
    if (params?.class_id != null) query.classroom_id = params.class_id;
    if (params?.stream_id != null) query.stream_id = params.stream_id;
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    return apiClient.get<PaginatedResponse<StudentRecord>>('/students', query);
  },

  getById(id: number): Promise<ApiResponse<StudentRecord>> {
    return apiClient.get<StudentRecord>(`/students/${id}`);
  },

  listClassrooms(): Promise<ApiResponse<ClassroomRecord[]>> {
    return apiClient.get<ClassroomRecord[]>('/classes');
  },

  listStreams(classId: number): Promise<ApiResponse<StreamRecord[]>> {
    return apiClient.get<StreamRecord[]>(`/classes/${classId}/streams`);
  },

  getStats(studentId: number): Promise<ApiResponse<StudentStatsRecord>> {
    return apiClient.get<StudentStatsRecord>(`/students/${studentId}/stats`);
  },

  getAttendanceCalendar(
    studentId: number,
    year: number,
    month: number,
  ): Promise<ApiResponse<AttendanceCalendarDay[]>> {
    return apiClient.get<AttendanceCalendarDay[]>(`/students/${studentId}/attendance-calendar`, {
      year,
      month,
    });
  },

  getStatement(
    studentId: number,
    year?: number,
    options?: { detailed?: boolean },
  ): Promise<ApiResponse<StudentStatementRecord>> {
    const params: Record<string, string | number | boolean> = {};
    if (year != null) params.year = year;
    if (options?.detailed != null) params.detailed = options.detailed;
    return apiClient.get<StudentStatementRecord>(`/students/${studentId}/statement`, params);
  },

  searchFinance(q: string): Promise<ApiResponse<StudentFinanceSearchResult[]>> {
    return apiClient.get<StudentFinanceSearchResult[]>('/students/search', { q });
  },

  getPaymentLink(studentId: number): Promise<
    ApiResponse<{
      payment_link_id: number;
      url: string;
      short_url: string | null;
      amount: number;
      currency: string;
    }>
  > {
    return apiClient.get(`/students/${studentId}/mpesa/payment-link`);
  },

  listCategories(): Promise<ApiResponse<Array<{ id: number; name: string; description?: string }>>> {
    return apiClient.get<Array<{ id: number; name: string; description?: string }>>(
      '/student-categories',
    );
  },

  update(
    studentId: number,
    payload: Record<string, unknown>,
  ): Promise<ApiResponse<StudentRecord>> {
    return apiClient.post<StudentRecord>(`/students/${studentId}/update`, payload);
  },
};
