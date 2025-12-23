import { apiClient } from './client';
import { Hostel, Room, RoomAllocation, HostelFilters } from '../types/hostel.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

export const hostelApi = {
    // ========== Hostels ==========
    async getHostels(filters?: HostelFilters): Promise<ApiResponse<PaginatedResponse<Hostel>>> {
        return apiClient.get<PaginatedResponse<Hostel>>('/hostels', filters);
    },

    async getHostel(id: number): Promise<ApiResponse<Hostel>> {
        return apiClient.get<Hostel>(`/hostels/${id}`);
    },

    async createHostel(data: any): Promise<ApiResponse<Hostel>> {
        return apiClient.post<Hostel>('/hostels', data);
    },

    async updateHostel(id: number, data: any): Promise<ApiResponse<Hostel>> {
        return apiClient.put<Hostel>(`/hostels/${id}`, data);
    },

    async deleteHostel(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/hostels/${id}`);
    },

    // ========== Rooms ==========
    async getRooms(filters?: { hostel_id?: number; status?: string }): Promise<ApiResponse<PaginatedResponse<Room>>> {
        return apiClient.get<PaginatedResponse<Room>>('/hostel-rooms', filters);
    },

    async getRoom(id: number): Promise<ApiResponse<Room>> {
        return apiClient.get<Room>(`/hostel-rooms/${id}`);
    },

    async createRoom(data: any): Promise<ApiResponse<Room>> {
        return apiClient.post<Room>('/hostel-rooms', data);
    },

    async updateRoom(id: number, data: any): Promise<ApiResponse<Room>> {
        return apiClient.put<Room>(`/hostel-rooms/${id}`, data);
    },

    async deleteRoom(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/hostel-rooms/${id}`);
    },

    // ========== Room Allocations ==========
    async getAllocations(filters?: { student_id?: number; hostel_id?: number; status?: string }): Promise<ApiResponse<PaginatedResponse<RoomAllocation>>> {
        return apiClient.get<PaginatedResponse<RoomAllocation>>('/room-allocations', filters);
    },

    async getAllocation(id: number): Promise<ApiResponse<RoomAllocation>> {
        return apiClient.get<RoomAllocation>(`/room-allocations/${id}`);
    },

    async allocateRoom(data: {
        student_id: number;
        hostel_id: number;
        room_id: number;
        bed_number?: string;
        allocation_date: string;
    }): Promise<ApiResponse<RoomAllocation>> {
        return apiClient.post<RoomAllocation>('/room-allocations', data);
    },

    async deallocateRoom(id: number): Promise<ApiResponse<RoomAllocation>> {
        return apiClient.post<RoomAllocation>(`/room-allocations/${id}/deallocate`);
    },

    // ========== Reports ==========
    async getHostelSummary(): Promise<ApiResponse<{
        total_hostels: number;
        total_capacity: number;
        total_occupied: number;
        total_available: number;
        occupancy_rate: number;
    }>> {
        return apiClient.get('/hostels/summary');
    },
};
