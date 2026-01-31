import { apiClient } from './client';
import {
    Staff,
    Leave,
    StaffAttendance,
    Payroll,
    SalaryAdvance,
    StaffFilters,
    LeaveFilters,
    CreateStaffData,
    UpdateStaffData,
} from '../types/hr.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

export const hrApi = {
    // ========== Staff Management ==========
    async getStaff(filters?: StaffFilters): Promise<ApiResponse<PaginatedResponse<Staff>>> {
        return apiClient.get<PaginatedResponse<Staff>>('/staff', filters);
    },

    async getStaffMember(id: number): Promise<ApiResponse<Staff>> {
        return apiClient.get<Staff>(`/staff/${id}`);
    },

    async createStaff(data: CreateStaffData): Promise<ApiResponse<Staff>> {
        return apiClient.post<Staff>('/staff', data);
    },

    async updateStaff(id: number, data: UpdateStaffData): Promise<ApiResponse<Staff>> {
        return apiClient.put<Staff>(`/staff/${id}`, data);
    },

    async deleteStaff(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/staff/${id}`);
    },

    // ========== Leave Management ==========
    async getLeaves(filters?: LeaveFilters): Promise<ApiResponse<PaginatedResponse<Leave>>> {
        return apiClient.get<PaginatedResponse<Leave>>('/leaves', filters);
    },

    async getLeaveApplications(filters?: LeaveFilters): Promise<ApiResponse<PaginatedResponse<Leave>>> {
        return apiClient.get<PaginatedResponse<Leave>>('/leaves', filters);
    },

    async getLeave(id: number): Promise<ApiResponse<Leave>> {
        return apiClient.get<Leave>(`/leaves/${id}`);
    },

    async applyLeave(data: {
        staff_id: number;
        leave_type: string;
        start_date: string;
        end_date: string;
        reason: string;
    }): Promise<ApiResponse<Leave>> {
        return apiClient.post<Leave>('/leaves', data);
    },

    async approveLeave(id: number): Promise<ApiResponse<Leave>> {
        return apiClient.post<Leave>(`/leaves/${id}/approve`);
    },

    async rejectLeave(id: number, reason: string): Promise<ApiResponse<Leave>> {
        return apiClient.post<Leave>(`/leaves/${id}/reject`, { reason });
    },

    async cancelLeave(id: number): Promise<ApiResponse<Leave>> {
        return apiClient.post<Leave>(`/leaves/${id}/cancel`);
    },

    // ========== Staff Attendance ==========
    async getStaffAttendance(filters?: {
        staff_id?: number;
        date_from?: string;
        date_to?: string;
    }): Promise<ApiResponse<PaginatedResponse<StaffAttendance>>> {
        return apiClient.get<PaginatedResponse<StaffAttendance>>('/staff-attendance', filters);
    },

    async markStaffAttendance(data: {
        staff_id: number;
        date: string;
        check_in?: string;
        check_out?: string;
        status: string;
        notes?: string;
    }): Promise<ApiResponse<StaffAttendance>> {
        return apiClient.post<StaffAttendance>('/staff-attendance', data);
    },

    // ========== Payroll ==========
    async getPayrolls(filters?: {
        staff_id?: number;
        month?: string;
        status?: string;
    }): Promise<ApiResponse<PaginatedResponse<Payroll>>> {
        return apiClient.get<PaginatedResponse<Payroll>>('/payrolls', filters);
    },

    async getPayroll(id: number): Promise<ApiResponse<Payroll>> {
        return apiClient.get<Payroll>(`/payrolls/${id}`);
    },

    async generatePayroll(month: string): Promise<ApiResponse<{ count: number; message: string }>> {
        return apiClient.post('/payrolls/generate', { month });
    },

    async processPayroll(id: number): Promise<ApiResponse<Payroll>> {
        return apiClient.post<Payroll>(`/payrolls/${id}/process`);
    },

    async downloadPayslip(id: number): Promise<ApiResponse<Blob>> {
        return apiClient.get<Blob>(`/payrolls/${id}/payslip`);
    },

    // ========== Salary Advances ==========
    async getSalaryAdvances(filters?: {
        staff_id?: number;
        status?: string;
    }): Promise<ApiResponse<PaginatedResponse<SalaryAdvance>>> {
        return apiClient.get<PaginatedResponse<SalaryAdvance>>('/salary-advances', filters);
    },

    async requestSalaryAdvance(data: {
        staff_id: number;
        amount: number;
        reason: string;
        recovery_months?: number;
    }): Promise<ApiResponse<SalaryAdvance>> {
        return apiClient.post<SalaryAdvance>('/salary-advances', data);
    },

    async approveSalaryAdvance(id: number): Promise<ApiResponse<SalaryAdvance>> {
        return apiClient.post<SalaryAdvance>(`/salary-advances/${id}/approve`);
    },

    async rejectSalaryAdvance(id: number, reason: string): Promise<ApiResponse<SalaryAdvance>> {
        return apiClient.post<SalaryAdvance>(`/salary-advances/${id}/reject`, { reason });
    },

    // ========== Reports ==========
    async getHRSummary(): Promise<ApiResponse<{
        total_staff: number;
        active_staff: number;
        on_leave: number;
        pending_leaves: number;
        pending_advances: number;
    }>> {
        return apiClient.get('/hr/summary');
    },
};
