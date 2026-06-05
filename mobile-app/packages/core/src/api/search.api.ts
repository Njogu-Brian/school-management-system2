import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface SearchHit {
  id: string;
  module: string;
  title: string;
  subtitle?: string | null;
  route: string;
  metadata?: Record<string, unknown>;
}

export interface SearchSuggestHit {
  id: string;
  title: string;
  module: string;
}

export type SearchModuleFilter =
  | 'all'
  | 'students'
  | 'staff'
  | 'finance'
  | 'operations'
  | 'communication';

export const searchApi = {
  search(params: {
    query: string;
    module?: SearchModuleFilter;
    page?: number;
    limit?: number;
  }): Promise<ApiResponse<PaginatedResponse<SearchHit>>> {
    return apiClient.get<PaginatedResponse<SearchHit>>('/search', {
      query: params.query,
      module: params.module ?? 'all',
      page: params.page ?? 1,
      limit: params.limit ?? 20,
    });
  },

  suggest(query: string, limit = 6): Promise<ApiResponse<SearchSuggestHit[]>> {
    return apiClient.get<SearchSuggestHit[]>('/search/suggest', { query, limit });
  },
};
