import { apiClient } from './client';
import {
    Product,
    ProductVariant,
    Order,
    Uniform,
    POSFilters,
} from '../types/pos.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

export const posApi = {
    // ========== Products ==========
    async getProducts(filters?: POSFilters): Promise<ApiResponse<PaginatedResponse<Product>>> {
        return apiClient.get<PaginatedResponse<Product>>('/pos/products', filters);
    },

    async getProduct(id: number): Promise<ApiResponse<Product>> {
        return apiClient.get<Product>(`/pos/products/${id}`);
    },

    async createProduct(data: any): Promise<ApiResponse<Product>> {
        return apiClient.post<Product>('/pos/products', data);
    },

    async updateProduct(id: number, data: any): Promise<ApiResponse<Product>> {
        return apiClient.put<Product>(`/pos/products/${id}`, data);
    },

    async deleteProduct(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/pos/products/${id}`);
    },

    // ========== Product Variants ==========
    async getVariants(productId: number): Promise<ApiResponse<ProductVariant[]>> {
        return apiClient.get<ProductVariant[]>(`/pos/products/${productId}/variants`);
    },

    async createVariant(productId: number, data: any): Promise<ApiResponse<ProductVariant>> {
        return apiClient.post<ProductVariant>(`/pos/products/${productId}/variants`, data);
    },

    // ========== Orders ==========
    async getOrders(filters?: POSFilters): Promise<ApiResponse<PaginatedResponse<Order>>> {
        return apiClient.get<PaginatedResponse<Order>>('/pos/orders', filters);
    },

    async getOrder(id: number): Promise<ApiResponse<Order>> {
        return apiClient.get<Order>(`/pos/orders/${id}`);
    },

    async createOrder(data: {
        customer_id?: number;
        customer_type: string;
        customer_name?: string;
        items: { product_id: number; variant_id?: number; quantity: number }[];
        payment_method?: string;
        notes?: string;
    }): Promise<ApiResponse<Order>> {
        return apiClient.post<Order>('/pos/orders', data);
    },

    async updateOrderStatus(id: number, status: string): Promise<ApiResponse<Order>> {
        return apiClient.put<Order>(`/pos/orders/${id}/status`, { status });
    },

    async cancelOrder(id: number, reason: string): Promise<ApiResponse<Order>> {
        return apiClient.post<Order>(`/pos/orders/${id}/cancel`, { reason });
    },

    // ========== Uniforms ==========
    async getUniforms(filters?: { gender?: string; category?: string }): Promise<ApiResponse<Uniform[]>> {
        return apiClient.get<Uniform[]>('/pos/uniforms', filters);
    },

    async getUniform(id: number): Promise<ApiResponse<Uniform>> {
        return apiClient.get<Uniform>(`/pos/uniforms/${id}`);
    },

    async createUniform(data: any): Promise<ApiResponse<Uniform>> {
        return apiClient.post<Uniform>('/pos/uniforms', data);
    },

    async updateUniform(id: number, data: any): Promise<ApiResponse<Uniform>> {
        return apiClient.put<Uniform>(`/pos/uniforms/${id}`, data);
    },

    // ========== Public Shop (Token-based) ==========
    async getPublicProducts(token: string): Promise<ApiResponse<Product[]>> {
        return apiClient.get<Product[]>(`/public/shop/products?token=${token}`);
    },

    async createPublicOrder(token: string, data: any): Promise<ApiResponse<Order>> {
        return apiClient.post<Order>(`/public/shop/orders?token=${token}`, data);
    },

    // ========== Reports ==========
    async getPOSSummary(filters?: { date_from?: string; date_to?: string }): Promise<ApiResponse<{
        total_sales: number;
        total_orders: number;
        pending_orders: number;
        revenue_today: number;
        revenue_this_week: number;
        revenue_this_month: number;
    }>> {
        return apiClient.get('/pos/summary', filters);
    },
};
