import axios, { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios';
import { API_BASE_URL, API_TIMEOUT } from '@env';
import { getToken, clearToken } from '@utils/storage';
import { ApiError, ApiResponse } from '@types/api.types';

class ApiClient {
    private client: AxiosInstance;

    constructor() {
        this.client = axios.create({
            baseURL: API_BASE_URL || 'http://localhost:8000/api',
            timeout: parseInt(API_TIMEOUT || '30000', 10),
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
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
                if (error.response?.status === 401) {
                    // Unauthorized - clear token and redirect to login
                    await clearToken();
                    // Navigation will be handled by AuthContext
                }

                const apiError: ApiError = {
                    message: error.response?.data?.message || error.message || 'An error occurred',
                    errors: error.response?.data?.errors,
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

    // Upload file
    async upload<T>(url: string, formData: FormData): Promise<ApiResponse<T>> {
        const response = await this.client.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return response.data;
    }
}

export const apiClient = new ApiClient();
