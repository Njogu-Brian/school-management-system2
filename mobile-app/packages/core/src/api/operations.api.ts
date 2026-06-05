import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface TransportRouteSummary {
  id: number;
  name: string;
  code?: string | null;
  description?: string | null;
  vehicle_registration?: string | null;
  driver_name?: string | null;
  status?: string;
  drop_points?: Array<{
    id: number;
    name: string;
    sequence?: number;
    pickup_time?: string | null;
  }>;
}

export interface StudentRequirementTemplateItem {
  template_id: number;
  requirement_id?: number | null;
  name: string;
  brand?: string | null;
  unit?: string | null;
  quantity_required: number;
  quantity_collected: number;
  status: string;
  student_type?: string;
  custody_type?: string | null;
  notes?: string | null;
}

export interface StudentRequirementsPayload {
  student: {
    id: number;
    full_name: string;
    admission_number: string;
    class_name?: string | null;
    is_new_joiner?: boolean;
  };
  current_term?: { id: number; name: string } | null;
  items: StudentRequirementTemplateItem[];
}

/** Reuses existing transport + teacher requirements routes (no new backend). */
export const operationsApi = {
  getRoute(id: number): Promise<ApiResponse<TransportRouteSummary>> {
    return apiClient.get<TransportRouteSummary>(`/routes/${id}`);
  },

  getStudentRequirements(
    studentId: number,
  ): Promise<ApiResponse<StudentRequirementsPayload>> {
    return apiClient.get<StudentRequirementsPayload>(
      `/teacher/requirements/students/${studentId}/templates`,
    );
  },
};
