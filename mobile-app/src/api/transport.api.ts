import { apiClient } from './client';
import {
    Vehicle,
    Route,
    DropPoint,
    Trip,
    StudentRouteAssignment,
    TransportFilters,
} from '../types/transport.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

export const transportApi = {
    // ========== Vehicles ==========
    async getVehicles(filters?: TransportFilters): Promise<ApiResponse<PaginatedResponse<Vehicle>>> {
        return apiClient.get<PaginatedResponse<Vehicle>>('/vehicles', filters);
    },

    async getVehicle(id: number): Promise<ApiResponse<Vehicle>> {
        return apiClient.get<Vehicle>(`/vehicles/${id}`);
    },

    async createVehicle(data: any): Promise<ApiResponse<Vehicle>> {
        return apiClient.post<Vehicle>('/vehicles', data);
    },

    async updateVehicle(id: number, data: any): Promise<ApiResponse<Vehicle>> {
        return apiClient.put<Vehicle>(`/vehicles/${id}`, data);
    },

    async deleteVehicle(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/vehicles/${id}`);
    },

    // ========== Routes ==========
    async getRoutes(filters?: TransportFilters): Promise<ApiResponse<PaginatedResponse<Route>>> {
        return apiClient.get<PaginatedResponse<Route>>('/routes', filters);
    },

    async getRoute(id: number): Promise<ApiResponse<Route>> {
        return apiClient.get<Route>(`/routes/${id}`);
    },

    async createRoute(data: any): Promise<ApiResponse<Route>> {
        return apiClient.post<Route>('/routes', data);
    },

    async updateRoute(id: number, data: any): Promise<ApiResponse<Route>> {
        return apiClient.put<Route>(`/routes/${id}`, data);
    },

    async deleteRoute(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/routes/${id}`);
    },

    // ========== Drop Points ==========
    async getDropPoints(routeId: number): Promise<ApiResponse<DropPoint[]>> {
        return apiClient.get<DropPoint[]>(`/routes/${routeId}/drop-points`);
    },

    async createDropPoint(routeId: number, data: any): Promise<ApiResponse<DropPoint>> {
        return apiClient.post<DropPoint>(`/routes/${routeId}/drop-points`, data);
    },

    async updateDropPoint(id: number, data: any): Promise<ApiResponse<DropPoint>> {
        return apiClient.put<DropPoint>(`/drop-points/${id}`, data);
    },

    async deleteDropPoint(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/drop-points/${id}`);
    },

    // ========== Trips ==========
    async getTrips(filters?: TransportFilters): Promise<ApiResponse<PaginatedResponse<Trip>>> {
        return apiClient.get<PaginatedResponse<Trip>>('/trips', filters);
    },

    async getTrip(id: number): Promise<ApiResponse<Trip>> {
        return apiClient.get<Trip>(`/trips/${id}`);
    },

    async createTrip(data: any): Promise<ApiResponse<Trip>> {
        return apiClient.post<Trip>('/trips', data);
    },

    async startTrip(id: number): Promise<ApiResponse<Trip>> {
        return apiClient.post<Trip>(`/trips/${id}/start`);
    },

    async completeTrip(id: number, data: {
        students_picked?: number;
        students_dropped?: number;
        notes?: string;
    }): Promise<ApiResponse<Trip>> {
        return apiClient.post<Trip>(`/trips/${id}/complete`, data);
    },

    async cancelTrip(id: number, reason: string): Promise<ApiResponse<Trip>> {
        return apiClient.post<Trip>(`/trips/${id}/cancel`, { reason });
    },

    // ========== Student Route Assignments ==========
    async getStudentAssignments(filters?: {
        student_id?: number;
        route_id?: number;
        status?: string;
    }): Promise<ApiResponse<PaginatedResponse<StudentRouteAssignment>>> {
        return apiClient.get<PaginatedResponse<StudentRouteAssignment>>('/student-route-assignments', filters);
    },

    async assignStudentToRoute(data: {
        student_id: number;
        route_id: number;
        drop_point_id?: number;
        start_date: string;
    }): Promise<ApiResponse<StudentRouteAssignment>> {
        return apiClient.post<StudentRouteAssignment>('/student-route-assignments', data);
    },

    async updateAssignment(id: number, data: any): Promise<ApiResponse<StudentRouteAssignment>> {
        return apiClient.put<StudentRouteAssignment>(`/student-route-assignments/${id}`, data);
    },

    async deactivateAssignment(id: number): Promise<ApiResponse<StudentRouteAssignment>> {
        return apiClient.post<StudentRouteAssignment>(`/student-route-assignments/${id}/deactivate`);
    },

    // ========== Reports ==========
    async getTransportSummary(): Promise<ApiResponse<{
        total_vehicles: number;
        active_routes: number;
        total_students_assigned: number;
        trips_today: number;
    }>> {
        return apiClient.get('/transport/summary');
    },
};
