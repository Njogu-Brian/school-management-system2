import type { ApiResponse, PaginatedResponse } from '../types/api';
import type {
  FeeStructureListRecord,
  FinanceTransactionDetailRecord,
  FinanceTransactionListFilters,
  FinanceTransactionListRecord,
  InvoiceDetailRecord,
  InvoiceListFilters,
  InvoiceListRecord,
  PaymentDetailRecord,
  PaymentListFilters,
  PaymentListRecord,
} from '../types/finance';
import { reconciliationQueueToView } from '../finance/normalize';
import { apiClient } from './client';

/**
 * Finance workspace APIs (Sprint 6) — reuses existing Laravel Sanctum routes.
 */
export const financeApi = {
  listInvoices(
    params?: InvoiceListFilters,
  ): Promise<ApiResponse<PaginatedResponse<InvoiceListRecord>>> {
    const query: Record<string, string | number | boolean> = {};
    if (params?.search) query.search = params.search;
    if (params?.status) query.status = params.status;
    if (params?.student_id != null) query.student_id = params.student_id;
    if (params?.class_id != null) query.class_id = params.class_id;
    if (params?.stream_id != null) query.stream_id = params.stream_id;
    if (params?.year != null) query.year = params.year;
    if (params?.year_id != null) query.year_id = params.year_id;
    if (params?.term != null) query.term = params.term;
    if (params?.term_id != null) query.term_id = params.term_id;
    if (params?.include_reversed) query.include_reversed = true;
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    return apiClient.get<PaginatedResponse<InvoiceListRecord>>('/invoices', query);
  },

  getInvoice(id: number): Promise<ApiResponse<InvoiceDetailRecord>> {
    return apiClient.get<InvoiceDetailRecord>(`/invoices/${id}`);
  },

  listFeeStructures(params?: {
    search?: string;
    class_id?: number;
    academic_year_id?: number;
    is_active?: boolean;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<FeeStructureListRecord>>> {
    const query: Record<string, string | number | boolean> = {};
    if (params?.search) query.search = params.search;
    if (params?.class_id != null) query.class_id = params.class_id;
    if (params?.academic_year_id != null) query.academic_year_id = params.academic_year_id;
    if (params?.is_active != null) query.is_active = params.is_active;
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    return apiClient.get<PaginatedResponse<FeeStructureListRecord>>('/fee-structures', query);
  },

  listPayments(
    params?: PaymentListFilters,
  ): Promise<ApiResponse<PaginatedResponse<PaymentListRecord>>> {
    const query: Record<string, string | number | boolean> = {};
    if (params?.search) query.search = params.search;
    if (params?.student_id != null) query.student_id = params.student_id;
    if (params?.class_id != null) query.class_id = params.class_id;
    if (params?.date_from) query.date_from = params.date_from;
    if (params?.date_to) query.date_to = params.date_to;
    if (params?.active_only != null) query.active_only = params.active_only;
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    return apiClient.get<PaginatedResponse<PaymentListRecord>>('/payments', query);
  },

  getPayment(id: number): Promise<ApiResponse<PaymentDetailRecord>> {
    return apiClient.get<PaymentDetailRecord>(`/payments/${id}`);
  },

  listTransactions(
    params?: FinanceTransactionListFilters,
  ): Promise<ApiResponse<PaginatedResponse<FinanceTransactionListRecord>>> {
    const query: Record<string, string | number> = {};
    if (params?.search) query.search = params.search;
    const view = params?.view ?? (params?.queue ? reconciliationQueueToView(params.queue) : undefined);
    if (view) query.view = view;
    if (params?.date_from) query.date_from = params.date_from;
    if (params?.date_to) query.date_to = params.date_to;
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    return apiClient.get<PaginatedResponse<FinanceTransactionListRecord>>(
      '/finance/transactions',
      query,
    );
  },

  getTransaction(
    id: number,
    type: 'bank' | 'c2b',
  ): Promise<ApiResponse<FinanceTransactionDetailRecord>> {
    return apiClient.get<FinanceTransactionDetailRecord>(`/finance/transactions/${id}`, { type });
  },

  confirmTransaction(
    id: number,
    type: 'bank' | 'c2b',
  ): Promise<ApiResponse<{ message?: string }>> {
    return apiClient.post(`/finance/transactions/${id}/confirm`, { type });
  },

  rejectTransaction(
    id: number,
    type: 'bank' | 'c2b',
  ): Promise<ApiResponse<{ message?: string }>> {
    return apiClient.post(`/finance/transactions/${id}/reject`, { type });
  },

  getSummary(): Promise<
    ApiResponse<{
      collected_today: number;
      collected_this_month: number;
      total_invoiced: number;
      total_paid: number;
      outstanding_balance: number;
      pending_invoices: number;
      overdue_invoices: number;
      students_in_arrears: number;
      pending_reconciliation: number;
      active_students: number;
      as_of: string;
    }>
  > {
    return apiClient.get('/finance/summary');
  },
};
