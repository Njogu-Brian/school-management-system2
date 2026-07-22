import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface LiveBusLocation {
  live: boolean;
  trip_id?: number;
  trip_name?: string | null;
  direction?: string | null;
  vehicle_registration?: string | null;
  driver_name?: string | null;
  status?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  accuracy_meters?: number | null;
  speed_kmh?: number | null;
  last_location_at?: string | null;
  age_seconds?: number | null;
  started_at?: string | null;
  message?: string | null;
}

export interface LiveFleetBus extends LiveBusLocation {
  run_id?: number;
  vehicle_id?: number | null;
  driver_id?: number | null;
  student_count?: number | null;
}

export const transportLiveApi = {
  forStudent(studentId: number): Promise<ApiResponse<LiveBusLocation>> {
    return apiClient.get(`/transport/live/students/${studentId}`);
  },

  fleet(): Promise<ApiResponse<LiveFleetBus[]>> {
    return apiClient.get('/transport/live/fleet');
  },

  forTrip(tripId: number, params?: { date?: string }): Promise<ApiResponse<LiveBusLocation>> {
    return apiClient.get(`/transport/live/trips/${tripId}`, params);
  },
};
