import { apiClient } from './client';
import { ApiResponse } from '@types/api.types';

export interface DashboardStats {
    total_students: number;
    total_staff: number;
    present_today: number;
    fees_collected: number;
}

export const dashboardApi = {
    async getStats(): Promise<ApiResponse<DashboardStats>> {
        return apiClient.get<DashboardStats>('/dashboard/stats');
    },
};
