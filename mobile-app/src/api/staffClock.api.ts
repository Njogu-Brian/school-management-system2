import { apiClient } from './client';
import { ApiResponse } from 'types/api.types';

export interface StaffGeofenceConfig {
    latitude: number | null;
    longitude: number | null;
    radius_meters: number;
    is_configured: boolean;
    can_manage: boolean;
}

export interface StaffTodayClock {
    id: number;
    date: string;
    status: string;
    check_in_time: string | null;
    check_out_time: string | null;
    check_in_distance_meters: number | null;
    check_out_distance_meters: number | null;
}

export interface StaffClockHistoryItem {
    id: number;
    date: string | null;
    status: string;
    check_in_time: string | null;
    check_out_time: string | null;
    check_in_distance_meters: number | null;
    check_out_distance_meters: number | null;
}

interface ClockPayload {
    latitude: number;
    longitude: number;
    accuracy_meters?: number;
}

export const staffClockApi = {
    async getGeofenceConfig(): Promise<ApiResponse<StaffGeofenceConfig>> {
        return apiClient.get<StaffGeofenceConfig>('/staff-attendance/geofence');
    },

    async updateGeofenceConfig(data: {
        latitude: number;
        longitude: number;
        radius_meters: number;
    }): Promise<ApiResponse<StaffGeofenceConfig>> {
        return apiClient.put<StaffGeofenceConfig>('/staff-attendance/geofence', data);
    },

    async getTodayClockStatus(): Promise<ApiResponse<StaffTodayClock | null>> {
        return apiClient.get<StaffTodayClock | null>('/staff-attendance/me/today');
    },

    async getClockHistory(limit = 14): Promise<ApiResponse<StaffClockHistoryItem[]>> {
        return apiClient.get<StaffClockHistoryItem[]>('/staff-attendance/me/history', { limit });
    },

    async clockIn(payload: ClockPayload): Promise<ApiResponse<{ check_in_time: string; distance_meters: number }>> {
        return apiClient.post('/staff-attendance/clock-in', payload);
    },

    async clockOut(payload: ClockPayload): Promise<ApiResponse<{ check_out_time: string; distance_meters: number }>> {
        return apiClient.post('/staff-attendance/clock-out', payload);
    },
};
