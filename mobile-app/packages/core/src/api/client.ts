import axios, {
  AxiosError,
  AxiosInstance,
  InternalAxiosRequestConfig,
} from 'axios';
import { API_BASE_URL, API_TIMEOUT_MS } from '../config/env';
import { clearToken, getToken } from '../storage/secureStorage';
import { touchSessionMeta } from '../storage/authStorage';
import type { ApiError, ApiResponse } from '../types';

type UnauthorizedCallback = () => void | Promise<void>;

/**
 * Shared HTTP client for the Admin App. Attaches the bearer token, normalizes
 * errors into `ApiError`, and reports a true 401 (expired/invalid token) up to the
 * auth layer via `setOnUnauthorized` so the session can be torn down gracefully.
 */
class ApiClient {
  private readonly client: AxiosInstance;
  private onUnauthorized: UnauthorizedCallback | null = null;
  /** Prevents recursive 401 → refresh → 401 loops when refresh itself fails. */
  private handlingUnauthorized = false;

  constructor() {
    const baseURL = API_BASE_URL;
    const isNgrok = __DEV__ && baseURL.includes('ngrok');
    this.client = axios.create({
      baseURL,
      timeout: Number.isFinite(API_TIMEOUT_MS) ? API_TIMEOUT_MS : 30000,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(isNgrok ? { 'ngrok-skip-browser-warning': '1' } : {}),
      },
    });
    this.setupInterceptors();
  }

  /** Register a handler invoked when the server rejects a request with 401. */
  setOnUnauthorized(cb: UnauthorizedCallback | null): void {
    this.onUnauthorized = cb;
  }

  private logRequest(config: InternalAxiosRequestConfig): void {
    if (!__DEV__) return;
    const method = (config.method ?? 'get').toUpperCase();
    const base = config.baseURL ?? '';
    const path = config.url ?? '';
    const params = config.params as Record<string, unknown> | undefined;
    const paramStr =
      params && Object.keys(params).length > 0 ? ` ${JSON.stringify(params)}` : '';
    console.log(`[API] → ${method} ${base}${path}${paramStr}`);
  }

  private logResponse(status: number, url: string, data?: unknown): void {
    if (!__DEV__) return;
    const preview =
      data != null && typeof data === 'object'
        ? JSON.stringify(data).slice(0, 400)
        : String(data ?? '');
    console.log(`[API] ← ${status} ${url}`, preview);
  }

  private logError(status: number | undefined, url: string, body: unknown, message: string): void {
    if (!__DEV__) return;
    // Use warn (not error) so transient Network Error / timeouts do not open the red LogBox overlay.
    console.warn(`[API] ✗ ${status ?? 'ERR'} ${url}`, { message, body });
  }

  private setupInterceptors(): void {
    this.client.interceptors.request.use(
      async (config: InternalAxiosRequestConfig) => {
        const token = await getToken();
        const headers = config.headers;
        const hasAuth =
          headers &&
          (typeof headers.get === 'function'
            ? headers.get('Authorization')
            : (headers as Record<string, string>).Authorization);
        if (token && headers && !hasAuth) {
          headers.Authorization = `Bearer ${token}`;
        }
        this.logRequest(config);
        return config;
      },
      (error) => Promise.reject(error),
    );

    this.client.interceptors.response.use(
      (response) => {
        if (response.status >= 200 && response.status < 300) {
          void touchSessionMeta();
        }
        this.logResponse(response.status, String(response.config.url ?? ''), response.data);
        return response;
      },
      async (error: AxiosError) => {
        const url = String(error.config?.url ?? '');
        const isAuthRoute =
          url.endsWith('/login') ||
          url.includes('/logout') ||
          url.includes('/auth/refresh') ||
          url.endsWith('/user');
        const isBenign401 = error.response?.status === 401 && isAuthRoute;
        if (!isBenign401) {
          this.logError(error.response?.status, url, error.response?.data, error.message);
        }

        if (error.response?.status === 401 && !isAuthRoute && !this.handlingUnauthorized) {
          this.handlingUnauthorized = true;
          try {
            await clearToken();
            await this.onUnauthorized?.();
          } finally {
            this.handlingUnauthorized = false;
          }
        }

        const data = error.response?.data as Record<string, unknown> | undefined;
        let message =
          (typeof data?.message === 'string' ? data.message : null) ||
          error.message ||
          'An error occurred';

        const errs = data?.errors as Record<string, string[] | string> | undefined;
        if (error.response?.status === 422 && errs && !Array.isArray(errs)) {
          const parts: string[] = [];
          for (const v of Object.values(errs)) {
            if (Array.isArray(v)) {
              parts.push(...v.map(String));
            } else if (v != null) {
              parts.push(String(v));
            }
          }
          if (parts.length) {
            message = parts.join('\n');
          }
        }

        const apiError: ApiError = {
          message,
          errors: data?.errors as ApiError['errors'],
          status: error.response?.status,
        };
        return Promise.reject(apiError);
      },
    );
  }

  async get<T>(url: string, params?: unknown): Promise<ApiResponse<T>> {
    const res = await this.client.get(url, { params });
    return res.data;
  }

  /** GET with an explicit bearer token (does not read from SecureStore). */
  async getWithToken<T>(url: string, token: string, params?: unknown): Promise<ApiResponse<T>> {
    const res = await this.client.get(url, {
      params,
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  }

  /** POST with an explicit bearer token (does not read from SecureStore). */
  async postWithToken<T>(url: string, token: string, data?: unknown): Promise<ApiResponse<T>> {
    const res = await this.client.post(url, data, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return res.data;
  }

  async post<T>(url: string, data?: unknown): Promise<ApiResponse<T>> {
    const res = await this.client.post(url, data);
    return res.data;
  }

  /** POST multipart/form-data (file uploads). */
  async postMultipart<T>(url: string, data: FormData): Promise<ApiResponse<T>> {
    const res = await this.client.post(url, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
      transformRequest: (d) => d,
    });
    return res.data;
  }

  async put<T>(url: string, data?: unknown): Promise<ApiResponse<T>> {
    const res = await this.client.put(url, data);
    return res.data;
  }

  async patch<T>(url: string, data?: unknown): Promise<ApiResponse<T>> {
    const res = await this.client.patch(url, data);
    return res.data;
  }

  async delete<T>(url: string): Promise<ApiResponse<T>> {
    const res = await this.client.delete(url);
    return res.data;
  }
}

export const apiClient = new ApiClient();
