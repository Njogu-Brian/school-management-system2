import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface TransportRouteSummary {
  id: number;
  name: string;
  code?: string | null;
  description?: string | null;
  vehicle_id?: number | null;
  vehicle_registration?: string | null;
  driver_id?: number | null;
  driver_name?: string | null;
  status?: string;
  drop_points?: Array<{
    id: number;
    name: string;
    sequence?: number;
    pickup_time?: string | null;
  }>;
}

export interface VehicleRecord {
  id: number;
  vehicle_number: string;
  driver_name?: string | null;
  make?: string | null;
  model?: string | null;
  type?: string | null;
  capacity?: number | null;
  chassis_number?: string | null;
  trips_count?: number;
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
  description?: string | null;
  quantity: number;
  min_stock_level: number;
  unit?: string | null;
  unit_cost?: number | null;
  is_low_stock: boolean;
  is_active?: boolean;
  location?: string | null;
  updated_at?: string | null;
}

export interface RequirementsStudentRow {
  id: number;
  admission_number: string;
  full_name: string;
  class_name?: string | null;
  stream_name?: string | null;
  avatar?: string | null;
  is_new_joiner: boolean;
  can_teacher_receive: boolean;
}

export interface LibraryBookRecord {
  id: number;
  title: string;
  author?: string | null;
  isbn?: string | null;
  status: string;
}

export interface RequisitionRecord {
  id: number;
  requisition_number: string;
  type: string;
  purpose?: string | null;
  status: string;
  rejection_reason?: string | null;
  requested_by?: string | null;
  approved_by?: string | null;
  requested_at?: string | null;
  approved_at?: string | null;
  fulfilled_at?: string | null;
  can_approve?: boolean;
  can_reject?: boolean;
  items?: Array<{
    id: number;
    item_name: string;
    quantity_requested: number;
    quantity_approved?: number | null;
    quantity_issued?: number | null;
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
  organization?: string | null;
  purpose?: string | null;
  host_name?: string | null;
  badge_number?: string | null;
  notes?: string | null;
  checked_in_at?: string | null;
  checked_out_at?: string | null;
  on_site: boolean;
}

export interface FixedAssetRecord {
  id: number;
  asset_tag?: string | null;
  name: string;
  category?: string | null;
  location?: string | null;
  status?: string | null;
  assigned_to?: string | null;
  serial_number?: string | null;
  purchase_date?: string | null;
  purchase_cost?: number | null;
  notes?: string | null;
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

  createRoute(payload: {
    vehicle_id: number;
    name: string;
    driver_id?: number;
    direction?: string;
    day_of_week?: number[];
  }): Promise<ApiResponse<TransportRouteSummary>> {
    return apiClient.post<TransportRouteSummary>('/routes', payload);
  },

  updateRoute(
    id: number,
    payload: {
      vehicle_id: number;
      name: string;
      driver_id?: number;
      direction?: string;
      day_of_week?: number[];
    },
  ): Promise<ApiResponse<TransportRouteSummary>> {
    return apiClient.put<TransportRouteSummary>(`/routes/${id}`, payload);
  },

  deleteRoute(id: number): Promise<ApiResponse<void>> {
    return apiClient.delete(`/routes/${id}`);
  },

  listVehicles(params?: {
    search?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<VehicleRecord>>> {
    return apiClient.get<PaginatedResponse<VehicleRecord>>('/vehicles', params);
  },

  getVehicle(id: number): Promise<ApiResponse<VehicleRecord>> {
    return apiClient.get<VehicleRecord>(`/vehicles/${id}`);
  },

  createVehicle(payload: {
    vehicle_number: string;
    driver_name?: string;
    make?: string;
    model?: string;
    type?: string;
    capacity?: number;
    chassis_number?: string;
  }): Promise<ApiResponse<VehicleRecord>> {
    return apiClient.post<VehicleRecord>('/vehicles', payload);
  },

  updateVehicle(
    id: number,
    payload: {
      vehicle_number: string;
      driver_name?: string;
      make?: string;
      model?: string;
      type?: string;
      capacity?: number;
      chassis_number?: string;
    },
  ): Promise<ApiResponse<VehicleRecord>> {
    return apiClient.put<VehicleRecord>(`/vehicles/${id}`, payload);
  },

  deleteVehicle(id: number): Promise<ApiResponse<void>> {
    return apiClient.delete(`/vehicles/${id}`);
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

  getInventoryItem(id: number): Promise<ApiResponse<InventoryItemRecord>> {
    return apiClient.get<InventoryItemRecord>(`/inventory/items/${id}`);
  },

  listRequirementsStudents(params?: {
    search?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<RequirementsStudentRow>>> {
    return apiClient.get<PaginatedResponse<RequirementsStudentRow>>(
      '/teacher/requirements/students',
      params,
    );
  },

  listLibraryBooks(params?: {
    search?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<LibraryBookRecord>>> {
    return apiClient.get<PaginatedResponse<LibraryBookRecord>>('/library/books', params);
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
    date?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<VisitorRecord>>> {
    return apiClient.get<PaginatedResponse<VisitorRecord>>('/visitors', params);
  },

  getVisitor(id: number): Promise<ApiResponse<VisitorRecord>> {
    return apiClient.get<VisitorRecord>(`/visitors/${id}`);
  },

  checkInVisitor(payload: {
    visitor_name: string;
    phone?: string;
    id_number?: string;
    organization?: string;
    purpose?: string;
    host_name?: string;
    host_staff_id?: number;
    badge_number?: string;
    notes?: string;
  }): Promise<ApiResponse<VisitorRecord>> {
    return apiClient.post<VisitorRecord>('/visitors', payload);
  },

  checkOutVisitor(id: number): Promise<ApiResponse<VisitorRecord>> {
    return apiClient.post<VisitorRecord>(`/visitors/${id}/checkout`);
  },

  listAssets(params?: {
    search?: string;
    status?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<FixedAssetRecord>>> {
    return apiClient.get<PaginatedResponse<FixedAssetRecord>>('/assets', params);
  },

  getAsset(id: number): Promise<ApiResponse<FixedAssetRecord>> {
    return apiClient.get<FixedAssetRecord>(`/assets/${id}`);
  },

  approveRequisition(
    id: number,
    payload?: { items?: Array<{ id: number; quantity_approved: number }> },
  ): Promise<ApiResponse<RequisitionRecord>> {
    return apiClient.post<RequisitionRecord>(`/requisitions/${id}/approve`, payload ?? {});
  },

  rejectRequisition(id: number, rejection_reason: string): Promise<ApiResponse<RequisitionRecord>> {
    return apiClient.post<RequisitionRecord>(`/requisitions/${id}/reject`, { rejection_reason });
  },
};
