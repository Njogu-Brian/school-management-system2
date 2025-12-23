import { apiClient } from './client';
import {
    Document,
    DocumentTemplate,
    ReportCard,
    Certificate,
    DocumentFilters,
} from '../types/documents.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

export const documentsApi = {
    // ========== Documents ==========
    async getDocuments(filters?: DocumentFilters): Promise<ApiResponse<PaginatedResponse<Document>>> {
        return apiClient.get<PaginatedResponse<Document>>('/documents', filters);
    },

    async getDocument(id: number): Promise<ApiResponse<Document>> {
        return apiClient.get<Document>(`/documents/${id}`);
    },

    async uploadDocument(formData: FormData): Promise<ApiResponse<Document>> {
        return apiClient.upload('/documents', formData);
    },

    async updateDocument(id: number, data: any): Promise<ApiResponse<Document>> {
        return apiClient.put<Document>(`/documents/${id}`, data);
    },

    async deleteDocument(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/documents/${id}`);
    },

    async downloadDocument(id: number): Promise<ApiResponse<Blob>> {
        return apiClient.get<Blob>(`/documents/${id}/download`);
    },

    // ========== Document Templates ==========
    async getTemplates(filters?: { type?: string; category?: string }): Promise<ApiResponse<DocumentTemplate[]>> {
        return apiClient.get<DocumentTemplate[]>('/document-templates', filters);
    },

    async getTemplate(id: number): Promise<ApiResponse<DocumentTemplate>> {
        return apiClient.get<DocumentTemplate>(`/document-templates/${id}`);
    },

    async generateFromTemplate(templateId: number, data: any): Promise<ApiResponse<Blob>> {
        return apiClient.post<Blob>(`/document-templates/${templateId}/generate`, data);
    },

    // ========== Report Cards ==========
    async getReportCards(filters?: {
        student_id?: number;
        class_id?: number;
        term_id?: number;
        status?: string;
    }): Promise<ApiResponse<PaginatedResponse<ReportCard>>> {
        return apiClient.get<PaginatedResponse<ReportCard>>('/report-cards', filters);
    },

    async generateReportCard(data: {
        student_id: number;
        term_id: number;
        academic_year_id: number;
    }): Promise<ApiResponse<ReportCard>> {
        return apiClient.post<ReportCard>('/report-cards/generate', data);
    },

    async generateBulkReportCards(data: {
        class_id: number;
        term_id: number;
        academic_year_id: number;
    }): Promise<ApiResponse<{ count: number; message: string }>> {
        return apiClient.post('/report-cards/generate-bulk', data);
    },

    async publishReportCard(id: number): Promise<ApiResponse<ReportCard>> {
        return apiClient.post<ReportCard>(`/report-cards/${id}/publish`);
    },

    async downloadReportCard(id: number): Promise<ApiResponse<Blob>> {
        return apiClient.get<Blob>(`/report-cards/${id}/download`);
    },

    // ========== Certificates ==========
    async getCertificates(filters?: { student_id?: number; type?: string }): Promise<ApiResponse<PaginatedResponse<Certificate>>> {
        return apiClient.get<PaginatedResponse<Certificate>>('/certificates', filters);
    },

    async issueCertificate(data: {
        student_id: number;
        type: string;
        issue_date: string;
    }): Promise<ApiResponse<Certificate>> {
        return apiClient.post<Certificate>('/certificates', data);
    },

    async downloadCertificate(id: number): Promise<ApiResponse<Blob>> {
        return apiClient.get<Blob>(`/certificates/${id}/download`);
    },

    // ========== Export/Import ==========
    async exportToExcel(type: string, filters?: any): Promise<ApiResponse<Blob>> {
        return apiClient.post<Blob>(`/export/${type}/excel`, filters);
    },

    async exportToPDF(type: string, filters?: any): Promise<ApiResponse<Blob>> {
        return apiClient.post<Blob>(`/export/${type}/pdf`, filters);
    },

    async importFromExcel(type: string, file: FormData): Promise<ApiResponse<{ success: number; errors: any[] }>> {
        return apiClient.upload(`/import/${type}/excel`, file);
    },
};
