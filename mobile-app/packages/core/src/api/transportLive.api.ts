import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface LiveChildStop {
  kind: 'morning_pickup' | 'evening_dropoff';
  latitude: number;
  longitude: number;
  updated_at?: string | null;
}

export interface LiveBusLocation {
  live: boolean;
  trip_id?: number;
  trip_name?: string | null;
  direction?: string | null;
  vehicle_registration?: string | null;
  vehicle?: {
    id?: number;
    vehicle_number?: string | null;
    type?: string | null;
  } | null;
  driver_name?: string | null;
  status?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  accuracy_meters?: number | null;
  speed_kmh?: number | null;
  last_location_at?: string | null;
  /** Preferred freshness field from API. */
  freshness_seconds?: number | null;
  /** Legacy alias kept for older clients. */
  age_seconds?: number | null;
  started_at?: string | null;
  message?: string | null;
  child_stop?: LiveChildStop | null;
  run?: {
    id?: number;
    status?: string | null;
    latitude?: number | null;
    longitude?: number | null;
    last_location_at?: string | null;
    freshness_seconds?: number | null;
  } | null;
}

export interface LiveFleetBus extends LiveBusLocation {
  run_id?: number;
  vehicle_id?: number | null;
  driver_id?: number | null;
  student_count?: number | null;
  vehicle_number?: string | null;
}

export interface LiveFleetResponse {
  date?: string;
  runs: LiveFleetBus[];
  total?: number;
}

export const transportLiveApi = {
  forStudent(studentId: number): Promise<ApiResponse<LiveBusLocation>> {
    return apiClient.get(`/transport/live/students/${studentId}`);
  },

  fleet(): Promise<ApiResponse<LiveFleetResponse | LiveFleetBus[]>> {
    return apiClient.get('/transport/live/fleet');
  },

  forTrip(tripId: number, params?: { date?: string }): Promise<ApiResponse<LiveBusLocation>> {
    return apiClient.get(`/transport/live/trips/${tripId}`, params);
  },
};
