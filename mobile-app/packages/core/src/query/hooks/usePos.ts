import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { posApi, type PosOrderCreateItem } from '../../api/pos.api';
import { queryKeys } from '../queryKeys';

export function usePosRequirementsCatalog(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: [...queryKeys.operations.all, 'pos-requirements-catalog', studentId] as const,
    queryFn: async () => {
      const res = await posApi.requirementsCatalog(studentId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load shop catalog.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && studentId > 0,
    staleTime: 30_000,
  });
}

export function usePosUniforms(options?: { enabled?: boolean; studentId?: number }) {
  return useQuery({
    queryKey: [...queryKeys.operations.all, 'pos-uniforms', options?.studentId ?? null] as const,
    queryFn: async () => {
      const res = await posApi.listUniforms(
        options?.studentId ? { student_id: options.studentId } : undefined,
      );
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load uniforms.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
  });
}

export function usePosOrders(filters?: {
  payment_status?: string;
  order_type?: string;
  student_id?: number;
}, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: [...queryKeys.operations.all, 'pos-orders', filters ?? {}] as const,
    queryFn: async () => {
      const res = await posApi.listOrders(filters);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load orders.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 20_000,
  });
}

export function usePosOrder(orderId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: [...queryKeys.operations.all, 'pos-order', orderId] as const,
    queryFn: async () => {
      const res = await posApi.getOrder(orderId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load order.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && orderId > 0,
  });
}

export function useCreatePosOrder() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: {
      studentId: number;
      items: PosOrderCreateItem[];
      notes?: string;
      transaction_code?: string;
      payment_method?: string;
      asAdmin?: boolean;
    }) => {
      const res = await posApi.createOrder(
        payload.studentId,
        {
          items: payload.items,
          notes: payload.notes,
          transaction_code: payload.transaction_code,
          payment_method: payload.payment_method,
        },
        Boolean(payload.asAdmin),
      );
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to create order.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: [...queryKeys.operations.all, 'pos-orders'] });
    },
  });
}

export function useConfirmPosPayment() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: { orderId: number; transaction_code: string; payment_method?: string }) => {
      const res = await posApi.confirmPayment(
        payload.orderId,
        payload.transaction_code,
        payload.payment_method,
      );
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to confirm payment.');
      }
      return res.data;
    },
    onSuccess: (_data, vars) => {
      void qc.invalidateQueries({ queryKey: [...queryKeys.operations.all, 'pos-orders'] });
      void qc.invalidateQueries({ queryKey: [...queryKeys.operations.all, 'pos-order', vars.orderId] });
    },
  });
}

export function useUpdatePosOrderStatus() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: { orderId: number; status: string }) => {
      const res = await posApi.updateOrderStatus(payload.orderId, payload.status);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to update order.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: [...queryKeys.operations.all, 'pos-orders'] });
    },
  });
}

export function useUpdateUniformStock() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: {
      productId: number;
      stock_quantity?: number;
      variants?: Array<{ id: number; stock_quantity: number }>;
    }) => {
      const res = await posApi.updateUniformStock(payload.productId, {
        stock_quantity: payload.stock_quantity,
        variants: payload.variants,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to update stock.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: [...queryKeys.operations.all, 'pos-uniforms'] });
    },
  });
}
