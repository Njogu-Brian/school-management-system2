import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { operationsApi } from '../../api/operations.api';
import { queryKeys } from '../queryKeys';

export function useOperationsSummary(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.operations.summary(),
    queryFn: async () => {
      const res = await operationsApi.getSummary();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load operations summary.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useTransportRoutes(options?: { enabled?: boolean; search?: string }) {
  return useQuery({
    queryKey: queryKeys.operations.routes(options?.search),
    queryFn: async () => {
      const res = await operationsApi.listRoutes({ per_page: 50, search: options?.search });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load transport routes.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useTransportRoute(routeId: number | null | undefined, options?: { enabled?: boolean }) {
  const id = routeId ?? 0;
  return useQuery({
    queryKey: queryKeys.operations.route(id),
    queryFn: async () => {
      const res = await operationsApi.getRoute(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load transport route.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0,
    staleTime: 60_000,
  });
}

export function useStudentRequirements(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.operations.studentRequirements(studentId),
    queryFn: async () => {
      const res = await operationsApi.getStudentRequirements(studentId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load student requirements.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && studentId > 0,
    staleTime: 45_000,
  });
}

export function useInventoryItems(options?: {
  enabled?: boolean;
  search?: string;
  lowStock?: boolean;
}) {
  return useQuery({
    queryKey: queryKeys.operations.inventory({
      search: options?.search,
      lowStock: options?.lowStock,
    }),
    queryFn: async () => {
      const res = await operationsApi.listInventoryItems({
        search: options?.search,
        low_stock: options?.lowStock,
        per_page: 50,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load inventory.');
      }
      return res.data.data ?? [];
    },
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useInfiniteRequisitions(options?: { enabled?: boolean; status?: string }) {
  return useInfiniteQuery({
    queryKey: queryKeys.operations.requisitions(options?.status ?? 'all'),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await operationsApi.listRequisitions({
        status: options?.status,
        per_page: 25,
        page: pageParam as number,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load requisitions.');
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
    staleTime: 45_000,
  });
}

export function useRequisitions(options?: { enabled?: boolean; status?: string }) {
  return useQuery({
    queryKey: queryKeys.operations.requisitions(options?.status),
    queryFn: async () => {
      const res = await operationsApi.listRequisitions({
        status: options?.status,
        per_page: 50,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load requisitions.');
      }
      return res.data.data ?? [];
    },
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}

export function useRequisition(id: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.operations.requisition(id),
    queryFn: async () => {
      const res = await operationsApi.getRequisition(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load requisition.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0,
    staleTime: 30_000,
  });
}

export function useApproveRequisition() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, items }: { id: number; items?: Array<{ id: number; quantity_approved: number }> }) =>
      operationsApi.approveRequisition(id, items ? { items } : undefined),
    onSuccess: (_data, vars) => {
      void qc.invalidateQueries({ queryKey: queryKeys.operations.all });
      void qc.invalidateQueries({ queryKey: queryKeys.operations.requisition(vars.id) });
    },
  });
}

export function useRejectRequisition() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, rejection_reason }: { id: number; rejection_reason: string }) =>
      operationsApi.rejectRequisition(id, rejection_reason),
    onSuccess: (_data, vars) => {
      void qc.invalidateQueries({ queryKey: queryKeys.operations.all });
      void qc.invalidateQueries({ queryKey: queryKeys.operations.requisition(vars.id) });
    },
  });
}

export function useInfiniteVisitors(options?: {
  enabled?: boolean;
  onSite?: boolean;
  date?: string;
}) {
  return useInfiniteQuery({
    queryKey: queryKeys.operations.visitors({
      onSite: options?.onSite,
      date: options?.date,
    }),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await operationsApi.listVisitors({
        on_site: options?.onSite,
        date: options?.date,
        per_page: 25,
        page: pageParam as number,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load visitors.');
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
    staleTime: 30_000,
  });
}

export function useVisitors(options?: { enabled?: boolean; onSite?: boolean; date?: string }) {
  return useQuery({
    queryKey: queryKeys.operations.visitors({ onSite: options?.onSite, date: options?.date }),
    queryFn: async () => {
      const res = await operationsApi.listVisitors({
        on_site: options?.onSite,
        date: options?.date,
        per_page: 50,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load visitors.');
      }
      return res.data.data ?? [];
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
  });
}

export function useCheckInVisitor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: operationsApi.checkInVisitor,
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.operations.all });
    },
  });
}

export function useCheckOutVisitor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => operationsApi.checkOutVisitor(id),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.operations.all });
    },
  });
}

export function useInfiniteAssets(options?: { enabled?: boolean; search?: string; status?: string }) {
  return useInfiniteQuery({
    queryKey: queryKeys.operations.assets({ search: options?.search, status: options?.status }),
    initialPageParam: 1,
    queryFn: async ({ pageParam }) => {
      const res = await operationsApi.listAssets({
        search: options?.search,
        status: options?.status,
        per_page: 25,
        page: pageParam as number,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load assets.');
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

export function useAsset(id: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.operations.asset(id),
    queryFn: async () => {
      const res = await operationsApi.getAsset(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load asset.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0,
    staleTime: 60_000,
  });
}

export function useMedicalRecords(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.operations.medicalRecords(studentId),
    queryFn: async () => {
      const res = await operationsApi.listMedicalRecords(studentId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load medical records.');
      }
      return res.data.data ?? [];
    },
    enabled: (options?.enabled !== false) && studentId > 0,
    staleTime: 60_000,
  });
}
