import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { driverTransportApi, type DriverBoardingStatus } from '../../api/driverTransport.api';
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

export function useDriverTrip(tripId: number, options?: { enabled?: boolean; date?: string; refetchInterval?: number }) {
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
    refetchInterval: options?.refetchInterval,
  });
}

export function useDriverVehicle(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.driverTransport.vehicle(),
    queryFn: async () => {
      const res = await driverTransportApi.getVehicle();
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load vehicle.');
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useDriverBoarding(tripId: number, options?: { enabled?: boolean; date?: string }) {
  const date = options?.date ?? new Date().toISOString().slice(0, 10);
  return useQuery({
    queryKey: queryKeys.driverTransport.boarding(tripId, date),
    queryFn: async () => {
      const res = await driverTransportApi.getBoarding(tripId, { date });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load boarding list.');
      return res.data;
    },
    enabled: (options?.enabled !== false) && tripId > 0,
    staleTime: 10_000,
  });
}

/** Mutations for the driver trip lifecycle: start, stop, boarding, and GPS pings. */
export function useDriverTripActions(tripId: number, date?: string) {
  const qc = useQueryClient();
  const day = date ?? new Date().toISOString().slice(0, 10);

  const invalidateTrip = () => {
    void qc.invalidateQueries({ queryKey: queryKeys.driverTransport.trip(tripId, day) });
    void qc.invalidateQueries({ queryKey: queryKeys.driverTransport.trips(day) });
  };

  const start = useMutation({
    mutationFn: async () => {
      const res = await driverTransportApi.startTrip(tripId, { date: day });
      if (!res.success) throw new Error(res.message || 'Failed to start trip.');
      return res.data;
    },
    onSuccess: invalidateTrip,
  });

  const stop = useMutation({
    mutationFn: async () => {
      const res = await driverTransportApi.stopTrip(tripId, { date: day });
      if (!res.success) throw new Error(res.message || 'Failed to end trip.');
      return res.data;
    },
    onSuccess: invalidateTrip,
  });

  const board = useMutation({
    mutationFn: async (payload: { student_id: number; status: DriverBoardingStatus }) => {
      const res = await driverTransportApi.markBoarding(tripId, { ...payload, date: day });
      if (!res.success) throw new Error(res.message || 'Failed to record boarding.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.driverTransport.boarding(tripId, day) });
    },
  });

  const boardBulk = useMutation({
    mutationFn: async (attendance: Array<{ student_id: number; status: DriverBoardingStatus }>) => {
      const res = await driverTransportApi.markBoardingBulk(tripId, { attendance, date: day });
      if (!res.success) throw new Error(res.message || 'Failed to record boarding.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.driverTransport.boarding(tripId, day) });
    },
  });

  const ping = useMutation({
    mutationFn: async (payload: {
      latitude: number;
      longitude: number;
      accuracy_meters?: number;
      speed_kmh?: number;
      heading?: number;
    }) => {
      const res = await driverTransportApi.pingLocation(tripId, { ...payload, date: day });
      if (!res.success) throw new Error(res.message || 'Failed to send location ping.');
      return res.data;
    },
  });

  return { start, stop, board, boardBulk, ping };
}
