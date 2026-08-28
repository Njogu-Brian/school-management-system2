import { apiClient } from './client';
import type { ApiResponse } from '../types';
import type { KemisOptions } from '../types/kemis';

export const kemisApi = {
  /** `GET /kemis/options` */
  options(): Promise<ApiResponse<KemisOptions>> {
    return apiClient.get<KemisOptions>('/kemis/options');
  },
};
