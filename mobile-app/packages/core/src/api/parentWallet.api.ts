import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface ParentWalletLedgerEntry {
  id: number;
  type: string;
  amount: number;
  balance_after: number;
  meta?: Record<string, unknown> | null;
  created_at?: string | null;
}

export interface ParentWalletRecord {
  parent_info_id: number;
  balance: number;
  total_credited: number;
  total_debited: number;
  ledger: ParentWalletLedgerEntry[];
}

export interface ParentWalletSavingPlan {
  id: number;
  amount: number;
  frequency: string;
  day_of_week: number;
  remind_at_time: string;
  timezone: string;
  next_remind_at?: string | null;
  active: boolean;
  label?: string | null;
}

export const parentWalletApi = {
  get(): Promise<ApiResponse<ParentWalletRecord>> {
    return apiClient.get<ParentWalletRecord>('/parent-wallet');
  },

  topUp(payload: {
    phone_number: string;
    amount: number;
    saving_plan_id?: number;
  }): Promise<ApiResponse<{ transaction_id: number; amount: number; purpose: string }>> {
    return apiClient.post('/parent-wallet/top-up', payload);
  },

  pay(payload: {
    amount: number;
    invoice_id?: number;
    student_id?: number;
  }): Promise<ApiResponse<{ wallet_balance: number; payment_id?: number | null }>> {
    return apiClient.post('/parent-wallet/pay', payload);
  },

  listSavingPlans(): Promise<ApiResponse<ParentWalletSavingPlan[]>> {
    return apiClient.get<ParentWalletSavingPlan[]>('/parent-wallet/saving-plans');
  },

  createSavingPlan(payload: {
    amount: number;
    day_of_week: number;
    remind_at_time: string;
    timezone?: string;
    label?: string;
    active?: boolean;
  }): Promise<ApiResponse<ParentWalletSavingPlan>> {
    return apiClient.post('/parent-wallet/saving-plans', payload);
  },

  updateSavingPlan(
    id: number,
    payload: Partial<{
      amount: number;
      day_of_week: number;
      remind_at_time: string;
      timezone: string;
      label: string | null;
      active: boolean;
    }>,
  ): Promise<ApiResponse<ParentWalletSavingPlan>> {
    return apiClient.patch(`/parent-wallet/saving-plans/${id}`, payload);
  },

  deleteSavingPlan(id: number): Promise<ApiResponse<null>> {
    return apiClient.delete(`/parent-wallet/saving-plans/${id}`);
  },

  paySavingPlanNow(
    id: number,
    phone_number: string,
  ): Promise<ApiResponse<{ transaction_id: number; amount: number; purpose: string }>> {
    return apiClient.post(`/parent-wallet/saving-plans/${id}/pay-now`, { phone_number });
  },
};
