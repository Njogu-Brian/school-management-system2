import type { ApiResponse, PaginatedResponse } from '../types/api';
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

export interface OperationsSummary {
  transport: { active_trips: number; students_assigned: number };
  library: { total_books: number; available_books: number };
  inventory: { tracked_items: number; low_stock_items: number };
  facilities: { open_tickets: number };
  visitors?: { on_site: number };
  assets?: { active: number };
  as_of: string;
}

export interface InventoryItemRecord {
  id: number;
  name: string;
  category?: string | null;
  brand?: string | null;
  quantity: number;
  min_stock_level: number;
  unit?: string | null;
  is_low_stock: boolean;
  location?: string | null;
}

export interface RequisitionRecord {
  id: number;
  requisition_number: string;
  type: string;
  purpose?: string | null;
  status: string;
  requested_by?: string | null;
  requested_at?: string | null;
  can_approve?: boolean;
  can_reject?: boolean;
  items?: Array<{
    id: number;
    item_name: string;
    quantity_requested: number;
    quantity_approved?: number | null;
    unit?: string | null;
  }>;
}

export interface MedicalRecordRow {
  id: number;
  record_type?: string | null;
  record_date?: string | null;
  title?: string | null;
  description?: string | null;
  doctor_name?: string | null;
  vaccination_name?: string | null;
  notes?: string | null;
}

export interface VisitorRecord {
  id: number;
  visitor_name: string;
  phone?: string | null;
  purpose?: string | null;
  host_name?: string | null;
  checked_in_at?: string | null;
  checked_out_at?: string | null;
  on_site: boolean;
}

/** Transport + operations summary APIs (Sprints 9–10). */
export const operationsApi = {
  getSummary(): Promise<ApiResponse<OperationsSummary>> {
    return apiClient.get<OperationsSummary>('/operations/summary');
  },

  listRoutes(params?: {
    search?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<TransportRouteSummary>>> {
    return apiClient.get<PaginatedResponse<TransportRouteSummary>>('/routes', params);
  },

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

  listInventoryItems(params?: {
    search?: string;
    low_stock?: boolean;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<InventoryItemRecord>>> {
    return apiClient.get<PaginatedResponse<InventoryItemRecord>>('/inventory/items', params);
  },

  listRequisitions(params?: {
    status?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<RequisitionRecord>>> {
    return apiClient.get<PaginatedResponse<RequisitionRecord>>('/requisitions', params);
  },

  getRequisition(id: number): Promise<ApiResponse<RequisitionRecord>> {
    return apiClient.get<RequisitionRecord>(`/requisitions/${id}`);
  },

  listMedicalRecords(
    studentId: number,
    params?: { page?: number; per_page?: number },
  ): Promise<ApiResponse<PaginatedResponse<MedicalRecordRow> & { student_id: number }>> {
    return apiClient.get(`/students/${studentId}/medical-records`, params);
  },

  listVisitors(params?: {
    on_site?: boolean;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<VisitorRecord>>> {
    return apiClient.get<PaginatedResponse<VisitorRecord>>('/visitors', params);
  },
};
