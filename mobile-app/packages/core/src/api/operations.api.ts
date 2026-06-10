import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';
import type { UploadFileInput } from './reports.api';

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
  category?: string | null;
  total_copies?: number;
  available_copies?: number;
  status: string;
}

export interface BorrowingRecord {
  id: number;
  status: string;
  is_overdue: boolean;
  book_title?: string | null;
  copy_number?: string | null;
  student_id?: number | null;
  student_name?: string | null;
  admission_number?: string | null;
  card_number?: string | null;
  borrowed_date?: string | null;
  due_date?: string | null;
  returned_date?: string | null;
  fine_amount?: number | null;
  can_return?: boolean;
  can_renew?: boolean;
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
  certificate_url?: string | null;
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
  assigned_staff_id?: number | null;
  notes?: string | null;
}

export interface FixedAssetPayload {
  asset_tag: string;
  name: string;
  category?: string;
  location?: string;
  serial_number?: string;
  purchase_date?: string;
  purchase_cost?: number;
  status?: 'active' | 'in_repair' | 'retired' | 'disposed';
  assigned_staff_id?: number | null;
  notes?: string;
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

  adjustInventoryStock(
    id: number,
    payload: { type: 'in' | 'out' | 'adjustment'; quantity: number; notes?: string },
  ): Promise<ApiResponse<InventoryItemRecord>> {
    return apiClient.post<InventoryItemRecord>(`/inventory/items/${id}/adjust`, payload);
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
    available_only?: boolean;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<LibraryBookRecord>>> {
    return apiClient.get<PaginatedResponse<LibraryBookRecord>>('/library/books', params);
  },

  listBorrowings(params?: {
    status?: string;
    student_id?: number;
    search?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<BorrowingRecord>>> {
    return apiClient.get<PaginatedResponse<BorrowingRecord>>('/library/borrowings', params);
  },

  issueBook(payload: {
    student_id: number;
    book_id: number;
    days?: number;
  }): Promise<ApiResponse<BorrowingRecord>> {
    return apiClient.post<BorrowingRecord>('/library/borrowings', payload);
  },

  returnBook(id: number, condition?: string): Promise<ApiResponse<BorrowingRecord>> {
    return apiClient.post<BorrowingRecord>(`/library/borrowings/${id}/return`, { condition });
  },

  renewBorrowing(id: number, days?: number): Promise<ApiResponse<BorrowingRecord>> {
    return apiClient.post<BorrowingRecord>(`/library/borrowings/${id}/renew`, { days });
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

  createRequisition(payload: {
    type: 'inventory' | 'requirement';
    purpose?: string;
    items: Array<{
      inventory_item_id?: number;
      item_name: string;
      brand?: string;
      quantity_requested: number;
      unit: string;
      purpose?: string;
    }>;
  }): Promise<ApiResponse<RequisitionRecord>> {
    return apiClient.post<RequisitionRecord>('/requisitions', payload);
  },

  listMedicalRecords(
    studentId: number,
    params?: { page?: number; per_page?: number },
  ): Promise<ApiResponse<PaginatedResponse<MedicalRecordRow> & { student_id: number }>> {
    return apiClient.get(`/students/${studentId}/medical-records`, params);
  },

  createMedicalRecord(
    studentId: number,
    payload: {
      record_type: 'vaccination' | 'checkup' | 'medication' | 'incident' | 'certificate' | 'other';
      record_date: string;
      title: string;
      description?: string;
      doctor_name?: string;
      clinic_hospital?: string;
      medication_name?: string;
      medication_dosage?: string;
      vaccination_name?: string;
      vaccination_date?: string;
      next_due_date?: string;
      notes?: string;
    },
  ): Promise<ApiResponse<MedicalRecordRow>> {
    return apiClient.post<MedicalRecordRow>(`/students/${studentId}/medical-records`, payload);
  },

  uploadMedicalCertificate(
    studentId: number,
    recordId: number,
    file: UploadFileInput,
  ): Promise<ApiResponse<MedicalRecordRow>> {
    const form = new FormData();
    form.append('file', { uri: file.uri, name: file.name, type: file.type } as unknown as Blob);
    return apiClient.postMultipart<MedicalRecordRow>(
      `/students/${studentId}/medical-records/${recordId}/certificate`,
      form,
    );
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

  updateAssetStatus(
    id: number,
    payload: { status: 'active' | 'in_repair' | 'retired' | 'disposed'; notes?: string },
  ): Promise<ApiResponse<FixedAssetRecord>> {
    return apiClient.post<FixedAssetRecord>(`/assets/${id}/status`, payload);
  },

  createAsset(payload: FixedAssetPayload): Promise<ApiResponse<FixedAssetRecord>> {
    return apiClient.post<FixedAssetRecord>('/assets', payload);
  },

  updateAsset(id: number, payload: FixedAssetPayload): Promise<ApiResponse<FixedAssetRecord>> {
    return apiClient.put<FixedAssetRecord>(`/assets/${id}`, payload);
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
