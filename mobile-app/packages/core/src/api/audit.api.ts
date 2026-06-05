import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface AuditTrailRecord {
  id: string;
  user: string;
  user_id?: number | null;
  action: string;
  module: string;
  timestamp: string;
  target: string;
  source: 'activity' | 'audit';
  before_values?: Record<string, unknown> | null;
  after_values?: Record<string, unknown> | null;
  metadata?: Record<string, unknown>;
}

export interface AuditTrailFilters {
  user_id?: number;
  module?: string;
  search?: string;
  date_from?: string;
  date_to?: string;
}

export const auditApi = {
  list(
    filters?: AuditTrailFilters & { page?: number; per_page?: number },
  ): Promise<ApiResponse<PaginatedResponse<AuditTrailRecord>>> {
    return apiClient.get<PaginatedResponse<AuditTrailRecord>>('/audit-trail', {
      page: filters?.page ?? 1,
      per_page: filters?.per_page ?? 30,
      user_id: filters?.user_id,
      module: filters?.module,
      search: filters?.search,
      date_from: filters?.date_from,
      date_to: filters?.date_to,
    });
  },

  show(id: string): Promise<ApiResponse<AuditTrailRecord>> {
    return apiClient.get<AuditTrailRecord>(`/audit-trail/${id}`);
  },
};
