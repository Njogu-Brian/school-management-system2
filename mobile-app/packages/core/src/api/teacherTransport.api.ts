import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface TeacherTransportPickup {
  id: number;
  direction: 'morning' | 'evening' | 'both';
  picked_up_by?: string | null;
  notes?: string | null;
  recorded_at?: string;
}

export interface TeacherTransportStudent {
  id: number;
  full_name: string;
  admission_number: string;
  class_name?: string | null;
  stream_name?: string | null;
  fee_status?: 'cleared' | 'pending';
  morning?: { trip_name?: string | null; vehicle_registration?: string | null } | null;
  evening?: { trip_name?: string | null; vehicle_registration?: string | null } | null;
  pickup: TeacherTransportPickup | null;
}

export interface TeacherTransportResponse {
  date: string;
  students: TeacherTransportStudent[];
}

export const teacherTransportApi = {
  getStudents(params?: { date?: string; classroom_id?: number }): Promise<ApiResponse<TeacherTransportResponse>> {
    return apiClient.get<TeacherTransportResponse>('/teacher/transport/students', params);
  },

  markCollectedByParent(payload: {
    student_id: number;
    date?: string;
    direction?: 'morning' | 'evening' | 'both';
    picked_up_by?: string;
    notes?: string;
  }): Promise<ApiResponse<TeacherTransportPickup>> {
    return apiClient.post('/teacher/transport/pickups', payload);
  },

  cancelPickup(pickupId: number): Promise<ApiResponse<void>> {
    return apiClient.delete(`/teacher/transport/pickups/${pickupId}`);
  },
};
