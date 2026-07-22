import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface TransportSpecialAssignmentRecord {
  id: number;
  student_id: number;
  student_name?: string | null;
  vehicle_id?: number | null;
  trip_id?: number | null;
  drop_off_point_id?: number | null;
  transport_mode: 'vehicle' | 'trip' | 'own_means' | string;
  start_date: string;
  end_date?: string | null;
  reason?: string | null;
  status: string;
}

export const transportSpecialApi = {
  list(params?: {
    student_id?: number;
    status?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<TransportSpecialAssignmentRecord>>> {
    return apiClient.get('/transport/special-assignments', params);
  },

  create(payload: {
    student_id: number;
    vehicle_id?: number | null;
    trip_id?: number | null;
    drop_off_point_id?: number | null;
    transport_mode: 'vehicle' | 'trip' | 'own_means';
    start_date: string;
    end_date?: string | null;
    reason?: string;
    /** Parents should pass false so admin must approve. */
    activate?: boolean;
  }): Promise<ApiResponse<TransportSpecialAssignmentRecord>> {
    return apiClient.post('/transport/special-assignments', payload);
  },

  cancel(id: number): Promise<ApiResponse<unknown>> {
    return apiClient.post(`/transport/special-assignments/${id}/cancel`);
  },
};
