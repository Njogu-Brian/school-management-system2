import { useQuery } from '@tanstack/react-query';
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

export function useVisitors(options?: { enabled?: boolean; onSite?: boolean }) {
  return useQuery({
    queryKey: queryKeys.operations.visitors(options?.onSite),
    queryFn: async () => {
      const res = await operationsApi.listVisitors({
        on_site: options?.onSite,
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
