import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface StaffAdvanceRecord {
  id: number;
  staff_id: number;
  staff_name?: string | null;
  amount: number;
  requested_amount: number;
  amount_rejected?: number;
  purpose?: string | null;
  description?: string | null;
  advance_date?: string | null;
  repayment_method: string;
  installment_count?: number | null;
  monthly_deduction_amount?: number | null;
  amount_repaid: number;
  balance: number;
  status: string;
  expected_completion_date?: string | null;
  notes?: string | null;
  approved_at?: string | null;
  created_at?: string | null;
}

export interface LeaveTypeRecord {
  id: number;
  name: string;
  code?: string;
  max_days?: number;
  is_paid?: boolean;
  requires_approval?: boolean;
  description?: string | null;
  is_active?: boolean;
}

export const staffAdvancesApi = {
  list(params?: {
    status?: string;
    staff_id?: number;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<StaffAdvanceRecord>>> {
    return apiClient.get('/staff-advances', params);
  },

  get(id: number): Promise<ApiResponse<StaffAdvanceRecord>> {
    return apiClient.get(`/staff-advances/${id}`);
  },

  create(payload: {
    staff_id?: number;
    amount: number;
    requested_amount?: number;
    purpose?: string;
    description?: string;
    advance_date: string;
    repayment_method?: string;
    installment_count?: number;
    monthly_deduction_amount?: number;
    expected_completion_date?: string;
    notes?: string;
  }): Promise<ApiResponse<StaffAdvanceRecord>> {
    return apiClient.post('/staff-advances', payload);
  },

  approve(
    id: number,
    payload?: {
      amount?: number;
      repayment_method?: string;
      installment_count?: number;
      monthly_deduction_amount?: number;
      notes?: string;
    },
  ): Promise<ApiResponse<StaffAdvanceRecord>> {
    return apiClient.post(`/staff-advances/${id}/approve`, payload ?? {});
  },

  reject(id: number, reason: string): Promise<ApiResponse<StaffAdvanceRecord>> {
    return apiClient.post(`/staff-advances/${id}/reject`, { reason });
  },
};

export const leaveTypesApi = {
  list(includeInactive?: boolean): Promise<ApiResponse<LeaveTypeRecord[]>> {
    return apiClient.get('/leave-types', includeInactive ? { include_inactive: 1 } : undefined);
  },

  create(payload: {
    name: string;
    code: string;
    max_days: number;
    is_paid: boolean;
    requires_approval?: boolean;
    description?: string;
    is_active?: boolean;
  }): Promise<ApiResponse<LeaveTypeRecord>> {
    return apiClient.post('/leave-types', payload);
  },

  update(
    id: number,
    payload: Partial<{
      name: string;
      code: string;
      max_days: number;
      is_paid: boolean;
      requires_approval: boolean;
      description: string;
      is_active: boolean;
    }>,
  ): Promise<ApiResponse<LeaveTypeRecord>> {
    return apiClient.put(`/leave-types/${id}`, payload);
  },

  assign(payload: {
    staff_id: number;
    leave_type_id: number;
    academic_year_id?: number;
    entitlement_days: number;
    carried_forward?: number;
  }): Promise<ApiResponse<unknown>> {
    return apiClient.post('/leave-types/assign', payload);
  },
};
