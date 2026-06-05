import { useQuery } from '@tanstack/react-query';
import { driverTransportApi } from '../../api/driverTransport.api';
import { queryKeys } from '../queryKeys';

export function useDriverTrips(options?: { enabled?: boolean; date?: string }) {
  const date = options?.date ?? new Date().toISOString().slice(0, 10);
  return useQuery({
    queryKey: queryKeys.driverTransport.trips(date),
    queryFn: async () => {
      const res = await driverTransportApi.listTrips({ date, per_page: 50 } as { date?: string; page?: number });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load driver trips.');
      return res.data.data ?? [];
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
  });
}

export function useDriverTrip(tripId: number, options?: { enabled?: boolean; date?: string }) {
  const date = options?.date ?? new Date().toISOString().slice(0, 10);
  return useQuery({
    queryKey: queryKeys.driverTransport.trip(tripId, date),
    queryFn: async () => {
      const res = await driverTransportApi.getTrip(tripId, { date });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load trip.');
      return res.data;
    },
    enabled: (options?.enabled !== false) && tripId > 0,
    staleTime: 30_000,
  });
}
