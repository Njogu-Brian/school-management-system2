import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface DriverTripSummary {
  id: number;
  name?: string | null;
  direction?: string | null;
  departure_time?: string | null;
  vehicle_registration?: string | null;
  student_count?: number;
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

export const driverTransportApi = {
  listTrips(params?: { date?: string; page?: number }): Promise<ApiResponse<PaginatedResponse<DriverTripSummary>>> {
    return apiClient.get<PaginatedResponse<DriverTripSummary>>('/driver/trips', params);
  },

  getTrip(id: number, params?: { date?: string }): Promise<ApiResponse<DriverTripDetail>> {
    return apiClient.get<DriverTripDetail>(`/driver/trips/${id}`, params);
  },
};
