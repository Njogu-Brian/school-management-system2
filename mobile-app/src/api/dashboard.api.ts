import { apiClient } from './client';
import { ApiResponse } from '@types/api.types';

export interface DashboardChartSeries {
    labels: string[];
    values: number[];
}

export interface BirthdayItem {
    name: string;
    date: string;
    type: 'student' | 'staff';
}

export interface TeacherOnLeaveItem {
    name: string;
    start_date: string;
    end_date: string;
    leave_type: string | null;
}

export interface DashboardStats {
    total_students?: number;
    total_staff?: number;
    present_today?: number;
    fees_collected?: number;
    role?: string;
    /** Teacher / senior teacher (from /dashboard/stats) */
    my_classes?: number;
    pending_marks?: number;
    classes_today?: number;
    /** Parent / guardian */
    children_count?: number;
    total_fee_balance?: number;
    /** Admin dashboard */
    charts?: {
        enrollment?: DashboardChartSeries;
        payments?: DashboardChartSeries;
        invoices?: DashboardChartSeries;
        /** Legacy keys (older clients) */
        line?: DashboardChartSeries;
        bar?: DashboardChartSeries;
    };
    birthdays?: BirthdayItem[];
    teachers_on_leave?: TeacherOnLeaveItem[];
}

export const dashboardApi = {
    async getStats(): Promise<ApiResponse<DashboardStats>> {
        return apiClient.get<DashboardStats>('/dashboard/stats');
    },
};
