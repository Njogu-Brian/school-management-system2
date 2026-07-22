import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export type DriverTripStatus = 'not_started' | 'in_progress' | 'completed' | string;

export interface DriverTripSummary {
  id: number;
  name?: string | null;
  direction?: string | null;
  departure_time?: string | null;
  vehicle_registration?: string | null;
  student_count?: number;
  status?: DriverTripStatus;
  start_time?: string | null;
  end_time?: string | null;
}

export interface DriverTripDetail extends DriverTripSummary {
  students?: Array<{
    id: number;
    full_name: string;
    admission_number?: string | null;
    drop_point?: string | null;
    fee_status?: string | null;
  }>;
}

export type DriverBoardingStatus = 'pending' | 'present' | 'absent' | 'late';

export interface DriverBoardingStudent {
  student_id: number;
  full_name?: string | null;
  admission_number?: string | null;
  drop_point?: string | null;
  status: DriverBoardingStatus;
  marked_at?: string | null;
}

export interface DriverBoardingResponse {
  date: string;
  direction?: string | null;
  boarded_count?: number;
  total_count?: number;
  students: DriverBoardingStudent[];
}

export interface DriverLocationPingPayload {
  latitude: number;
  longitude: number;
  accuracy_meters?: number;
  speed_kmh?: number;
  heading?: number;
  date?: string;
}

export interface DriverVehicleRecord {
  id: number;
  vehicle_number: string;
  driver_name?: string | null;
  make?: string | null;
  model?: string | null;
  type?: string | null;
  capacity?: number | null;
  chassis_number?: string | null;
  insurance_expiry?: string | null;
  inspection_expiry?: string | null;
  status?: string | null;
}

export const driverTransportApi = {
  listTrips(params?: { date?: string; page?: number }): Promise<ApiResponse<PaginatedResponse<DriverTripSummary>>> {
    return apiClient.get<PaginatedResponse<DriverTripSummary>>('/driver/trips', params);
  },

  getTrip(id: number, params?: { date?: string }): Promise<ApiResponse<DriverTripDetail>> {
    return apiClient.get<DriverTripDetail>(`/driver/trips/${id}`, params);
  },

  startTrip(id: number, payload?: { date?: string }): Promise<ApiResponse<DriverTripDetail>> {
    return apiClient.post<DriverTripDetail>(`/driver/trips/${id}/start`, payload ?? {});
  },

  stopTrip(id: number, payload?: { date?: string }): Promise<ApiResponse<DriverTripDetail>> {
    return apiClient.post<DriverTripDetail>(`/driver/trips/${id}/stop`, payload ?? {});
  },

  getBoarding(id: number, params?: { date?: string }): Promise<ApiResponse<DriverBoardingResponse>> {
    return apiClient.get<DriverBoardingResponse>(`/driver/trips/${id}/boarding`, params);
  },

  markBoarding(
    id: number,
    payload: { student_id: number; status: DriverBoardingStatus; date?: string },
  ): Promise<ApiResponse<DriverBoardingStudent>> {
    return apiClient.post<DriverBoardingStudent>(`/driver/trips/${id}/boarding`, payload);
  },

  markBoardingBulk(
    id: number,
    payload: { attendance: Array<{ student_id: number; status: DriverBoardingStatus }>; date?: string },
  ): Promise<ApiResponse<DriverBoardingResponse>> {
    return apiClient.post<DriverBoardingResponse>(`/driver/trips/${id}/boarding`, payload);
  },

  pingLocation(id: number, payload: DriverLocationPingPayload): Promise<ApiResponse<void>> {
    return apiClient.post<void>(`/driver/trips/${id}/location`, payload);
  },

  getVehicle(): Promise<ApiResponse<DriverVehicleRecord>> {
    return apiClient.get<DriverVehicleRecord>('/driver/vehicle');
  },
};
