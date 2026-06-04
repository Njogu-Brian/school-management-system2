/** Filters for `GET /dashboard/stats` (academic year / term scope). */
export interface DashboardStatsFilters {
  academic_year_id?: number | null;
  term_id?: number | null;
}

export interface DashboardChartSeries {
  labels: string[];
  values: number[];
}

export interface DashboardAcademicYearOption {
  id: number;
  year: number | string;
  is_active?: boolean;
}

export interface DashboardTermOption {
  id: number;
  name: string;
  academic_year_id: number;
  opening_date?: string | null;
  closing_date?: string | null;
  is_current?: boolean;
}

export interface DashboardStatsFiltersPayload {
  academic_year_id: number | null;
  term_id: number | null;
  available_years: DashboardAcademicYearOption[];
  available_terms: DashboardTermOption[];
}

/** Admin role payload from `ApiDashboardController::adminDashboard`. */
export interface AdminDashboardStats {
  role: string;
  total_students: number;
  total_staff: number;
  present_today: number;
  fees_collected: number;
  total_invoiced: number;
  total_payments: number;
  outstanding_balance: number;
  filters?: DashboardStatsFiltersPayload;
  charts?: {
    enrollment?: DashboardChartSeries;
    payments?: DashboardChartSeries;
    invoices?: DashboardChartSeries;
  };
}

export interface PaginatedListMeta {
  total: number;
  current_page?: number;
  last_page?: number;
  per_page?: number;
}

export interface PendingApprovalsSummary {
  pending_leave_requests: number;
  pending_lesson_plans: number;
  total: number;
}
