import type { ApiResponse } from '../types/api';
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

  getBoardPack(): Promise<ApiResponse<BoardPackSummary>> {
    return apiClient.get<BoardPackSummary>('/reports/board-pack');
  },
};
