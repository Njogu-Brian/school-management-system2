import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { parentWalletApi } from '../../api/parentWallet.api';

const walletKey = ['parent-wallet'] as const;
const plansKey = ['parent-wallet', 'saving-plans'] as const;

export function useParentWallet(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: walletKey,
    queryFn: async () => {
      const res = await parentWalletApi.get();
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load wallet.');
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
    retry: (count, error) => {
      const msg = error instanceof Error ? error.message : String(error);
      if (msg.includes('404') || msg.toLowerCase().includes('could not be found')) return false;
      return count < 2;
    },
  });
}

export function useParentWalletSavingPlans(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: plansKey,
    queryFn: async () => {
      const res = await parentWalletApi.listSavingPlans();
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load saving plans.');
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
  });
}

export function useParentWalletTopUp() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Parameters<typeof parentWalletApi.topUp>[0]) => {
      const res = await parentWalletApi.topUp(payload);
      if (!res.success) throw new Error(res.message || 'Top-up failed.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: walletKey });
    },
  });
}

export function useParentWalletPay() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Parameters<typeof parentWalletApi.pay>[0]) => {
      const res = await parentWalletApi.pay(payload);
      if (!res.success) throw new Error(res.message || 'Wallet payment failed.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: walletKey });
    },
  });
}

export function useCreateSavingPlan() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Parameters<typeof parentWalletApi.createSavingPlan>[0]) => {
      const res = await parentWalletApi.createSavingPlan(payload);
      if (!res.success || !res.data) throw new Error(res.message || 'Could not create plan.');
      return res.data;
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: plansKey }),
  });
}

export function useUpdateSavingPlan() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({
      id,
      ...payload
    }: { id: number } & Parameters<typeof parentWalletApi.updateSavingPlan>[1]) => {
      const res = await parentWalletApi.updateSavingPlan(id, payload);
      if (!res.success || !res.data) throw new Error(res.message || 'Could not update plan.');
      return res.data;
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: plansKey }),
  });
}

export function useDeleteSavingPlan() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      const res = await parentWalletApi.deleteSavingPlan(id);
      if (!res.success) throw new Error(res.message || 'Could not delete plan.');
      return res;
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: plansKey }),
  });
}

export function usePaySavingPlanNow() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, phone_number }: { id: number; phone_number: string }) => {
      const res = await parentWalletApi.paySavingPlanNow(id, phone_number);
      if (!res.success) throw new Error(res.message || 'STK failed.');
      return res.data;
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: walletKey }),
  });
}
