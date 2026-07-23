import type { ApiResponse } from '../types/api';
import type { AttendanceMarkStatus } from './attendance.api';
import { apiClient } from './client';

export type ActivityType = 'activity' | 'swimming';

/** A markable activity — an extra-curricular activity fee or per-class swimming. */
export interface ActivitySummary {
  /** Composite id, e.g. `activity-12` or `swimming-4`. */
  id: string;
  type: ActivityType;
  name: string;
  classroom_id: number | null;
  classroom_name: string | null;
  fee_amount: string | number | null;
}

export interface ActivityStudent {
  id: number;
  full_name: string;
  admission_number: string;
}

export interface ActivityAttendanceRow {
  student_id: number;
  status: AttendanceMarkStatus;
}

export interface SaveActivityAttendancePayload {
  activityId: string;
  date: string;
  records: Array<{ student_id: number; status: AttendanceMarkStatus }>;
}

export const activitiesApi = {
  list(): Promise<ApiResponse<ActivitySummary[]>> {
    return apiClient.get<ActivitySummary[]>('/activities');
  },

  students(activityId: string, date?: string): Promise<ApiResponse<ActivityStudent[]>> {
    const query: Record<string, string> = {};
    if (date) query.date = date;
    return apiClient.get<ActivityStudent[]>(`/activities/${activityId}/students`, query);
  },

  attendance(activityId: string, date: string): Promise<ApiResponse<ActivityAttendanceRow[]>> {
    return apiClient.get<ActivityAttendanceRow[]>(`/activities/${activityId}/attendance`, { date });
  },

  saveAttendance(
    payload: SaveActivityAttendancePayload,
  ): Promise<ApiResponse<{ message: string; count: number }>> {
    return apiClient.post<{ message: string; count: number }>(
      `/activities/${payload.activityId}/attendance`,
      { date: payload.date, records: payload.records },
    );
  },
};
