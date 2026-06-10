import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface WeeklyReportItem {
  type: string;
  id: number;
  week_ending?: string | null;
  title: string;
  subtitle?: string | null;
  campus?: string | null;
  resolved?: boolean;
}

export interface ExpenseReportSummary {
  total_expenses: number;
  expense_count: number;
  category_summary: Array<{ category_name: string; total_amount: number }>;
  vendor_summary: Array<{ vendor_name: string; total_amount: number }>;
  recent_expenses: Array<{
    id: number;
    expense_no?: string | null;
    expense_date?: string | null;
    status?: string | null;
    vendor_name?: string | null;
    total: number;
  }>;
  as_of: string;
}

export interface WeeklyReportDetail {
  type: string;
  id: number;
  week_ending?: string | null;
  campus?: string | null;
  title: string;
  subtitle?: string | null;
  fields: Array<{ label: string; value?: string | null }>;
  notes?: string | null;
}

export interface ExpenseSummaryRecord {
  id: number;
  expense_no?: string | null;
  vendor?: string | null;
  expense_date?: string | null;
  total: number;
  status?: string | null;
  source_type?: string | null;
}

export interface IncomeStatementData {
  from: string;
  to: string;
  months: Array<{
    month: string;
    label: string;
    income: number;
    expenses: number;
    net: number;
  }>;
  totals: { income: number; expenses: number; net: number };
  as_of: string;
}

export interface ExpenseDetailRecord extends ExpenseSummaryRecord {
  can_submit?: boolean;
  can_approve?: boolean;
  can_pay?: boolean;
  due_date?: string | null;
  currency?: string | null;
  subtotal?: number;
  tax_total?: number;
  requested_by?: string | null;
  approved_by?: string | null;
  approved_at?: string | null;
  submitted_at?: string | null;
  notes?: string | null;
  lines: Array<{
    id: number;
    description?: string | null;
    category?: string | null;
    department?: string | null;
    qty: number;
    unit_cost: number;
    tax_rate: number;
    line_total: number;
  }>;
  vouchers: Array<{
    id: number;
    voucher_no?: string | null;
    status?: string | null;
    amount: number;
    payment_method?: string | null;
    payment_date?: string | null;
  }>;
  attachments?: ExpenseAttachmentRecord[];
}

export interface ExpenseAttachmentRecord {
  id: number;
  file_name?: string | null;
  mime_type?: string | null;
  url?: string | null;
  uploaded_by?: string | null;
  uploaded_at?: string | null;
}

export interface LedgerPostingRecord {
  id: number;
  posting_date?: string | null;
  account_code: string;
  dr_cr: 'dr' | 'cr';
  amount: number;
  source_type?: string | null;
  source_id?: number | null;
}

export interface TrialBalanceData {
  accounts: Array<{
    account_code: string;
    total_dr: number;
    total_cr: number;
    net: number;
  }>;
  totals: { dr: number; cr: number };
  as_of: string;
}

export interface BalanceSheetData {
  assets: Array<{ label: string; amount: number }>;
  liabilities: Array<{ label: string; amount: number }>;
  totals: { assets: number; liabilities: number; net_position: number };
  as_of: string;
}

export interface UploadFileInput {
  uri: string;
  name: string;
  type: string;
}

export interface BoardPackSummary {
  finance: Record<string, unknown>;
  operations: Record<string, unknown>;
  approvals: { pending_total: number };
  expenses: { month_to_date: number };
  facilities: { open_issues: number; low_stock_items: number };
  generated_at: string;
}

export const reportsApi = {
  listWeeklyReports(params?: { week_ending?: string; limit?: number }): Promise<
    ApiResponse<{
      items: WeeklyReportItem[];
      counts: Record<string, number>;
    }>
  > {
    return apiClient.get('/reports/weekly', params);
  },

  getExpenseSummary(params?: { from_date?: string; to_date?: string }): Promise<
    ApiResponse<ExpenseReportSummary>
  > {
    return apiClient.get<ExpenseReportSummary>('/reports/expenses/summary', params);
  },

  getWeeklyReportDetail(type: string, id: number): Promise<ApiResponse<WeeklyReportDetail>> {
    return apiClient.get<WeeklyReportDetail>(`/reports/weekly/${type}/${id}`);
  },

  listExpenses(params?: {
    page?: number;
    per_page?: number;
    status?: string;
    search?: string;
    date_from?: string;
    date_to?: string;
  }): Promise<ApiResponse<PaginatedResponse<ExpenseSummaryRecord>>> {
    return apiClient.get<PaginatedResponse<ExpenseSummaryRecord>>('/expenses', params);
  },

  getExpense(id: number): Promise<ApiResponse<ExpenseDetailRecord>> {
    return apiClient.get<ExpenseDetailRecord>(`/expenses/${id}`);
  },

  submitExpense(id: number): Promise<ApiResponse<ExpenseSummaryRecord>> {
    return apiClient.post<ExpenseSummaryRecord>(`/expenses/${id}/submit`);
  },

  approveExpense(id: number, remarks?: string): Promise<ApiResponse<ExpenseSummaryRecord>> {
    return apiClient.post<ExpenseSummaryRecord>(`/expenses/${id}/approve`, { remarks });
  },

  rejectExpense(id: number, remarks: string): Promise<ApiResponse<ExpenseSummaryRecord>> {
    return apiClient.post<ExpenseSummaryRecord>(`/expenses/${id}/reject`, { remarks });
  },

  payExpense(
    id: number,
    payload?: { payment_method?: string; reference_no?: string },
  ): Promise<ApiResponse<ExpenseSummaryRecord>> {
    return apiClient.post<ExpenseSummaryRecord>(`/expenses/${id}/pay`, payload ?? {});
  },

  getIncomeStatement(params?: { months?: number }): Promise<ApiResponse<IncomeStatementData>> {
    return apiClient.get<IncomeStatementData>('/reports/income-statement', params);
  },

  uploadExpenseAttachment(
    expenseId: number,
    file: UploadFileInput,
  ): Promise<ApiResponse<ExpenseAttachmentRecord>> {
    const form = new FormData();
    form.append('file', { uri: file.uri, name: file.name, type: file.type } as unknown as Blob);
    return apiClient.postMultipart<ExpenseAttachmentRecord>(`/expenses/${expenseId}/attachments`, form);
  },

  deleteExpenseAttachment(expenseId: number, attachmentId: number): Promise<ApiResponse<null>> {
    return apiClient.delete(`/expenses/${expenseId}/attachments/${attachmentId}`);
  },

  listLedgerPostings(params?: {
    account_code?: string;
    dr_cr?: string;
    date_from?: string;
    date_to?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<LedgerPostingRecord>>> {
    return apiClient.get<PaginatedResponse<LedgerPostingRecord>>('/ledger/postings', params);
  },

  getTrialBalance(params?: {
    date_from?: string;
    date_to?: string;
  }): Promise<ApiResponse<TrialBalanceData>> {
    return apiClient.get<TrialBalanceData>('/ledger/trial-balance', params);
  },

  getBalanceSheet(): Promise<ApiResponse<BalanceSheetData>> {
    return apiClient.get<BalanceSheetData>('/reports/balance-sheet');
  },

  getBoardPack(): Promise<ApiResponse<BoardPackSummary>> {
    return apiClient.get<BoardPackSummary>('/reports/board-pack');
  },
};
