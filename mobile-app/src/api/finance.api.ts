import { apiClient } from './client';
import {
    FeeStructure,
    Invoice,
    Payment,
    FinanceTransaction,
    StudentStatement,
    FinanceFilters,
} from '../types/finance.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

/** Mobile API client aligned with `routes/api.php` (sanctum). */
export const financeApi = {
    async getFeeStructures(filters?: FinanceFilters): Promise<ApiResponse<PaginatedResponse<FeeStructure>>> {
        return apiClient.get<PaginatedResponse<FeeStructure>>('/fee-structures', filters);
    },

    async getInvoices(filters?: FinanceFilters): Promise<ApiResponse<PaginatedResponse<Invoice>>> {
        return apiClient.get<PaginatedResponse<Invoice>>('/invoices', filters);
    },

    async getInvoice(id: number): Promise<ApiResponse<Invoice>> {
        return apiClient.get<Invoice>(`/invoices/${id}`);
    },

    async getPayments(filters?: FinanceFilters & { student_id?: number }): Promise<ApiResponse<PaginatedResponse<Payment>>> {
        return apiClient.get<PaginatedResponse<Payment>>('/payments', filters);
    },

    async getPayment(id: number): Promise<ApiResponse<Payment>> {
        return apiClient.get<Payment>(`/payments/${id}`);
    },

    async createPayment(data: {
        student_id: number;
        amount: number;
        payment_method: string;
        payment_date: string;
        reference_number?: string;
        notes?: string;
        invoice_allocations?: { invoice_id: number; amount: number }[];
    }): Promise<ApiResponse<Payment>> {
        return apiClient.post<Payment>('/payments', data);
    },

    async getFinanceTransactions(
        filters?: FinanceFilters & { view?: string }
    ): Promise<ApiResponse<PaginatedResponse<FinanceTransaction>>> {
        return apiClient.get<PaginatedResponse<FinanceTransaction>>('/finance/transactions', filters);
    },

    async getFinanceTransaction(
        id: number,
        type: 'bank' | 'c2b'
    ): Promise<ApiResponse<Record<string, unknown>>> {
        return apiClient.get<Record<string, unknown>>(`/finance/transactions/${id}`, { type });
    },

    async markTransactionsAsSwimming(transactionIds: number[]): Promise<
        ApiResponse<{ message?: string; errors?: string[]; marked?: number; processed?: number; skipped?: number }>
    > {
        return apiClient.post('/finance/transactions/mark-swimming', { transaction_ids: transactionIds });
    },

    async shareFinanceTransaction(
        id: number,
        allocations: { student_id: number; amount: number }[],
        type: 'bank' | 'c2b'
    ): Promise<ApiResponse<{ message?: string }>> {
        return apiClient.post(`/finance/transactions/${id}/share`, { type, allocations });
    },

    async confirmFinanceTransaction(
        id: number,
        type: 'bank' | 'c2b'
    ): Promise<ApiResponse<{ message?: string; receipt_ids?: number[]; payment_conflict?: unknown }>> {
        return apiClient.post(`/finance/transactions/${id}/confirm`, { type });
    },

    async rejectFinanceTransaction(
        id: number,
        type: 'bank' | 'c2b'
    ): Promise<ApiResponse<{ message?: string }>> {
        return apiClient.post(`/finance/transactions/${id}/reject`, { type });
    },

    async getStudentStatement(
        studentId: number,
        year?: number
    ): Promise<ApiResponse<StudentStatement & { year?: number }>> {
        return apiClient.get<StudentStatement & { year?: number }>(`/students/${studentId}/statement`, {
            year: year ?? new Date().getFullYear(),
        });
    },

    async getFinanceSummary(filters?: { date_from?: string; date_to?: string }): Promise<ApiResponse<{
        total_invoiced: number;
        total_paid: number;
        total_outstanding: number;
        payments_today: number;
        payments_this_week: number;
        payments_this_month: number;
    }>> {
        try {
            return await apiClient.get('/finance/summary', filters);
        } catch (error: any) {
            if (error?.status === 404) {
                return {
                    success: true,
                    data: {
                        total_invoiced: 0,
                        total_paid: 0,
                        total_outstanding: 0,
                        payments_today: 0,
                        payments_this_week: 0,
                        payments_this_month: 0,
                    },
                    message: 'Finance summary endpoint unavailable; using fallback values.',
                };
            }
            throw error;
        }
    },

    async mpesaPrompt(
        studentId: number,
        body: {
            phone_number: string;
            amount: number;
            invoice_id?: number | null;
            notes?: string;
            is_swimming?: boolean;
        }
    ): Promise<
        ApiResponse<{
            transaction_id: number;
            waiting_url: string | null;
            status_poll_url: string | null;
        }>
    > {
        return apiClient.post(`/students/${studentId}/mpesa/prompt`, body);
    },

    async getMpesaPaymentLink(studentId: number): Promise<
        ApiResponse<{ payment_link_id: number; url: string; short_url: string }>
    > {
        return apiClient.get(`/students/${studentId}/mpesa/payment-link`);
    },
};
