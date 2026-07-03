import type {
  StaffAttendanceHistoryPayload,
  StaffLeaveBalancesPayload,
} from '../types/staff360';
import type {
  StaffFilterOptions,
  StaffListQueryParams,
  StaffRecord,
} from '../types/staff';
import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface StaffAttendanceHistoryParams {
  start_date?: string;
  end_date?: string;
  page?: number;
  per_page?: number;
}

/**
 * Staff registry API — reuses Laravel `ApiStaffController`.
 *
 * - `GET /staff` — paginated directory
 * - `GET /staff/{id}` — detail
 * - `GET /staff/filter-options` — filter chips (Batch 2)
 * - `GET /staff/{id}/leave-balances` — Staff 360 leave balances (Batch 4)
 * - `GET /staff/{id}/attendance-history` — Staff 360 attendance (Batch 4)
 */
export const staffApi = {
  list(params?: StaffListQueryParams): Promise<ApiResponse<PaginatedResponse<StaffRecord>>> {
    const query: Record<string, string | number> = {};
    if (params?.search) query.search = params.search;
    if (params?.department_id != null) query.department_id = params.department_id;
    if (params?.staff_category_id != null) query.staff_category_id = params.staff_category_id;
    if (params?.employment_status) query.employment_status = params.employment_status;
    if (params?.gender) query.gender = params.gender;
    if (params?.role) query.role = params.role;
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    return apiClient.get<PaginatedResponse<StaffRecord>>('/staff', query);
  },

  getById(id: number): Promise<ApiResponse<StaffRecord>> {
    return apiClient.get<StaffRecord>(`/staff/${id}`);
  },

  filterOptions(): Promise<ApiResponse<StaffFilterOptions>> {
    return apiClient.get<StaffFilterOptions>('/staff/filter-options');
  },

  leaveBalances(staffId: number): Promise<ApiResponse<StaffLeaveBalancesPayload>> {
    return apiClient.get<StaffLeaveBalancesPayload>(`/staff/${staffId}/leave-balances`);
  },

  attendanceHistory(
    staffId: number,
    params?: StaffAttendanceHistoryParams,
  ): Promise<ApiResponse<StaffAttendanceHistoryPayload>> {
    return apiClient.get<StaffAttendanceHistoryPayload>(
      `/staff/${staffId}/attendance-history`,
      params,
    );
  },

  update(id: number, payload: Record<string, unknown>): Promise<ApiResponse<StaffRecord>> {
    return apiClient.put<StaffRecord>(`/staff/${id}`, payload);
  },
};
