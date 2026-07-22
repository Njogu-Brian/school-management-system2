import type { ApiResponse, PaginatedResponse } from '../types/api';
import type { PayrollRecordDetail, PayrollRecordRow } from '../types/staff360';
import { apiClient } from './client';

export interface PayrollListParams {
  staff_id?: number;
  status?: string;
  /** YYYY-MM */
  month?: string;
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

  getById(id: number): Promise<ApiResponse<PayrollRecordDetail>> {
    return apiClient.get<PayrollRecordDetail>(`/payroll-records/${id}`);
  },

  payslipDownloadPath(recordId: number): string {
    return `/payroll-records/${recordId}/payslip/download`;
  },
};
