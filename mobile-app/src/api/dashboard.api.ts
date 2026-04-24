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

export interface DashboardAcademicYearOption {
    id: number;
    year: number | string;
    is_active?: boolean;
}

export interface DashboardTermOption {
    id: number;
    name: string;
    academic_year_id: number;
    opening_date?: string | null;
    closing_date?: string | null;
    is_current?: boolean;
}

export interface DashboardFilters {
    academic_year_id: number | null;
    term_id: number | null;
    available_years: DashboardAcademicYearOption[];
    available_terms: DashboardTermOption[];
}

export interface DashboardStats {
    total_students?: number;
    total_staff?: number;
    present_today?: number;
    fees_collected?: number;
    total_invoiced?: number;
    total_payments?: number;
    outstanding_balance?: number;
    role?: string;
    /** Teacher / senior teacher (from /dashboard/stats) */
    my_classes?: number;
    pending_marks?: number;
    classes_today?: number;
    /** Parent / guardian */
    children_count?: number;
    total_fee_balance?: number;
    /** Admin dashboard */
    filters?: DashboardFilters;
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

export interface DashboardStatsFilters {
    academic_year_id?: number | null;
    term_id?: number | null;
}

export const dashboardApi = {
    async getStats(filters?: DashboardStatsFilters): Promise<ApiResponse<DashboardStats>> {
        const params: Record<string, any> = {};
        if (filters?.academic_year_id != null) params.academic_year_id = filters.academic_year_id;
        if (filters?.term_id != null) params.term_id = filters.term_id;
        return apiClient.get<DashboardStats>('/dashboard/stats', params);
    },
};
