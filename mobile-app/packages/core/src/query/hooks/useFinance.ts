import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { financeApi } from '../../api/finance.api';
import { fetchFinanceDashboardKpis } from '../../finance/fetchFinanceDashboard';
import {
  normalizeFinanceTransactionSummary,
  normalizeInvoiceSummary,
  normalizePaymentSummary,
} from '../../finance/normalize';
import type {
  FinanceTransactionListFilters,
  InvoiceListFilters,
  PaymentListFilters,
} from '../../types/finance';
import { queryKeys } from '../queryKeys';

export function useFinanceDashboardKpis(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.finance.dashboard(),
    queryFn: fetchFinanceDashboardKpis,
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useInfiniteInvoiceList(
  filters: InvoiceListFilters,
  options?: { enabled?: boolean },
) {
  return useInfiniteQuery({
    queryKey: queryKeys.finance.invoices(filters),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await financeApi.listInvoices({
        ...filters,
        page: pageParam as number,
        per_page: filters.per_page ?? 25,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load invoices.');
      }
      const page = res.data;
      return {
        items: page.data.map(normalizeInvoiceSummary),
        raw: page.data,
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last, _pages, lastPageParam) =>
      last.hasMore ? (lastPageParam as number) + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useInvoiceDetail(invoiceId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.finance.invoiceDetail(invoiceId),
    queryFn: async () => {
      const res = await financeApi.getInvoice(invoiceId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load invoice.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && invoiceId > 0,
    staleTime: 60_000,
  });
}

export function useInfinitePaymentList(
  filters: PaymentListFilters,
  options?: { enabled?: boolean },
) {
  return useInfiniteQuery({
    queryKey: queryKeys.finance.payments(filters),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await financeApi.listPayments({
        ...filters,
        page: pageParam as number,
        per_page: filters.per_page ?? 25,
        active_only: filters.active_only ?? true,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load payments.');
      }
      const page = res.data;
      return {
        items: page.data.map(normalizePaymentSummary),
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last, _pages, lastPageParam) =>
      last.hasMore ? (lastPageParam as number) + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function usePaymentDetail(paymentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.finance.paymentDetail(paymentId),
    queryFn: async () => {
      const res = await financeApi.getPayment(paymentId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load payment.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && paymentId > 0,
    staleTime: 60_000,
  });
}

export function useInfiniteFinanceTransactions(
  filters: FinanceTransactionListFilters,
  options?: { enabled?: boolean },
) {
  return useInfiniteQuery({
    queryKey: queryKeys.finance.transactions(filters),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await financeApi.listTransactions({
        ...filters,
        page: pageParam as number,
        per_page: filters.per_page ?? 25,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load transactions.');
      }
      const page = res.data;
      return {
        items: page.data.map(normalizeFinanceTransactionSummary),
        raw: page.data,
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last, _pages, lastPageParam) =>
      last.hasMore ? (lastPageParam as number) + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useFinanceTransactionDetail(
  id: number,
  type: 'bank' | 'c2b',
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.finance.transactionDetail(id, type),
    queryFn: async () => {
      const res = await financeApi.getTransaction(id, type);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load transaction.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && id > 0,
    staleTime: 60_000,
  });
}

export function useReconciliationActions() {
  const queryClient = useQueryClient();

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: queryKeys.finance.all });
  };

  const confirm = useMutation({
    mutationFn: async ({ id, type }: { id: number; type: 'bank' | 'c2b' }) => {
      const res = await financeApi.confirmTransaction(id, type);
      if (!res.success) {
        throw new Error(res.message || 'Failed to confirm transaction.');
      }
      return res.data;
    },
    onSuccess: invalidate,
  });

  const reject = useMutation({
    mutationFn: async ({ id, type }: { id: number; type: 'bank' | 'c2b' }) => {
      const res = await financeApi.rejectTransaction(id, type);
      if (!res.success) {
        throw new Error(res.message || 'Failed to reject transaction.');
      }
      return res.data;
    },
    onSuccess: invalidate,
  });

  return { confirm, reject };
}
