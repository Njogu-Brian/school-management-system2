import { apiClient } from './client';
import {
    InventoryItem,
    StockAdjustment,
    Requisition,
    StudentRequirement,
    InventoryFilters,
} from '../types/inventory.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

export const inventoryApi = {
    // ========== Inventory Items ==========
    async getItems(filters?: InventoryFilters): Promise<ApiResponse<PaginatedResponse<InventoryItem>>> {
        return apiClient.get<PaginatedResponse<InventoryItem>>('/inventory/items', filters);
    },

    async getItem(id: number): Promise<ApiResponse<InventoryItem>> {
        return apiClient.get<InventoryItem>(`/inventory/items/${id}`);
    },

    async createItem(data: any): Promise<ApiResponse<InventoryItem>> {
        return apiClient.post<InventoryItem>('/inventory/items', data);
    },

    async updateItem(id: number, data: any): Promise<ApiResponse<InventoryItem>> {
        return apiClient.put<InventoryItem>(`/inventory/items/${id}`, data);
    },

    async deleteItem(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/inventory/items/${id}`);
    },

    // ========== Stock Adjustments ==========
    async getAdjustments(filters?: { item_id?: number; date_from?: string; date_to?: string }): Promise<ApiResponse<PaginatedResponse<StockAdjustment>>> {
        return apiClient.get<PaginatedResponse<StockAdjustment>>('/inventory/adjustments', filters);
    },

    async createAdjustment(data: {
        item_id: number;
        type: string;
        quantity: number;
        reason: string;
        date: string;
    }): Promise<ApiResponse<StockAdjustment>> {
        return apiClient.post<StockAdjustment>('/inventory/adjustments', data);
    },

    // ========== Requisitions ==========
    async getRequisitions(filters?: { status?: string; department?: string }): Promise<ApiResponse<PaginatedResponse<Requisition>>> {
        return apiClient.get<PaginatedResponse<Requisition>>('/requisitions', filters);
    },

    async getRequisition(id: number): Promise<ApiResponse<Requisition>> {
        return apiClient.get<Requisition>(`/requisitions/${id}`);
    },

    async createRequisition(data: any): Promise<ApiResponse<Requisition>> {
        return apiClient.post<Requisition>('/requisitions', data);
    },

    async approveRequisition(id: number): Promise<ApiResponse<Requisition>> {
        return apiClient.post<Requisition>(`/requisitions/${id}/approve`);
    },

    async rejectRequisition(id: number, reason: string): Promise<ApiResponse<Requisition>> {
        return apiClient.post<Requisition>(`/requisitions/${id}/reject`, { reason });
    },

    async fulfillRequisition(id: number, items: any[]): Promise<ApiResponse<Requisition>> {
        return apiClient.post<Requisition>(`/requisitions/${id}/fulfill`, { items });
    },

    // ========== Student Requirements ==========
    async getStudentRequirements(filters?: { class_id?: number; term_id?: number }): Promise<ApiResponse<StudentRequirement[]>> {
        return apiClient.get<StudentRequirement[]>('/student-requirements', filters);
    },

    async createStudentRequirement(data: any): Promise<ApiResponse<StudentRequirement>> {
        return apiClient.post<StudentRequirement>('/student-requirements', data);
    },

    // ========== Reports ==========
    async getInventorySummary(): Promise<ApiResponse<{
        total_items: number;
        low_stock_items: number;
        out_of_stock_items: number;
        total_value: number;
        pending_requisitions: number;
    }>> {
        return apiClient.get('/inventory/summary');
    },
};
