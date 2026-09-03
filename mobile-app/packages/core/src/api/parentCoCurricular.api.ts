import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export type CoCurricularKind = 'activity' | 'yogurt';
export type CoCurricularIcon = 'ballet' | 'skating' | 'music' | 'yogurt' | 'swimming' | 'activities';
export type CoCurricularAction = 'join' | 'leave';

export interface CoCurricularTerm {
  year: number;
  term: number;
  label: string;
  is_current?: boolean;
  is_upcoming?: boolean;
}

export interface CoCurricularPendingRequest {
  id: number;
  action: CoCurricularAction;
  status: string;
  requested_amount: number;
}

export interface CoCurricularAttendance {
  present_count: number;
  last_date: string | null;
  recent: Array<{ date: string | null; attended: boolean }>;
}

export interface CoCurricularOffer {
  votehead_id: number;
  name: string;
  kind: CoCurricularKind;
  icon: CoCurricularIcon | string;
  amount: number;
  enrolled: boolean;
  billed_amount: number | null;
  attendance: CoCurricularAttendance | null;
  pending_request: CoCurricularPendingRequest | null;
}

export interface CoCurricularSnapshot {
  student: {
    id: number;
    full_name: string;
    admission_number?: string | null;
    class_name?: string | null;
    category_name?: string | null;
  };
  current_term: CoCurricularTerm;
  upcoming_term: CoCurricularTerm;
  selected_term: CoCurricularTerm;
  activities: CoCurricularOffer[];
  yogurt: CoCurricularOffer[];
  confirmation_message: string;
}

export const parentCoCurricularApi = {
  show(
    studentId: number,
    params?: { year?: number; term?: number },
  ): Promise<ApiResponse<CoCurricularSnapshot>> {
    return apiClient.get<CoCurricularSnapshot>(
      `/students/${studentId}/co-curricular`,
      {
        ...(params?.year ? { year: params.year } : {}),
        ...(params?.term ? { term: params.term } : {}),
      },
    );
  },

  requestChange(
    studentId: number,
    payload: {
      votehead_id: number;
      action: CoCurricularAction;
      year: number;
      term: number;
      note?: string;
    },
  ): Promise<ApiResponse<unknown>> {
    return apiClient.post(`/students/${studentId}/co-curricular`, payload);
  },

  cancelRequest(studentId: number, requestId: number): Promise<ApiResponse<unknown>> {
    return apiClient.post(`/students/${studentId}/co-curricular/requests/${requestId}/cancel`);
  },
};
