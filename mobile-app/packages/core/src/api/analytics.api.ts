import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export type AnalyticsPeriod = 'week' | 'month' | 'term' | 'year';

export interface ChartSeries {
  labels: string[];
  values: number[];
}

export interface PieSlice {
  name: string;
  value: number;
  color: string;
}

export interface ExecutiveAnalytics {
  period: AnalyticsPeriod;
  as_of: string;
  finance: {
    daily_collections: ChartSeries;
    weekly_collections: ChartSeries;
    monthly_collections: number;
    outstanding_balances: number;
  };
  admissions: {
    enrollment_trends: ChartSeries;
    admission_trends: ChartSeries;
    enrollment_pie: PieSlice[];
  };
  academics: {
    attendance_trends: ChartSeries;
    exam_trends: ChartSeries;
  };
  hr: {
    staff_growth: ChartSeries;
    attendance_trends: ChartSeries;
  };
  operations: {
    visitors: ChartSeries;
    assets: number;
    inventory_alerts: number;
  };
}

export const analyticsApi = {
  executive(period: AnalyticsPeriod = 'month'): Promise<ApiResponse<ExecutiveAnalytics>> {
    return apiClient.get<ExecutiveAnalytics>('/analytics/executive', { period });
  },
};
