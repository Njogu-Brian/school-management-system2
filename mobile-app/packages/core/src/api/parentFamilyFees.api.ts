import { apiClient, type ApiResponse } from './client';

export type FamilyFeeInvoice = {
  id: number;
  invoice_number: string;
  issue_date?: string | null;
  due_date?: string | null;
  term?: number | null;
  term_name?: string | null;
  year?: number | null;
  total: number;
  paid_amount: number;
  balance: number;
  status: 'overdue' | 'upcoming' | string;
  items?: Array<{ id: number; name: string; amount: number }>;
};

export type FamilyFeeChild = {
  student_id: number;
  full_name: string;
  admission_number?: string | null;
  class_name?: string | null;
  outstanding: number;
  overdue: number;
  upcoming: number;
  invoices: FamilyFeeInvoice[];
};

export type FamilyFeesSummary = {
  family: {
    outstanding: number;
    overdue: number;
    upcoming: number;
  };
  children: FamilyFeeChild[];
};

export const parentFamilyFeesApi = {
  getSummary(): Promise<ApiResponse<FamilyFeesSummary>> {
    return apiClient.get('/parent/family-fees');
  },

  prompt(payload: {
    phone_number: string;
    amount: number;
    idempotency_key?: string;
  }): Promise<
    ApiResponse<{
      transaction_id: number;
      amount: number;
      family_outstanding: number;
      waiting_url?: string | null;
      status_poll_url?: string | null;
    }>
  > {
    return apiClient.post('/parent/family-fees/prompt', payload);
  },
};
