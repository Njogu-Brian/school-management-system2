import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export type TransportStopKind = 'morning_pickup' | 'evening_dropoff';

export interface StudentTransportStopRow {
  student_id: number;
  full_name?: string | null;
  admission_number?: string | null;
  kind: TransportStopKind;
  latitude?: number | null;
  longitude?: number | null;
  updated_at?: string | null;
  marked_today?: boolean;
  last_marked_at?: string | null;
}

export interface StudentTransportStopRecord {
  id?: number;
  student_id: number;
  kind: TransportStopKind;
  latitude: number;
  longitude: number;
  updated_at?: string | null;
}

export interface TripStopsResponse {
  trip_id: number;
  date: string;
  kind: TransportStopKind;
  stops: StudentTransportStopRow[];
}

export interface StudentStopsResponse {
  student_id: number;
  stops: StudentTransportStopRecord[];
}

export interface MarkTransportStopPayload {
  student_id: number;
  latitude: number;
  longitude: number;
  kind?: TransportStopKind;
  date?: string;
  recorded_at?: string;
}

export interface MarkTransportStopResult {
  stop: StudentTransportStopRecord;
  event: {
    id: number;
    student_id: number;
    kind: TransportStopKind;
    latitude: number;
    longitude: number;
    recorded_at?: string | null;
    trip_run_id?: number | null;
    trip_id?: number | null;
  };
}

export const transportStopsApi = {
  forTrip(tripId: number, params?: { date?: string; kind?: TransportStopKind }): Promise<ApiResponse<TripStopsResponse>> {
    return apiClient.get(`/transport/trips/${tripId}/stops`, params);
  },

  forStudent(studentId: number, params?: { kind?: TransportStopKind }): Promise<ApiResponse<StudentStopsResponse>> {
    return apiClient.get(`/transport/students/${studentId}/stops`, params);
  },

  mark(tripId: number, payload: MarkTransportStopPayload): Promise<ApiResponse<MarkTransportStopResult>> {
    return apiClient.post(`/transport/trips/${tripId}/stops`, payload);
  },
};
