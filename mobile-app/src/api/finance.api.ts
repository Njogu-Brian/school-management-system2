import { apiClient } from './client';
import {
    FeeStructure,
    Votehead,
    Invoice,
    Payment,
    FinanceTransaction,
    StudentStatement,
    PaymentPlan,
    OnlinePaymentRequest,
    OnlinePaymentResponse,
    FinanceFilters,
} from '@types/finance.types';
import { ApiResponse, PaginatedResponse } from '@types/api.types';

export const financeApi = {
    // ========== Fee Structures ==========
    async getFeeStructures(filters?: FinanceFilters): Promise<ApiResponse<PaginatedResponse<FeeStructure>>> {
        return apiClient.get<PaginatedResponse<FeeStructure>>('/fee-structures', filters);
    },

    async getFeeStructure(id: number): Promise<ApiResponse<FeeStructure>> {
        return apiClient.get<FeeStructure>(`/fee-structures/${id}`);
    },

    async createFeeStructure(data: any): Promise<ApiResponse<FeeStructure>> {
        return apiClient.post<FeeStructure>('/fee-structures', data);
    },

    async updateFeeStructure(id: number, data: any): Promise<ApiResponse<FeeStructure>> {
        return apiClient.put<FeeStructure>(`/fee-structures/${id}`, data);
    },

    async deleteFeeStructure(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/fee-structures/${id}`);
    },

    // ========== Voteheads ==========
    async getVoteheads(): Promise<ApiResponse<Votehead[]>> {
        return apiClient.get<Votehead[]>('/voteheads');
    },

    // ========== Invoices ==========
    async getInvoices(filters?: FinanceFilters): Promise<ApiResponse<PaginatedResponse<Invoice>>> {
        return apiClient.get<PaginatedResponse<Invoice>>('/invoices', filters);
    },

    async getInvoice(id: number): Promise<ApiResponse<Invoice>> {
        return apiClient.get<Invoice>(`/invoices/${id}`);
    },

    async createInvoice(data: any): Promise<ApiResponse<Invoice>> {
        return apiClient.post<Invoice>('/invoices', data);
    },

    async generateInvoices(data: { class_id: number; term_id: number; fee_structure_id: number }): Promise<ApiResponse<{ count: number; message: string }>> {
        return apiClient.post('/invoices/generate-batch', data);
    },

    async updateInvoice(id: number, data: any): Promise<ApiResponse<Invoice>> {
        return apiClient.put<Invoice>(`/invoices/${id}`, data);
    },

    async reverseInvoice(id: number, reason: string): Promise<ApiResponse<Invoice>> {
        return apiClient.post<Invoice>(`/invoices/${id}/reverse`, { reason });
    },

    async downloadInvoice(id: number): Promise<ApiResponse<Blob>> {
        return apiClient.get<Blob>(`/invoices/${id}/download`);
    },

    // ========== Payments ==========
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

    async reversePayment(id: number, reason: string): Promise<ApiResponse<Payment>> {
        return apiClient.post<Payment>(`/payments/${id}/reverse`, { reason });
    },

    async downloadReceipt(id: number): Promise<ApiResponse<Blob>> {
        return apiClient.get<Blob>(`/payments/${id}/receipt`);
    },

    // ========== Bank + C2B transactions (unified) ==========
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

    // ========== Student Statements ==========
    async getStudentStatement(
        studentId: number,
        year?: number
    ): Promise<ApiResponse<StudentStatement & { year?: number }>> {
        return apiClient.get<StudentStatement & { year?: number }>(`/students/${studentId}/statement`, {
            year: year ?? new Date().getFullYear(),
        });
    },

    async downloadStatement(studentId: number, dateFrom?: string, dateTo?: string): Promise<ApiResponse<Blob>> {
        return apiClient.get<Blob>(`/students/${studentId}/statement/download`, {
            date_from: dateFrom,
            date_to: dateTo,
        });
    },

    // ========== Payment Plans ==========
    async getPaymentPlans(studentId?: number): Promise<ApiResponse<PaginatedResponse<PaymentPlan>>> {
        return apiClient.get<PaginatedResponse<PaymentPlan>>('/payment-plans', { student_id: studentId });
    },

    async createPaymentPlan(data: any): Promise<ApiResponse<PaymentPlan>> {
        return apiClient.post<PaymentPlan>('/payment-plans', data);
    },

    async updatePaymentPlan(id: number, data: any): Promise<ApiResponse<PaymentPlan>> {
        return apiClient.put<PaymentPlan>(`/payment-plans/${id}`, data);
    },

    // ========== Online Payments ==========
    async initiateOnlinePayment(data: OnlinePaymentRequest): Promise<ApiResponse<OnlinePaymentResponse>> {
        return apiClient.post<OnlinePaymentResponse>('/payments/online/initiate', data);
    },

    async checkPaymentStatus(transactionId: string): Promise<ApiResponse<OnlinePaymentResponse>> {
        return apiClient.get<OnlinePaymentResponse>(`/payments/online/status/${transactionId}`);
    },

    // ========== Finance Summary ==========
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
            // Some deployments may not expose /finance/summary yet; keep mobile dashboards functional.
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

    /** Admin STK push (same as web Prompt parent to pay). */
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

    /** Public payment page URL(s) for the student / family. */
    async getMpesaPaymentLink(studentId: number): Promise<
        ApiResponse<{ payment_link_id: number; url: string; short_url: string }>
    > {
        return apiClient.get(`/students/${studentId}/mpesa/payment-link`);
    },
};
