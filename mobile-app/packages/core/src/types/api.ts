/** Standard Laravel API envelope used across every endpoint. */
export interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

/** Normalized client-side error produced by the API client interceptor. */
export interface ApiError {
  message: string;
  /** Flattened field validation errors (HTTP 422). */
  errors?: Record<string, string[]>;
  status?: number;
}
