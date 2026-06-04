import type {
  StaffFilterOptions,
  StaffListQueryParams,
  StaffRecord,
} from '../types/staff';
import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

/**
 * Staff registry API — reuses Laravel `ApiStaffController`.
 *
 * - `GET /staff` — paginated directory
 * - `GET /staff/{id}` — detail
 * - `GET /staff/filter-options` — filter chips (Batch 2)
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
};
