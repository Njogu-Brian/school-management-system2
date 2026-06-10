import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { reportsApi } from '../../api/reports.api';
import { queryKeys } from '../queryKeys';

export function useWeeklyReports(options?: { enabled?: boolean; weekEnding?: string }) {
  return useQuery({
    queryKey: queryKeys.reports.weekly(options?.weekEnding),
    queryFn: async () => {
      const res = await reportsApi.listWeeklyReports({
        week_ending: options?.weekEnding,
        limit: 50,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load weekly reports.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useExpenseReportSummary(options?: {
  enabled?: boolean;
  fromDate?: string;
  toDate?: string;
}) {
  return useQuery({
    queryKey: queryKeys.reports.expenses({
      from: options?.fromDate,
      to: options?.toDate,
    }),
    queryFn: async () => {
      const res = await reportsApi.getExpenseSummary({
        from_date: options?.fromDate,
        to_date: options?.toDate,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load expense report.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useWeeklyReportDetail(
  type: string,
  id: number,
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.reports.weeklyDetail(type, id),
    queryFn: async () => {
      const res = await reportsApi.getWeeklyReportDetail(type, id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load report.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0 && type.length > 0,
    staleTime: 60_000,
  });
}

export function useInfiniteExpenses(options?: {
  enabled?: boolean;
  status?: string;
  search?: string;
  perPage?: number;
}) {
  const perPage = options?.perPage ?? 25;
  return useInfiniteQuery({
    queryKey: queryKeys.reports.expensesList({
      status: options?.status,
      search: options?.search,
    }),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await reportsApi.listExpenses({
        per_page: perPage,
        page: pageParam as number,
        status: options?.status,
        search: options?.search,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load expenses.');
      }
      const page = res.data;
      return {
        items: page.data,
        currentPage: page.current_page,
        lastPage: page.last_page,
        total: page.total,
        hasMore: page.current_page < page.last_page,
      };
    },
    getNextPageParam: (last) => (last.hasMore ? last.currentPage + 1 : undefined),
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useExpense(id: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.reports.expenseDetail(id),
    queryFn: async () => {
      const res = await reportsApi.getExpense(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load expense.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0,
    staleTime: 60_000,
  });
}

function useExpenseAction<TVars>(mutationFn: (vars: TVars) => Promise<unknown>, expenseId: (vars: TVars) => number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn,
    onSuccess: (_data, vars) => {
      void qc.invalidateQueries({ queryKey: queryKeys.reports.expenseDetail(expenseId(vars)) });
      void qc.invalidateQueries({ queryKey: [...queryKeys.reports.all, 'expenses-list'] });
      void qc.invalidateQueries({ queryKey: [...queryKeys.reports.all, 'expenses'] });
    },
  });
}

export function useSubmitExpense() {
  return useExpenseAction(
    async ({ id }: { id: number }) => {
      const res = await reportsApi.submitExpense(id);
      if (!res.success) throw new Error(res.message || 'Failed to submit expense.');
      return res;
    },
    (vars) => vars.id,
  );
}

export function useApproveExpense() {
  return useExpenseAction(
    async ({ id, remarks }: { id: number; remarks?: string }) => {
      const res = await reportsApi.approveExpense(id, remarks);
      if (!res.success) throw new Error(res.message || 'Failed to approve expense.');
      return res;
    },
    (vars) => vars.id,
  );
}

export function useRejectExpense() {
  return useExpenseAction(
    async ({ id, remarks }: { id: number; remarks: string }) => {
      const res = await reportsApi.rejectExpense(id, remarks);
      if (!res.success) throw new Error(res.message || 'Failed to reject expense.');
      return res;
    },
    (vars) => vars.id,
  );
}

export function usePayExpense() {
  return useExpenseAction(
    async ({ id, payment_method, reference_no }: { id: number; payment_method?: string; reference_no?: string }) => {
      const res = await reportsApi.payExpense(id, { payment_method, reference_no });
      if (!res.success) throw new Error(res.message || 'Failed to pay expense.');
      return res;
    },
    (vars) => vars.id,
  );
}

export function useIncomeStatement(options?: { enabled?: boolean; months?: number }) {
  return useQuery({
    queryKey: queryKeys.reports.incomeStatement(options?.months),
    queryFn: async () => {
      const res = await reportsApi.getIncomeStatement({ months: options?.months ?? 6 });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load income statement.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useBoardPack(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.reports.boardPack(),
    queryFn: async () => {
      const res = await reportsApi.getBoardPack();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load board pack.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}
