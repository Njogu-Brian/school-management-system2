import { apiClient } from './client';
import { ApiResponse } from '../types/api.types';

export interface TeacherTransportLeg {
    type: 'own_means' | 'trip' | 'vehicle' | 'default';
    trip_id?: number | null;
    trip_name?: string | null;
    direction?: string | null;
    departure_time?: string | null;
    vehicle_id?: number | null;
    vehicle_registration?: string | null;
    vehicle_name?: string | null;
    drop_off_point?: string | null;
    reason?: string | null;
}

export interface TeacherTransportPickup {
    id: number;
    direction: 'morning' | 'evening' | 'both';
    picked_up_by?: string | null;
    skip_evening_trip: boolean;
    notes?: string | null;
    recorded_at?: string;
}

export interface TeacherTransportStudent {
    id: number;
    full_name: string;
    admission_number: string;
    class_name?: string | null;
    stream_name?: string | null;
    morning: TeacherTransportLeg | null;
    evening: TeacherTransportLeg | null;
    pickup: TeacherTransportPickup | null;
}

export interface TeacherTransportResponse {
    date: string;
    students: TeacherTransportStudent[];
}

export interface TeacherTransportVehicle {
    id: number;
    vehicle_number: string;
    driver_name?: string | null;
    capacity?: number | null;
}

export interface TeacherTransportTrip {
    id: number;
    name?: string | null;
    direction?: string | null;
    departure_time?: string | null;
    vehicle?: { id: number; vehicle_number?: string; driver_name?: string | null } | null;
}

export const teacherTransportApi = {
    async getStudents(params?: { date?: string; classroom_id?: number }): Promise<ApiResponse<TeacherTransportResponse>> {
        return apiClient.get('/teacher/transport/students', params);
    },

    async getVehiclesAndTrips(): Promise<ApiResponse<{ vehicles: TeacherTransportVehicle[]; trips: TeacherTransportTrip[] }>> {
        return apiClient.get('/teacher/transport/vehicles');
    },

    async markCollectedByParent(payload: {
        student_id: number;
        date?: string;
        direction?: 'morning' | 'evening' | 'both';
        picked_up_by?: string;
        notes?: string;
    }): Promise<ApiResponse<TeacherTransportPickup>> {
        return apiClient.post('/teacher/transport/pickups', payload);
    },

    async cancelPickup(pickupId: number): Promise<ApiResponse<void>> {
        return apiClient.delete(`/teacher/transport/pickups/${pickupId}`);
    },

    async temporaryReassign(payload: {
        student_id: number;
        start_date: string;
        end_date?: string;
        mode: 'vehicle' | 'trip';
        vehicle_id?: number;
        trip_id?: number;
        reason?: string;
    }): Promise<ApiResponse<any>> {
        return apiClient.post('/teacher/transport/reassign', payload);
    },
};
