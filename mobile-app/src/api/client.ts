import axios, { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios';
import { API_BASE_URL, API_TIMEOUT } from '@utils/env';
import { getToken, clearToken } from '@utils/storage';
import { ApiError, ApiResponse } from '@types/api.types';

type UnauthorizedCallback = () => void | Promise<void>;

class ApiClient {
    private client: AxiosInstance;
    private onUnauthorized: UnauthorizedCallback | null = null;

    setOnUnauthorized(cb: UnauthorizedCallback | null) {
        this.onUnauthorized = cb;
    }

    constructor() {
        const baseURL = API_BASE_URL;
        const isNgrok = __DEV__ && baseURL.includes('ngrok');
        this.client = axios.create({
            baseURL,
            timeout: parseInt(API_TIMEOUT || '30000', 10),
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(isNgrok && { 'ngrok-skip-browser-warning': '1' }),
            },
        });

        this.setupInterceptors();
    }

    private setupInterceptors() {
        // Request interceptor - attach token
        this.client.interceptors.request.use(
            async (config: InternalAxiosRequestConfig) => {
                const token = await getToken();
                if (token && config.headers) {
                    config.headers.Authorization = `Bearer ${token}`;
                }
                // Let the runtime set multipart boundary (default JSON Content-Type breaks file uploads)
                const body = config.data as unknown;
                if (
                    body &&
                    typeof body === 'object' &&
                    typeof (body as FormData).append === 'function' &&
                    config.headers
                ) {
                    delete config.headers['Content-Type'];
                }
                return config;
            },
            (error) => {
                return Promise.reject(error);
            }
        );

        // Response interceptor - handle errors
        this.client.interceptors.response.use(
            (response) => {
                return response;
            },
            async (error: AxiosError) => {
                const url = String(error.config?.url ?? '');
                const isAuthRoute =
                    url.endsWith('/login') ||
                    url.includes('/logout') ||
                    url.endsWith('/register');

                if (error.response?.status === 401 && !isAuthRoute) {
                    await clearToken();
                    this.onUnauthorized?.();
                }

                const data = error.response?.data as Record<string, unknown> | undefined;
                let message =
                    (typeof data?.message === 'string' ? data.message : null) ||
                    error.message ||
                    'An error occurred';
                const errs = data?.errors as Record<string, string[] | string> | undefined;
                if (
                    error.response?.status === 422 &&
                    errs &&
                    typeof errs === 'object' &&
                    !Array.isArray(errs)
                ) {
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
            }
        );
    }

    // GET request
    async get<T>(url: string, params?: any): Promise<ApiResponse<T>> {
        const response = await this.client.get(url, { params });
        return response.data;
    }

    // POST request
    async post<T>(url: string, data?: any): Promise<ApiResponse<T>> {
        const response = await this.client.post(url, data);
        return response.data;
    }

    // PUT request
    async put<T>(url: string, data?: any): Promise<ApiResponse<T>> {
        const response = await this.client.put(url, data);
        return response.data;
    }

    // DELETE request
    async delete<T>(url: string): Promise<ApiResponse<T>> {
        const response = await this.client.delete(url);
        return response.data;
    }

    // PATCH request
    async patch<T>(url: string, data?: any): Promise<ApiResponse<T>> {
        const response = await this.client.patch(url, data);
        return response.data;
    }

    // Upload file (multipart; Content-Type stripped in interceptor for correct boundary)
    async upload<T>(url: string, formData: FormData): Promise<ApiResponse<T>> {
        const response = await this.client.post(url, formData);
        return response.data;
    }
}

export const apiClient = new ApiClient();
