import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface ParentTransportTripOption {
  id: number;
  name: string;
  direction?: string | null;
  type?: string | null;
  vehicle_id?: number | null;
  vehicle_number?: string | null;
  label: string;
}

export interface ParentTransportPointOption {
  id: number;
  name: string;
  label: string;
}

export interface ParentTransportVehicleOption {
  id: number;
  vehicle_number: string | null;
  driver_name?: string | null;
  capacity?: number | null;
  label: string;
}

export interface ParentTransportOptions {
  trips: ParentTransportTripOption[];
  drop_off_points: ParentTransportPointOption[];
  vehicles: ParentTransportVehicleOption[];
}

export const parentTransportApi = {
  options(): Promise<ApiResponse<ParentTransportOptions>> {
    return apiClient.get('/parent/transport-options');
  },
};
