import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

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
  staff_id?: number;
  date: string | null;
  status: string;
  check_in_time: string | null;
  check_out_time: string | null;
  check_in_distance_meters: number | null;
  check_out_distance_meters: number | null;
}

export interface StaffClockRosterItem {
  id: number;
  staff_id: string;
  full_name: string;
}

interface ClockPayload {
  latitude: number;
  longitude: number;
  accuracy_meters?: number;
}

export const staffClockApi = {
  getGeofenceConfig(): Promise<ApiResponse<StaffGeofenceConfig>> {
    return apiClient.get<StaffGeofenceConfig>('/staff-attendance/geofence');
  },

  updateGeofenceConfig(data: {
    latitude: number;
    longitude: number;
    radius_meters: number;
  }): Promise<ApiResponse<StaffGeofenceConfig>> {
    return apiClient.put<StaffGeofenceConfig>('/staff-attendance/geofence', data);
  },

  getTodayClockStatus(): Promise<ApiResponse<StaffTodayClock | null>> {
    return apiClient.get<StaffTodayClock | null>('/staff-attendance/me/today');
  },

  getClockHistory(limit = 90): Promise<ApiResponse<StaffClockHistoryItem[]>> {
    return apiClient.get<StaffClockHistoryItem[]>('/staff-attendance/me/history', { limit });
  },

  getClockRoster(): Promise<ApiResponse<StaffClockRosterItem[]>> {
    return apiClient.get<StaffClockRosterItem[]>('/staff-attendance/clock-roster');
  },

  getStaffClockHistory(
    staffId: number,
    limit = 90,
  ): Promise<ApiResponse<{ staff: { id: number; full_name: string } | null; history: StaffClockHistoryItem[] }>> {
    return apiClient.get('/staff-attendance/staff/history', { staff_id: staffId, limit });
  },

  clockIn(payload: ClockPayload): Promise<ApiResponse<{ check_in_time: string; distance_meters: number }>> {
    return apiClient.post('/staff-attendance/clock-in', payload);
  },

  clockOut(payload: ClockPayload): Promise<ApiResponse<{ check_out_time: string; distance_meters: number }>> {
    return apiClient.post('/staff-attendance/clock-out', payload);
  },
};
