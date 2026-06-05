import type { ApiResponse, PaginatedResponse } from '../types/api';
import type { PayrollRecordRow } from '../types/staff360';
import { apiClient } from './client';

export interface PayrollListParams {
  staff_id?: number;
  status?: string;
  page?: number;
  per_page?: number;
}

/** Payroll records — reuses `ApiPayrollRecordsController`. */
export const payrollApi = {
  list(
    params?: PayrollListParams,
  ): Promise<ApiResponse<PaginatedResponse<PayrollRecordRow>>> {
    return apiClient.get<PaginatedResponse<PayrollRecordRow>>('/payroll-records', params);
  },
};
